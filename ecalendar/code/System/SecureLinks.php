<?php

// $_GET['confirmDialog'] is set if called from confirm dialog

class SecureLinkRequest extends DataObject {
	static $db = array(
		'MethodName' => 'Varchar(255)',
		'MethodID' => 'Int',
		'MethodAction' => 'Varchar(255)',
		'AccessToken' => 'Varchar(40)',
		'ExpirationDate' => 'Date'
	);
	
	public function Link() {
		return Controller::join_links(singleton('SecureLinks')->Link(), $this->MethodName, $this->MethodID, $this->AccessToken, $this->MethodAction);
	}
	
	public static function generate_access_token() {
		$access_token = sha1(uniqid('access_token_', true));
		return $access_token;
	}
	
	public static function generate_link_object($methodName, $methodID, $methodAction = '', $expirationDate = null) {
		$linkRequest = new SecureLinkRequest();
		$linkRequest->MethodName = $methodName;
		$linkRequest->MethodID = $methodID;
		$linkRequest->AccessToken = self::generate_access_token();
		$linkRequest->MethodAction = $methodAction;
		if ($expirationDate)
			$linkRequest->ExpirationDate = $expirationDate;
		
		$linkRequest->write();
		return $linkRequest;
	}
	
	public static function generate_link($methodName, $methodID, $methodAction = '', $expirationDate = null) {
		$linkRequest = self::generate_link_object($methodName, $methodID, $methodAction, $expirationDate);
		return $linkRequest->Link();
	}
	
	public static function find_link($methodName, $methodID, $accessToken, $methodAction = '', $checkExpirationDate = true) {	
		$linkRequest = DataObject::get_one('SecureLinkRequest', "MethodName = '" . Convert::raw2sql($methodName) . "' AND MethodID = " . (int)$methodID . " AND (MethodAction = '" . Convert::raw2sql($methodAction) . "' OR MethodAction IS NULL) AND AccessToken = '" . Convert::raw2sql($accessToken) . "'");
		if ($linkRequest) {
			// Still valid?
			if ($linkRequest->ExpirationDate && $checkExpirationDate) {
				if ($linkRequest->ExpirationDate < date('Y-m-d')) // Is ExpirationDate in the past?
					return false;
			}
		}
		return $linkRequest;
	}
	
	public static function consume_link($methodName, $methodID, $accessToken, $methodAction = '') {
		$link = self::find_link($methodName, $methodID, $accessToken, $methodAction, false);
		if ($link)
			$link->delete();
	}
}

class SecureLinks extends Controller {	
	static $allowed_actions = array(
		'KeepMemberAlive',
		'AcceptMember',
		'HandleNewAssociation',	
		'PermissionRequest',
		'UserInviteRequest',
		'handleMethodRequest',
		'index'
	);
	
	public static $url_handlers = array(
		'$Action/$ID/$OtherID/$OtherAction' => 'handleMethodRequest'
	);
		
	protected $lastMember = null;
	protected $systemAdmin = null;
	protected $confirmDialog = false;
	
	public function index() {
		return '';
	}
	
	public function Link() {
		return Director::absoluteBaseURL() . 'SecureLinks';
	}
	
	protected function handleMethodRequest() {
		$action = $this->urlParams['Action'];
		$id = (int)$this->urlParams['ID'];
		$accessToken = $this->urlParams['OtherID'];
		$methodAction = $this->urlParams['OtherAction'];
		$methodResult = '';
		
		if ($this->hasMethod($action) && in_array($action, self::$allowed_actions)) {
			$methodResult = $this->$action();
			
			if ($this->confirmDialog == false) { 
				// Called from normal browser window
				
				$page = Translatable::get_one_by_lang('SiteTree', i18n::get_locale(), "ClassName = 'Page_Controller'");
				if (!$page) {
					$page = DataObject::get_one('Page_Controller');
				}
				
				if ($page && $page->Locale != Translatable::get_current_locale()) {
					$page = $page->getTranslation(Translatable::get_current_locale());
				}

				$tmpPage = new Page();
				$tmpPage->Title = 'Ostrobotnia Eventcalendar';
				$tmpPage->ID = -1;
				$controller = new Page_Controller($tmpPage);
				$controller->init();
					
				$siteConfig = $page ? $page->getSiteConfig() : $tmpPage->getSiteConfig();
				
				$customisedController = $controller->customise(array(
					'Content' => '<p>' . $methodResult . '</p>',
					'SiteConfig' => $siteConfig,
					'Title' => $siteConfig->Tagline
				));

				$methodResult = $customisedController->renderWith(array('Page'));
			}		
		}
		
		return $methodResult;
	}
	
