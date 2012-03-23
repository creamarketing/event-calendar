<?php

class IM_Controller extends RequestHandler {

	public $controller;
	protected $name;
	protected $searchFieldname = '';
	protected $searchableFields = array('' => '');

	/*static $url_handlers = array(
		//'field/$FieldName!' => 'handleField',
		'$Action!' => 'handleAction',
		'POST ' => 'httpSubmission',
		'GET ' => 'httpSubmission',
	);	*/
	
	static $url_handlers = array(
		'messagebox/$MessageBox' => 'handleMessageBox',
		'newMessage/replyTo/$ID' => 'replyMessage',
		'newMessage/send' => 'sendMessage',
		'$Action' => '$Action'
	);
	
	public static $default_email_address = '';
	public static $default_email_template = 'IM_NotificationEmail';
	
	public function __construct($controller, $name) {
		parent::__construct();
		
		$this->controller = $controller;
		$this->name = $name;
	}
	
	function forTemplate() {
		// javascript localization
		Requirements::javascript('sapphire/javascript/i18n.js');
		Requirements::add_i18n_javascript('internal_messaging/javascript/lang');					
		
		Requirements::javascript('sapphire/thirdparty/jquery/jquery.js');
		Requirements::javascript('sapphire/thirdparty/jquery-livequery/jquery.livequery.js');
		Requirements::javascript('sapphire/thirdparty/jquery-form/jquery.form.js');
		Requirements::javascript('dialog_dataobject_manager/javascript/jquery-ui-1.8.6.custom.min.js');
		Requirements::css('dialog_dataobject_manager/css/smoothness/jquery-ui-1.8.6.custom.css');
		
		Requirements::javascript('internal_messaging/javascript/IM_Controller.js');
		Requirements::css('internal_messaging/css/IM_Controller.css');
		
		Requirements::javascript('creamarketing/javascript/AdvancedDropdownField.js');
		Requirements::javascript('creamarketing/javascript/AdvancedDropdownFieldHelpers.js');
		Requirements::css('creamarketing/css/AdvancedDropdownField.css');		
		
		// Silverstripe messes up jQuery UI tabs by rewriting hashes.. any other way to resolve the issue?
		SSViewer::setOption('rewriteHashlinks', false);
		
		return $this->renderWith('IM_Controller');
	}
	
	public function index() {
		return '';
	}
	
	public function SearchableFieldsDropdown()
	{
		$value = $this->searchFieldname;
		$map = array('' => _t('IM_Controller.ALL', 'All'));
		$map += $this->searchableFields;
		
		$dropdown = new DropdownField("SearchFieldnameSelect", '', $map, $value);
		return $dropdown->Field();
	}	
	
	public function Link($action = null) {
		return Controller::join_links($this->controller->Link(), $this->name, $action);
	}
	
	public function handleMessageBox($request) {
		return new IM_Controller_MessageBoxRequest($this, $request->param('MessageBox'));
	}
	
	public function replyMessage($request) {
		$message = DataObject::get_by_id('IM_Message', (int)$request->param('ID'));
		
		if (!$message)
			return '';
			
		$defaults = array();
		$defaults['ToID'] = $message->FromID;
		$defaults['Subject'] = _t('IM_Message.REPLY_PREPEND', 'Re') . ': ' . $message->Subject;
		$defaults['Body'] = "\n\n\n" . $message->NiceFrom . ' ' . _t('IM_Message.WROTE', 'wrote') . ' (' . $message->NiceDate . "):\n> " . str_replace("\n", "\n> ", wordwrap($message->Body, 90, "\n", true));
		
		return $this->newMessage($defaults);
	}
	
