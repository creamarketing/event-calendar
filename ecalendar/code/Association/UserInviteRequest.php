<?php

class UserInviteRequest extends DataObject {
	static $extensions = array(			
		'CreaDataObjectExtension'
	);
	
	static $db = array(		
		'PermissionType' => 'Enum("Organizer,Moderator", "Organizer")',
		'Status' => 'Enum("New,Accepted,Rejected", "New")',
		'AcceptLinkID' => 'Int',
		'RejectLinkID' => 'Int'
	);	
	
	static $has_one = array(
		'Creator' => 'Member',
		'User' => 'AssociationOrganizer',
		'Association' => 'Association',	
	);	
	
	static $defaults = array(	
		'Status' => 'New',
		'PermissionType' => 'Organizer'
	);
	
	static $default_sort = 'Created';
	
	protected $ignoreCanEdit = false;
	
	public function canCreate() {
		return true;
	}
	
	public function canView() {
		return true;
	}
	
	public function canEdit($member = null) {
		if ($this->ignoreCanEdit)
			return true;
		
		if (!$member)
			$member = Member::currentUser ();
		
		if (!$this->ID)
			return true;
		
		if ($this->User()->ID == $member->ID)
			return true;
		
		$canEdit = parent::canEdit($member);	
		return $canEdit;
	}
	
	public function getUserFullName() {
		return $this->User()->FullName;
	}
	
	public function getUserEmail() {
		return $this->User()->Email;
	}
	
	public function getNicePermissionType() {
		return _t('AssociationPermission.TYPE_' . strtoupper($this->PermissionType), $this->PermissionType);		
	}	
		
	public function requireDefaultRecords() {
		parent::requireDefaultRecords();
		
		// Cleanup..
		$requests = DataObject::get('PermissionRequest');
		if ($requests) {
			foreach ($requests as $request) {
				if (!$request->AcceptLinkID || $request->RejectLinkID)
					$request->delete();
			}
		}
	}	
	
	public function getRequirementsForPopup() {		
		$this->extend('getRequirementsForPopup');

		Requirements::css('ecalendar/css/UserInviteRequestDialog.css');
		Requirements::javascript('ecalendar/javascript/UserInviteRequestDialog.js');
		Requirements::javascript('thirdparty/tipsy-0.1.7/src/javascripts/jquery.tipsy.js');
		Requirements::css('thirdparty/tipsy-0.1.7/src/stylesheets/tipsy.css');
		Requirements::customScript('jQuery(function() { jQuery(".tipsy-hint").tipsy({fade: true, gravity: "w", html: true }); });');			
	}	
	
	public function getCMSFields() {
		if (isset($_GET['userID'])) {
			$this->UserID = (int)$_GET['userID'];
		}		
		
		$associations = array('' => _t('AdvancedDropdownField.NONESELECTED', '(None selected)'));
		$myassociations = PermissionExtension::getMyAssociations(null, 'organizers', true);
		if ($myassociations) {
			foreach ($myassociations as $associationid) {
				$association = DataObject::get_by_id('Association', $associationid);
				if ($association)
					$associations[$association->ID] = $association->NameHierachyAsTextWithStatus;
			}
		}
		else if (eCalendarExtension::isAdmin()) {
			$associations = DataObject::get('Association')->map('ID', 'NameHierachyAsTextWithStatus', _t('AdvancedDropdownField.NONESELECTED', '(None selected)'));
		}
		
		// Remove associations we are already invited to 
		$existingInvitations = DataObject::get('UserInviteRequest', 'UserID = ' . (int)$this->UserID . " AND Status = 'New'");
		if ($existingInvitations) {
			foreach ($existingInvitations as $existingInvitation) {
				if (isset($associations[$existingInvitation->AssociationID])) {
					unset($associations[$existingInvitation->AssociationID]);
				}
			}		
		}
		
		// Remove associations we are already a member of
		if ($this->User()->exists()) {
			$existingAssociations = PermissionExtension::getMyAssociations($this->User(), 'organizers', true);
			if ($existingAssociations) {
				foreach ($existingAssociations as $existingAssociationID) {
					if (isset($associations[$existingAssociationID])) {
						unset($associations[$existingAssociationID]);
					}
				}
			}
			else if (eCalendarExtension::isAdmin($this->User())) { // Do not invite admins..
				$associations = array('' => _t('AdvancedDropdownField.NONESELECTED', '(None selected)'));
			}
		}
		
		$fields = new FieldSet(
			$tabset = new DialogTabSet('Tabset',
				new Tab('Main', _t('UserInviteRequest.GENERALTAB'),
					new LabelField('UserName', '<strong>' . _t('AssociationOrganizer.SINGULARNAME', 'User') . ':</strong> ' . $this->User()->FullName . '<br/>', null, true),
					new LabelField('UserEmail', '<strong>' . _t('Member.EMAIL', 'Email') . ':</strong> ' . $this->User()->Email . '<br/><br/>', null, true),
					new HiddenField('UserID'),
					$associationField = new AdvancedDropdownField(
						'AssociationID', 
						_t('Association.SINGULARNAME', 'Association'). '<span class="tipsy-hint" title="' . _t('UserInviteRequest.HINT_ASSOCIATION', 'Select which association you want to invite the user to.') . '"></span>',
						$associations
					),
					new LabelField('Help', _t('UserInviteRequest.EXPLANATIONTEXT', 'When you click "Save" this application will be sent to the system moderator and you will recieve a message when it has been checked.'), null, true)
				)
			)
		);
		
		return $fields;
	}
	