	function init() {
		parent::init();
		
		$member = Member::currentUser();
		if ($member && $member->Locale)
			i18n::set_locale($member->Locale);
		else
			i18n::set_locale('fi_FI');
		
		if (isset($_GET['confirmDialog'])) 
			$this->confirmDialog = true;

		if (isset($_GET['locale']) && in_array($_GET['locale'], Translatable::get_allowed_locales()))
			i18n::set_locale($_GET['locale']);

		if (isset($_GET['email']) && !Member::currentUser()) {
			$recipientEmail = Convert::raw2sql($_GET['email']);
			$member = DataObject::get_one('Member', "Email = '$recipientEmail'");
			if ($member && $member->Locale)
				i18n::set_locale($member->Locale);
		}
	}
	
	
	public function HandleNewAssociation() {
		$associationId = (int)$this->urlParams['ID'];
		$accessToken = $this->urlParams['OtherID'];
		$methodAction = $this->urlParams['OtherAction'];
		
		$linkRequest = SecureLinkRequest::find_link('HandleNewAssociation', $associationId, $accessToken, $methodAction);
		if (!$linkRequest)
			return _t('SecureLinks.NOTVALIDLINK', 'Not a valid link or this link has expired.');
		
		$association = DataObject::get_by_id('Association', (int)$associationId);
		if ($association) {
			$this->SwitchToAdmin();	
			if ($methodAction == 'accept') {
				$association->Status = 'Active';
			} elseif ($methodAction == 'reject') {
				$association->Status = 'Passive';
			}
				
			$association->write();
			
			SecureLinkRequest::consume_link('HandleNewAssociation', $associationId, $accessToken, $methodAction);		
			
			$this->SwitchToUser();
			
			if ($methodAction == 'accept') {
				return sprintf(_t('Association.ASSOCIATIONACCEPTED', 'The association %s was now accepted.'), $association->Name);	
			} elseif ($methodAction == 'reject') {
				return sprintf(_t('Association.ASSOCIATIONREJECTED', 'The association %s was now rejected.'), $association->Name);
			}
		} else {
			return _t('Association.ASSOCIATIONNOTFOUND', 'Association does no longer exist!!');
		}
	}
	
	// NOTE! Changing this password will make all Accept Links invalid
	
	public function AcceptMember() {
		$id = (int)$this->urlParams['ID'];
		$accessToken = $this->urlParams['OtherID'];
		$methodAction = $this->urlParams['OtherAction'];
			
		$linkRequest = SecureLinkRequest::find_link('AcceptMember', $id, $accessToken, $methodAction);
		if (!$linkRequest)
			return _t('SecureLinks.NOTVALIDLINK', 'Not a valid link or this link has expired.');
		
		$organizer = DataObject::get_by_id('AssociationOrganizer', $id);
		
		if ($organizer) {	
			$return = sprintf( _t('AssociationOrganizer.CANNOWPUBLISH', 'User %s can now publish events directly and all Preliminary events have been accepted'), $organizer->FullName ).'<br />';
			if ($organizer->PermissionPublish) {
				$return = _t('AssociationOrganizer.USERALREADYACCEPTED', 'User is already accepted.');
				return $return;
			} 
			$currentMember = Member::currentUser();
			$organizer->logIn();
			$organizer->PermissionPublish = true;
			$organizer->write();
			// Accept also the Associations
			$permissions = $organizer->AssociationPermissions("Type = 'Moderator'");
			if ($permissions) {
				foreach ($permissions as $permission) {
					$association = $permission->Association();
					if ($association && $association->Status == 'New') {
						$return.= sprintf(_t('AssociationOrganizer.ASSOCIATIONACCEPTED','Association %s is now also Active'), $association->Name).'<br />';
					}
				}
			}
			$organizer->logOut();
			if ($currentMember)	// Log original member back in
				$currentMember->logIn();
		} else {
			$return = 'Not a valid user.';
		}
		
		return $return;
	}
	