	public function newMessage($defaults = null) {
		$result = array();
				
		$subjectDefault = isset($defaults['Subject']) ? $defaults['Subject'] : _t('IM_Controller.NEWMESSAGE', 'New message');
		$bodyDefault = isset($defaults['Body']) ? $defaults['Body'] : '';
		$defaultTo = isset($defaults['ToID']) ? $defaults['ToID'] : '';

		$recipients = new DataObjectSet();
		
		$recipientsDropdownArray = array('' => '');
		$recipientsDropdownArray['MarkAll'] = array('id' => 'markall', 'class' => 'recipient-group mark-all', 'text' => '- ' . _t('IM_Controller.MARKALL', 'Mark all') . ' -');
		$recipientsDropdownArray['UnmarkAll'] = array('id' => 'unmarkall', 'class' => 'recipient-group unmark-all', 'text' => '- ' . _t('IM_Controller.UNMARKALL', 'Unmark all') . ' -');
		
		$members = DataObject::get('Member');
		if ($members) {
			$members->sort('Name');
			$recipientsDropdownArray += $members->toDropdownMap('ID', 'Name');			
		}
		
		$this->extend('updateRecipients', $recipientsDropdownArray);
					
		$toField = new AdvancedDropdownField('Recipient', _t('IM_Message.TO', 'Recipient'), $recipientsDropdownArray, $defaultTo, false, false, 'recipientDropdown_Select', 'recipientDropdown_Show');
		$toField->setGenerateUniqueID(true);
		$toField->addExtraClass('recipients');
				
		$extraData['BodyField'] = '<textarea name="Body" rows="5" columns="40">' . $bodyDefault . '</textarea>';
		$extraData['SubjectField'] = '<input class="subject" name="Subject" value="' . ($subjectDefault == _t('IM_Controller.NEWMESSAGE', 'New message') ? '' : $subjectDefault) . '">';
		$extraData['ToField'] = $toField->Field();
			
		$template = $this->renderWith('IM_NewMessage', $extraData);
		
		$result['TabTitle'] = $subjectDefault;
		$result['Template'] = $template;
		
		$response = new SS_HTTPResponse(json_encode($result));
		$response->addHeader("Content-type", "application/json");
		return $response;		
	}	
		
	public function sendMessage() {
		if (!isset($_POST['Recipient']) || !isset($_POST['Subject']) || !isset($_POST['Body']))
			return '';
		
		if (empty($_POST['Recipient']) || empty($_POST['Subject']))
			return '';
		
		$recipients = explode(',', $_POST['Recipient']);
		if (is_array($recipients) && (int)$_POST['Recipient'] != 0) {
			
			$only_members = true;
			foreach ($recipients as $recipient) {
				$recipient_classname_id = explode('_', $recipient);
				if (count($recipient_classname_id) == 2)
					$only_members = false;
			}
		
			$message = new IM_Message();
			$message->ToID = self::currentUser()->ID;
			$message->ToMany = $_POST['Recipient'];
			if ($only_members)
				$message->RecipientType = 'ManyMembers';
			else {
				$message->RecipientType = 'Other';
				$message->RecipientClassName = 'Mixed';
			}			
			$message->FromID = self::currentUser()->ID;
			$message->Subject = $_POST['Subject']; // Escaped automatically when saving the object
			$message->Body = $_POST['Body']; // Escaped automatically when saving the object
			$message->send();
		}
		else {
			$message = new IM_Message();
			
			$recipient_classname_id = explode('_', $_POST['Recipient']);
			
			if (count($recipient_classname_id) == 2) {
				$message->RecipientType = 'Other';
				$message->RecipientClassName = $recipient_classname_id[0];
				$message->ToID = (int)$recipient_classname_id[1];
			}				
			else {
				$message->ToID = (int)$_POST['Recipient'];
			}

			$message->FromID = self::currentUser()->ID;
			$message->Subject = $_POST['Subject']; // Escaped automatically when saving the object
			$message->Body = $_POST['Body']; // Escaped automatically when saving the object
			$message->send();
		}
		
		$response = new SS_HTTPResponse(json_encode(array()));
		$response->addHeader("Content-type", "application/json");
		return $response;		
	}
	
	public static function currentUser() {
		return Member::currentUser();
	}
	
	public static function UnreadMessage($user = null, $messageBox = 'inbox') {
		if (!$user) $user = self::currentUser();
		
		if ($user) {
			$messageBoxID = 0;
			
			if ($messageBox == 'inbox')
				$messageBoxID = $user->IM_InboxID;
			else if ($messageBox == 'sentbox')
				$messageBoxID = $user->IM_SentboxID;
			else if ($messageBox == 'trashbox')
				$messageBoxID = $user->IM_TrashboxID;
			
			if ($messageBoxID) {
				$count = DB::query("SELECT COUNT(IM_Message.ID) FROM IM_Message WHERE MessageBoxID = $messageBoxID AND Status = 'Unread'")->value();		
				return $count;
			}
		}
		
		return 0;
	}	
}

class IM_Controller_MessageBoxRequest extends RequestHandler {
	public $controller = null;
	protected $messageBoxName = '';
	protected $messageBox = null;
	protected $messageBoxMessages = null;
	protected $paginationStart = 0;
	protected $paginationPageSize = 10;
	protected $sortField = 'DateSort';
	protected $sortDir = 'DESC';
	