	public function getValidator() {
		return new UserInviteRequest_Validator($this);
	}
	
	protected function onBeforeWrite() {
		parent::onBeforeWrite();
		if (!$this->CreatorID)
			$this->CreatorID = Member::currentUserID();
		
		if ($this->ID) {
			if (!$this->AcceptLinkID)
				$this->AcceptLinkID = SecureLinkRequest::generate_link_object('UserInviteRequest', $this->ID, 'accept', date('Y-m-d', strtotime('+1 month')))->ID;
			if (!$this->RejectLinkID)
				$this->RejectLinkID = SecureLinkRequest::generate_link_object('UserInviteRequest', $this->ID, 'reject', date('Y-m-d', strtotime('+1 month')))->ID;			
		}
	}
	
	public function AcceptLink() {
		$linkRequest = DataObject::get_by_id('SecureLinkRequest', (int)$this->AcceptLinkID);
		if ($linkRequest && $this->canEdit())
			return $linkRequest->Link();
		return '';
	}
	
	public function AcceptLinkNice() {
		$link = $this->AcceptLink();
		if (!empty($link))
			return '<a class="accept-reject-link" href="'.$link.'">'._t('PermissionRequest.ACCEPT', 'Accept').'</a>';
		return '';
	}
	
	public function RejectLink() {
		$linkRequest = DataObject::get_by_id('SecureLinkRequest', (int)$this->RejectLinkID);
		if ($linkRequest && $this->canEdit())
			return $linkRequest->Link();
		return '';
	}	
	
	public function RejectLinkNice() {
		$link = $this->RejectLink();
		if (!empty($link))
			return '<a class="accept-reject-link" href="'.$link.'">'._t('PermissionRequest.REJECT', 'Reject').'</a>';
		return '';
	}	
	
	public function onAfterWrite() {		
		parent::onAfterWrite();
		
		$member = Member::currentUser();			
		$originalLocale = i18n::get_locale();
			
		if ($this->ID && $this->isChanged('AssociationID')) {	
			$changedFields = $this->getChangedFields(true, 2);			
			if ($changedFields['AssociationID']['before'] == 0 && $changedFields['AssociationID']['after'] != 0) {
				eCalendarExtension::SendMemberSelfNewInviteRequestMessage($this->Creator(), $this->User(), $this->Association());
				$this->sendUserInviteRequest($this->Creator(), $this->User(), $this->Association(), $this->PermissionType);
			}
		}

		i18n::set_locale($originalLocale);
	}
	
	public function sendUserInviteRequest($creator, $member, $association, $permissiontype) {
		$this->ignoreCanEdit = true;
		
		$currentLocale = $member->Locale; 
		i18n::set_locale($currentLocale);							

		$subject = _t('UserInviteRequest.CREATEDNOTICE_SUBJECT', 'Invitation');
		$body = sprintf( 
				_t('UserInviteRequest.CREATEDNOTICE_BODY1', '%s has invited you to "%s".'),
				$creator->FullName,
				$association->Name
		)."\n\n";

		$body .= sprintf(_t('UserInviteRequest.CREATEDNOTICE_BODY2', 'Click [url=%s]here[/url] to accept it or [url=%s]here[/url] to reject it.'), $this->AcceptLink(), $this->RejectLink());
		$msg = new IM_Message();	
		$msg->Subject = $subject;
		$msg->Body = $body;								
		$msg->ToID = $member->ID;
		$msg->send(false);		
		
		$this->ignoreCanEdit = false;
	}	
}

class UserInviteRequest_Validator extends RequiredFields {
	protected $userInvite = null;
	
	public function __construct($userInviteObject) { 
		$this->userInvite = $userInviteObject;
		
		parent::__construct(); 
	}
   
	function php($data) { 
		$valid = parent::php($data); 
		if(isset($_REQUEST['ctf']['childID'])) { 
			$id = (int)$_REQUEST['ctf']['childID']; 
		} elseif(isset($_REQUEST['ID'])) { 
			$id = (int)$_REQUEST['ID']; 
		} else { 
			$id = null; 
		} 
	  
		// Can only change association to an association in a association where Im organizer or more		
		$myassociations = PermissionExtension::getMyAssociations(null, 'organizers', true);
		if (!empty($data['AssociationID']) && !eCalendarExtension::isAdmin() ) {					
			if (!in_array($data['AssociationID'], $myassociations) && $this->userInvite->ID == 0) {
				$this->validationError('AssociationID', sprintf(_t('eCalendarAdmin.ERROR_PERMISSION', 'Not allowed to set this value for %s'), _t('Association.SINGULARNAME', 'Association')));
				return false;
			}
		} 

		$requiredFields = array(		
			'AssociationID' => 'Association.SINGULARNAME'
		);
		
		foreach ($requiredFields as $key => $value) {
			if (isset($data[$key]) && empty($data[$key])) {
				$this->validationError($key, sprintf(_t('DialogDataObjectManager.FILLOUT', 'Please fill out %s'), _t($value, $value)));
				return false;
			}
		}
       
      return $valid; 
   }    
}

?>