	public function KeepMemberAlive() {
		$id = (int)$this->urlParams['ID'];
		$accessToken = $this->urlParams['OtherID'];		
		$methodAction = $this->urlParams['OtherAction'];		
		
		$linkRequest = SecureLinkRequest::find_link('KeepMemberAlive', $id, $accessToken, $methodAction);
		if (!$linkRequest)
			return _t('SecureLinks.NOTVALIDLINK', 'Not a valid link or this link has expired.');		
		
		$member = DataObject::get_by_id('Member', $id);
		
		if ($member) {
			if ($member->LastVisited = date('Y-m-d H:i:s', $methodAction)) {
				$member->LastVisited = date('Y-m-d H:i:s');
				$member->write();
				$return = sprintf(_t('AssociationOrganizer.MEMBERUPDATED', 'User %s will no longer be deleted, next check is after 18 months.'), $member->FullName);			
			}
			else {
				$return = 'User has already logged.';
			}
		} else {
			$return = 'Not a valid user.';
		}
		
		// Prevent this request from being used again
		$linkRequest->delete();		
		
		return $return;
	}
	
	public function PermissionRequest() {		
		$id = (int)$this->urlParams['ID'];
		$accessToken = $this->urlParams['OtherID'];		
		$methodAction = $this->urlParams['OtherAction'];		
		
		$linkRequest = SecureLinkRequest::find_link('PermissionRequest', $id, $accessToken, $methodAction);
		if (!$linkRequest)
			return _t('SecureLinks.NOTVALIDLINK', 'Not a valid link or this link has expired.');
				
		$permissionRequest = DataObject::get_one('PermissionRequest', "PermissionRequest.ID = '$id' AND Status = 'New'");
		if (!$permissionRequest) {
			return _t('PermissionRequest.ALREADYPROCESSED', 'This request has already been processed.');
		}
	
		$this->SwitchToAdmin();		
		
		$myassociations = PermissionExtension::getMyAssociations(null, 'moderators', true);
			
		if (!in_array($permissionRequest->Association()->ID, $myassociations) && !eCalendarExtension::isAdmin()) {
			return 'Not allowed!';
		}
		
		$originalLocale = i18n::get_locale();
		$currentLocale = $permissionRequest->User()->Locale; 
		i18n::set_locale($currentLocale);							
				
		switch ($methodAction) {
			case 'accept':
				
				// Checking first if it already exists, then it will change the old
				$permission = DataObject::get_one('AssociationPermission', "AssociationID = '".$permissionRequest->Association()->ID."' AND AssociationOrganizerID = '".$permissionRequest->User()->ID."'");
				if (!$permission) {
					$permission = new AssociationPermission();
					$permission->AssociationID = $permissionRequest->Association()->ID;
					$permission->AssociationOrganizerID = $permissionRequest->User()->ID;
				}
				
				$permission->Type = $permissionRequest->PermissionType;
				$permission->write();
				
				$permissionRequest->Status = 'Accepted';
				$permissionRequest->write();
				
				$subject = _t('PermissionRequest.ACCEPTEDNOTICE_SUBJECT', 'Permissionrequest accepted');
				$body = sprintf(_t('PermissionRequest.ACCEPTEDNOTICE_BODY1', 'Permissionrequest accepted, you are now %s for %s.'),
								_t('AssociationPermission.TYPE_'.strtoupper( $permissionRequest->PermissionType ), $permissionRequest->PermissionType ), 
								$permissionRequest->Association()->Name
				);
				$msg = new IM_Message();	
				$msg->Subject = $subject;
				$msg->Body = $body;								
				$msg->ToID = $permissionRequest->User()->ID;
				$msg->send(false);		
				
				// Switch to other locale for responce text
				i18n::set_locale($originalLocale);
				$return = sprintf(_t('PermissionRequest.ACCEPTEDREQUEST', 'Accepted "%s" as "%s".'), $permissionRequest->User()->FullName, $permissionRequest->NicePermissionType).' -&gt; '.$permissionRequest->Association()->Name;
				i18n::set_locale($currentLocale);
			break;
		
			case 'reject':
				$permissionRequest->Status = 'Rejected';
				$permissionRequest->write();
				
				$subject = _t('PermissionRequest.REJECTEDNOTICE_SUBJECT', 'Permissionrequest rejected');
				$body = sprintf( 
					_t('PermissionRequest.REJECTEDNOTICE_BODY1', 'Permissionrequest was rejected in %s'),						
					$permissionRequest->Association()->Name
				);
				$msg = new IM_Message();	
				$msg->Subject = $subject;
				$msg->Body = $body;								
				$msg->ToID = $permissionRequest->User()->ID;
				$msg->send(false);	
				
				// Switch to other locale for responce text
				i18n::set_locale($originalLocale);
				$return = sprintf(_t('PermissionRequest.REJECTEDREQUEST', 'Rejected "%s" as "%s".'), $permissionRequest->User()->FullName, $permissionRequest->NicePermissionType);
				i18n::set_locale($currentLocale);
			break;
		
			default: 
				$return = ''; 
			break;
		}
		
		i18n::set_locale($originalLocale);
		
		$this->SwitchToUser();
		
		return $return;
	}