	static $url_handlers = array(
		'message/$ID' => 'handleMessage',
		'$Action!' => '$Action',
		'' => 'index',
	);	
	
	function __construct($controller, $messageBoxName) {
		parent::__construct();
		
		$this->controller = $controller;
		$this->messageBoxName = $messageBoxName;
		
		$user = IM_Controller::currentUser();
		if ($user) {
			if ($messageBoxName == 'inbox') {
				$this->messageBox = $user->IM_Inbox();
				
				// Create messageBox if it doesnt exist
				if ($this->messageBox->exists() != true) {
					$this->messageBox = new IM_MessageBox();
					$this->messageBox->OwnerID = $user->ID;
					$this->messageBox->Type = 'Inbox';
					$this->messageBox->write();
				
					$user->IM_InboxID = $this->messageBox->ID;
					$user->write();				
				}
			}
			else if ($messageBoxName == 'sentbox') {
				$this->messageBox = $user->IM_Sentbox();
				
				// Create messageBox if it doesnt exist
				if ($this->messageBox->exists() != true) {
					$this->messageBox = new IM_MessageBox();
					$this->messageBox->OwnerID = $user->ID;
					$this->messageBox->Type = 'Sentbox';
					$this->messageBox->write();
				
					$user->IM_SentboxID = $this->messageBox->ID;
					$user->write();				
				}				
			}
			else if ($messageBoxName == 'trashbox') {
				$this->messageBox = $user->IM_Trashbox();
				
				// Create messageBox if it doesnt exist
				if ($this->messageBox->exists() != true) {
					$this->messageBox = new IM_MessageBox();
					$this->messageBox->OwnerID = $user->ID;
					$this->messageBox->Type = 'Trashbox';
					$this->messageBox->write();
				
					$user->IM_TrashboxID = $this->messageBox->ID;
					$user->write();				
				}				
			}
			
			if ($this->messageBox !== null) {
				$messages = $this->messageBox->Messages();

				// Pagination
				if (!isset($_GET['start']) || !is_numeric($_GET['start']) || (int)$_GET['start'] < 1) 
					$_GET['start'] = 0;
				$this->paginationStart = (int)$_GET['start'];		
				
				// Sorting with sanity checks
				if (isset($_GET['sort'])) {
					$sort = $_GET['sort'];
					
					if ($sort == 'Date')
						$this->sortField = 'DateSort';
					else if ($sort == 'Subject')
						$this->sortField = 'Subject';
					else if ($sort == 'From')
						$this->sortField = 'NiceFrom';
					else if ($sort == 'To')
						$this->sortField = 'NiceTo';
					else if ($sort == 'Status')
						$this->sortField = 'Status';
				}
				if (isset($_GET['sortDir'])) {
					$sortDir = $_GET['sortDir'];
					if ($sortDir == 'ASC') 
						$this->sortDir = 'ASC';
					else if ($sortDir == 'DESC')
						$this->sortDir = 'DESC';
				}
				
				// Search
				if (!empty($_POST['searchText']) && $_POST['searchText'] !== _t('IM_Controller.SEARCH', 'Search')) {
					$tmpMessages = new DataObjectSet();
					foreach ($messages as $message) {
						$fields = array('DateSort', 'NiceFrom', 'NiceTo', 'Subject');
						$was_found = false;
						
						foreach ($fields as $field) {
							if (preg_match('/' . $_POST['searchText'] . '/i', $message->$field)) 
								$was_found = true;					
						}
					
						if ($was_found)
							$tmpMessages->push($message);
					}
					
					$messages = $tmpMessages;
				}

				$messages->sort($this->sortField, $this->sortDir);
				$messages->setPageLimits($this->paginationStart, $this->paginationPageSize, $messages->Count());
				
				$this->messageBoxMessages = $messages;
			}
			else
				$this->httpError(400, "Invalid messagebox");
		}
	}
	
	public function deleteAll() {
		$trashbox = $this->messageBoxName == 'trashbox' ? true : false;
		
		if (!$trashbox)
			$this->httpError(400, "Cannot delete all message from this messagebox");
		
		
		$result['DeletedMessages'] = array();
		if ($this->messageBoxMessages) {
			$result = array('DeletedMessages' => $this->messageBoxMessages->column('ID'));
			
			foreach ($this->messageBoxMessages as $message)
				$message->delete();
		}
		
		$response = new SS_HTTPResponse(json_encode($result));
		$response->addHeader("Content-type", "application/json");
		return $response;		
	}
	
	public function refresh() {
		$result = array('Messages' => '', 'Pagination' => '', 'Unread' => 0);
			
		$inbox = $this->messageBoxName == 'inbox' ? true : false;
		$sentbox = $this->messageBoxName == 'sentbox' ? true : false;
		$trashbox = $this->messageBoxName == 'trashbox' ? true : false;
		$pagination = $this->messageBoxMessages->MoreThanOnePage();
		
		$result['Messages'] = $this->renderWith('IM_MessageBox_Messages', 
			array(
				'Messages' => $this->messageBoxMessages,
				'Inbox' => $inbox,
				'Sent' => $sentbox,
				'Trash' => $trashbox,
				'MoreThanOnePage' => $pagination
			)
		);
		$result['Pagination'] = $this->renderWith('IM_MessageBox_Pagination', 
			array(
				'Messages' => $this->messageBoxMessages,
				'Inbox' => $inbox,
				'Sent' => $sentbox,
				'Trash' => $trashbox,
				'MoreThanOnePage' => $pagination
			)
		);		
		$result['Unread'] = $this->messageBox->Messages("Status = 'Unread'")->Count();
		
		$response = new SS_HTTPResponse(json_encode($result));
		$response->addHeader("Content-type", "application/json");
		return $response;			
	}
	
	protected function getQueryString($params = array()) {
		$start = isset($params['start']) ? $params['start'] : 0;
		$sort = isset($params['sort']) ? $params['sort'] : $this->SortFieldForTemplate($this->sortField);
		$sortDir = isset($params['sortDir']) ? $params['sortDir'] : $this->sortDir;
		return "start={$start}&sort={$sort}&sortDir={$sortDir}";
	}
	
	public function Link($action = null)
	{
		return Controller::join_links($this->controller->Link('messagebox'), $this->messageBoxName, $action);
	}
	
	public function FirstItem() {
		if ($this->TotalCount() < 1) return 0;
		return $this->messageBoxMessages->FirstItem();
	}
	
	public function FirstLink() {
		if ($this->messageBoxMessages->CurrentPage() == 1)
			return 0;
		
		return $this->PaginationLink(array('start' => 0));
	}	
	
	public function LastItem() {
		if ($this->TotalCount() < 1) return 0;
		return $this->messageBoxMessages->LastItem();
	}	
	
	public function LastLink() {
		if ($this->messageBoxMessages->CurrentPage() == $this->messageBoxMessages->Pages()->Count())
			return 0;
		
		$start = ($this->messageBoxMessages->Pages()->Count()-1) * $this->paginationPageSize;
		return $this->PaginationLink(array('start' => $start));
	}
	
	public function NextLink() {
		$nextPage = $this->messageBoxMessages->CurrentPage()+1;
		if ($nextPage >= $this->messageBoxMessages->Pages()->Count()) // Last page
			return $this->LastLink();
		
		$start = ($nextPage-1) * $this->paginationPageSize;
		return $this->PaginationLink(array('start' => $start));
	}
	
	public function PrevLink() {
		$prevPage = $this->messageBoxMessages->CurrentPage()-1;
		if ($prevPage < 1)
			return 0;
		
		$start = ($prevPage-1) * $this->paginationPageSize;
		return $this->PaginationLink(array('start' => $start));
	}
	
	public function TotalCount() {
		return $this->messageBoxMessages->Count();
	}
	
	public function RefreshLink() {
		return Controller::join_links($this->Link('refresh'), '?'.$this->getQueryString());
	}
	
	public function PaginationLink($params = array())
	{
		return Controller::join_links($this->Link('refresh'), '?'.$this->getQueryString($params));
	}	
	
	public function MessageLink($id = null, $action = null) {
		return Controller::join_links($this->Link('message'), $id, $action);
	}
	
	public function handleMessage($request) {
		return new IM_Controller_MessageRequest($this, $this->messageBox, $request->param('ID'));
	}
	
	protected function IsCurrentSortField($fieldName) {
		if ($this->SortFieldForTemplate($this->sortField) == $fieldName)
			return true;
		return false;
	}
	
	protected function SortFieldForTemplate($fieldName = '') {	
		if ($fieldName == 'DateSort') 
			return 'Date';
		else if ($fieldName == 'NiceFrom') 
			return 'From';
		else if ($fieldName == 'NiceTo') 
			return 'To';
		
		return $fieldName;
	}
	