	public function UserInviteRequest() {		
		$id = (int)$this->urlParams['ID'];
		$accessToken = $this->urlParams['OtherID'];		
		$methodAction = $this->urlParams['OtherAction'];		
		
		$linkRequest = SecureLinkRequest::find_link('UserInviteRequest', $id, $accessToken, $methodAction);
		if (!$linkRequest)
			return _t('SecureLinks.NOTVALIDLINK', 'Not a valid link or this link has expired.');
				
		$inviteRequest = DataObject::get_one('UserInviteRequest', "UserInviteRequest.ID = '$id' AND UserInviteRequest.Status = 'New'");
		if (!$inviteRequest) {
			return _t('UserInviteRequest.ALREADYPROCESSED', 'This invitation has already been processed.');
		}
	
		$this->SwitchToAdmin();		
				
		$originalLocale = i18n::get_locale();
		$currentLocale = $inviteRequest->Creator()->Locale; 
		i18n::set_locale($currentLocale);							
				
		switch ($methodAction) {
			case 'accept':
				
				// Checking first if it already exists, then it will change the old
				$permission = DataObject::get_one('AssociationPermission', "AssociationID = '".$inviteRequest->Association()->ID."' AND AssociationOrganizerID = '".$inviteRequest->User()->ID."'");
				if (!$permission) {
					$permission = new AssociationPermission();
					$permission->AssociationID = $inviteRequest->Association()->ID;
					$permission->AssociationOrganizerID = $inviteRequest->User()->ID;
				}
				
				$permission->Type = $inviteRequest->PermissionType;
				$permission->write();
				
				$inviteRequest->Status = 'Accepted';
				$inviteRequest->write();
				
				$subject = _t('UserInviteRequest.ACCEPTEDNOTICE_SUBJECT', 'Invitation accepted');
				$body = sprintf(_t('UserInviteRequest.ACCEPTEDNOTICE_BODY1', 'Invitation accepted, %s is now "%s" for "%s".'),
								$inviteRequest->User()->FullName,
								_t('AssociationPermission.TYPE_'.strtoupper( $inviteRequest->PermissionType ), $inviteRequest->PermissionType ), 
								$inviteRequest->Association()->Name
				);
				$msg = new IM_Message();	
				$msg->Subject = $subject;
				$msg->Body = $body;								
				$msg->ToID = $inviteRequest->Creator()->ID;
				$msg->send(false);		
				
				// Switch to other locale for responce text
				i18n::set_locale($originalLocale);
				$return = sprintf(_t('UserInviteRequest.ACCEPTEDREQUEST', 'Accepted invitation to "%s".'), $inviteRequest->Association()->Name);
				i18n::set_locale($currentLocale);
			break;
		
			case 'reject':
				$inviteRequest->Status = 'Rejected';
				$inviteRequest->write();
				
				$subject = _t('UserInviteRequest.REJECTEDNOTICE_SUBJECT', 'Invitation rejected');
				$body = sprintf( 
					_t('UserInviteRequest.REJECTEDNOTICE_BODY1', 'Invitation for "%s" to "%s" has been rejected.'),
					$inviteRequest->User()->FullName,
					$inviteRequest->Association()->Name
				);
				$msg = new IM_Message();	
				$msg->Subject = $subject;
				$msg->Body = $body;								
				$msg->ToID = $inviteRequest->Creator()->ID;
				$msg->send(false);	
				
				// Switch to other locale for responce text
				i18n::set_locale($originalLocale);
				$return = sprintf(_t('UserInviteRequest.REJECTEDREQUEST', 'Rejected invitation to "%s".'), $inviteRequest->Association()->Name);
				i18n::set_locale($currentLocale);
			break;
		
			default: 
				$return = ''; 
			break;
		}
		
		i18n::set_locale($originalLocale);
		
		$this->SwitchToUser();
		
		return $return;
	}	
	
	protected function SwitchToAdmin() {
		$this->lastMember = Member::currentUser();
		$this->systemAdmin = null;
		
		if ($this->lastMember)
			$this->lastMember->logOut();
		
		$admin = eCalendarExtension::FindSystemAdministrator();
		if ($admin) {
			$this->systemAdmin = $admin;
			$this->systemAdmin->logIn();
		}
	}
	
	protected function SwitchToUser() {
		if ($this->systemAdmin)
			$this->systemAdmin->logOut();
		if ($this->lastMember)
			$this->lastMember->logIn();
	}
}

?>