	protected function SortDirForTemplate() {
		return strtolower($this->sortDir);
	}
	
	protected function SortLink($fieldName = '') {
		$sortDir = 'ASC';
		
		if ($this->SortFieldForTemplate($this->sortField) == $fieldName && $this->sortDir == 'ASC')
			$sortDir = 'DESC';
				
		return Controller::join_links($this->Link('refresh'), '?'.$this->getQueryString(array('sort' => $this->SortFieldForTemplate($fieldName), 'sortDir' => $sortDir)));
	}
}

class IM_Controller_MessageRequest extends RequestHandler {
	public $controller = null;
	protected $messageBox = null;
	protected $messageID = 0;
	protected $message = null;
	
	static $url_handlers = array(
		'$Action!' => '$Action',
		'' => 'index',
	);		
	
	function __construct($controller, $messageBox, $messageID) {
		parent::__construct();
		
		$this->controller = $controller;
		$this->messageBox = $messageBox;
		$this->messageID = $messageID;
		$this->message = $this->messageBox->Messages()->find('ID', $messageID);

		if (!$this->message)
			$this->httpError(400, "Invalid message ID");
	}
	
	public function markUnread() {
		$this->message->Status = 'Unread';
		$this->message->write();
		
		$count = DB::query("SELECT COUNT(IM_Message.ID) FROM IM_Message WHERE MessageBoxID = {$this->messageBox->ID} AND Status = 'Unread'")->value();
		
		$result = array('Unread' => $count, 'Link' => $this->Link('markRead'));
		
		$response = new SS_HTTPResponse(json_encode($result));
		$response->addHeader("Content-type", "application/json");
		return $response;
	}
	
	public function markRead() {
		$this->message->Status = 'Read';
		$this->message->write();
		
		$count = DB::query("SELECT COUNT(IM_Message.ID) FROM IM_Message WHERE MessageBoxID = {$this->messageBox->ID} AND Status = 'Unread'")->value();
		
		$result = array('Unread' => $count, 'Link' => $this->Link('markUnread'));
		
		$response = new SS_HTTPResponse(json_encode($result));
		$response->addHeader("Content-type", "application/json");
		return $response;
	}	
	
	public function trash() {
		$result = array();
		$result['ID'] = $this->message->ID;		
		
		$owner = $this->messageBox->Owner();
		$trashBox = $owner->IM_Trashbox();
		
		$this->message->RestoreTo = $this->messageBox->ID;
		$this->message->MessageBoxID = $trashBox->ID;
		$this->message->write();
		
		$response = new SS_HTTPResponse(json_encode($result));
		$response->addHeader("Content-type", "application/json");
		return $response;
	}
	
	public function restore() {
		$result = array();
		$result['ID'] = $this->message->ID;		
		
		$this->message->MessageBoxID = $this->message->RestoreTo;
		$this->message->RestoreTo = 0;
		$this->message->write();
		
		$response = new SS_HTTPResponse(json_encode($result));
		$response->addHeader("Content-type", "application/json");
		return $response;		
	}
	
	public function delete() {
		$result = array();
		$result['ID'] = $this->message->ID;
		
		$this->message->delete();
		
		$response = new SS_HTTPResponse(json_encode($result));
		$response->addHeader("Content-type", "application/json");
		return $response;		
	}
	
	public function Link($action = null) {
		return Controller::join_links($this->controller->Link('message'), $this->messageID, $action);
	}	
	
	public function index() {
		$result = array();
		$result['ID'] = $this->message->ID;
		$result['FromID'] = $this->message->FromID;
		$result['From'] = $this->message->NiceFrom;
		$result['ToID'] = $this->message->ToID;
		$result['To'] = $this->message->NiceTo;
		$result['RecipientType'] = $this->message->RecipientType;
		$result['Status'] = $this->message->Status;
		$result['Date'] = date('d.m.Y H:i', strtotime($this->message->Created));
		$result['Subject'] = $this->message->Subject;
		$result['Body'] = $this->message->Body;
		$result['Template'] = $this->message->renderWith('IM_Message', 
				array(
					'ReplyLink' => Controller::join_links($this->controller->controller->Link('newMessage'), 'replyTo', $this->message->ID),
					'TrashLink' => $this->Link('trash'),
					'DeleteLink' => $this->Link('delete'),
					'RestoreLink' => $this->Link('restore')
				)
		);
		
		$response = new SS_HTTPResponse(json_encode($result));
		$response->addHeader("Content-type", "application/json");
		return $response;		
	}
}

?>
