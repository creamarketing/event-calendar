<?php
class PermissionRequest extends DataObject {
	
	static $extensions = array(			
			'TranslatableDataObject', // For association
			'CreaDataObjectExtension'
	);
	
	static $db = array(		
			'PermissionType' => 'Varchar(20)',	
			'Status' => 'Enum("New,Accepted,Rejected", "New")',
			'AcceptLinkID' => 'Int',
			'RejectLinkID' => 'Int'
	);
	
	static $has_one = array(
			'User' => 'AssociationOrganizer',			
			'Association' => 'Association',	
	);
 
	static $translatableFields = array(	
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
	
		$condition1 = false;
		$condition2 = false;

		$association = $this->Association();
		if ($association && singleton('PermissionExtension')->canEdit($member, $association)) 
			$condition1 = true;

		$myassociations = singleton('PermissionExtension')->getMyAssociations(null, 'moderators', true);
		if ( in_array($association->ID, $myassociations) ) 
			$condition2 = true;

		if ($condition1 || $condition2)
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
	
	public function getDOMTitle() {
		$member = Member::currentUser();
		return sprintf( _t('PermissionRequest.PERMISSIONREQUESTFOR', 'Permission request for %s'), $member->FullName);
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
		Requirements::css('ecalendar/css/PermissionRequestDialog.css');
		Requirements::javascript('ecalendar/javascript/PermissionRequestDialog.js');
		Requirements::javascript('thirdparty/tipsy-0.1.7/src/javascripts/jquery.tipsy.js');
		Requirements::css('thirdparty/tipsy-0.1.7/src/stylesheets/tipsy.css');
		Requirements::customScript('jQuery(function() { jQuery(".tipsy-hint").tipsy({fade: true, gravity: "w", html: true }); });');			
	}
	
	public function getCMSFields() {	
		$tmpNewAssoc = new Association();
		$tmpNewAssoc->Status = 'New';
		
		$fields = $tmpNewAssoc->getStandardFields( _t('PermissionRequest.NEWASSOCIATION', 'Register new association'), 'New' );
		$tmpNewAssoc->extend('updateCMSFields', $fields);
		
		$fields->removeByName('Note');
		
		$member = Member::currentUser();					
			
		$toExistingTab = new Tab(
			'ToExistingTab', 
			_t('PermissionRequest.GENERALTAB', 'Application')														
		);					

		$fields->insertBefore($toExistingTab, 'GeneralTab');
		
		$associations = DataObject::get('Association')->map('ID', 'NameHierachyAsTextWithStatus', _t('AdvancedDropdownField.NONESELECTED', '(None selected)'));
		$myassociations = PermissionExtension::getMyAssociations(null, 'organizers', true, true);
		foreach ($myassociations as $associationid) {
			if (isset($associations[$associationid])) {
				unset($associations[$associationid]);
			}
		}
		
		$requests = DataObject::get('PermissionRequest', "UserID = '".(int)$member->ID."' AND Status = 'New'");
		$requests_array = array();
		if ($requests) {
			foreach ($requests as $request) {
				if (isset($associations[$request->Association()->ID])) {
					unset($associations[$request->Association()->ID]);					
				}
				$requests_array[] = date('d.m.Y H:i', strtotime($request->Created)).' - '._t('Association.TYPE_'.strtoupper($request->Association()->Type),$request->Association()->Type).' - '.$request->Association()->Name;
			}
		}
		
		$newAssociations = DataObject::get('Association', "CreatorID = '" . (int)$member->ID . "' AND Status = 'New'");
		$newAssociations_array = array();
		if ($newAssociations) {
			foreach ($newAssociations as $newAssociation) {
				if (isset($associations[$newAssociation->ID])) {
					unset($associations[$newAssociation->ID]);					
				}
				$newAssociations_array[] = date('d.m.Y H:i', strtotime($newAssociation->Created)).' - '._t('Association.TYPE_'.strtoupper($newAssociation->Type),$newAssociation->Type).' - '.$newAssociation->Name;
			}
		}
		
		$types = singleton('AssociationPermission')->dbObject('Type')->enumValues();
		foreach ($types as $key => $value) {
			$types[$key] = _t('AssociationPermission.TYPE_'.strtoupper($key), $value);
		}
		
		if (count($requests_array)) {
			$toExistingTab->push($associationField = new LabelField('requestsWaiting', _t('PermissionRequest.REQUESTSWAITING', 'Waiting for confirmation').':', null, true));
			$toExistingTab->push(new LiteralField('requestsWaitingList', '<br /><span class="requestsWaitingItems">'.implode('<br/>',$requests_array).'</span><br /><br />'));
		}
		
		if (count($newAssociations_array)) {
			$toExistingTab->push($newAssociationField = new LabelField('associationsWaiting', _t('PermissionRequest.ASSOCIATIONSWAITING', 'Associations waiting for confirmation').':', null, true));
			$toExistingTab->push(new LiteralField('associationsWaitingList', '<br /><span class="associationsWaitingItems">'.implode('<br/>',$newAssociations_array).'</span><br /><br />'));
		}
		
		$toExistingTab->push( 
			$associationField = new AdvancedDropdownField(
				'AssociationID', 
				_t('Association.SINGULARNAME', 'Association'). '<span class="tipsy-hint" title="' . _t('PermissionRequest.HINT_ASSOCIATION', 'For which association, company or organization do you need permission.') . '"></span>',
				$associations
			)		
		);
		
		$toExistingTab->push( 
			$newAssociationField = new CheckBoxField(
				'newAssociation', 
				_t('PermissionRequest.REGISTERNEWASSOCIATION', 'I didnt find the association, I want to register a new association.').'<br /><br />'					
			)		
		);
		
		/*	
		$toExistingTab->push( 
			new OptionsetField(
				'PermissionType', 
				_t('AssociationPermission.TYPE', 'Type'). '<span class="tipsy-hint" title="' . _t('PermissionRequest.HINT_TYPE', 'What role do you need, Organizer is just able to publish events when a Moderator can also add and edit useraccounts.') . '"></span>', 
				$types, 'Organizer'
			)		 					
		);*/
		
		$toExistingTab->push( 
			new LabelField(
				'help', 
				_t('PermissionRequest.EXPLANATIONTEXT', 'When you click "Save" this application will be sent to the system moderator and you will recieve a message when it has been checked.'),
				true,
				true								
			)		 					
		);
		
		return $fields;
	}
	
	function getValidator() {  
		return null;
	}
	
	public function validate() {	
		$data = Convert::raw2sql($_POST);
				
		if (!empty($data['newAssociation'])) {		
			return Association::validate();					
		}
		
		$requiredFields = array(
			'AssociationID' => 'Association.SINGULARNAME',
			//'PermissionType' => 'AssociationPermission.TYPE',
		);
		foreach ($requiredFields as $field => $value) {
			if ( empty($data[$field]) && $this->$field == '' ) {
				return new ValidationResult(false, sprintf(_t('DialogDataObjectManager.FILLOUT', 'Please fill out %s'), _t($value, $value)));
			}
		}
		
		if (!empty($data['AssociationID'])) {
			$permission = DataObject::get_one('AssociationPermission', "AssociationID = '".$data['AssociationID'] ."' AND AssociationOrganizerID = '".Member::currentUserID()."' AND Type = 'Organizer'");
			if ($permission) {
				return new ValidationResult(false, _t('PermissionRequest.PERMISSIONEXISTS', 'You already have this permission!'));
			}
		}
		return new ValidationResult(true, 'SUCCESS');
	}
	
	public function onBeforeWrite() {
		$data = Convert::raw2sql($_POST);
		if (!empty($data['newAssociation'])) {			
			parent::onBeforeWrite();  // is must " PermissionRequest has a broken onBeforeWrite() function. "
			return true;// EXITING, dont want to do anything with PermissionRequest
		}
				
		parent::onBeforeWrite();		
		
		$member = Member::currentUser();
		
		$this->UserID = $member->ID;
		$this->PermissionType = 'Organizer';
		
		if ($this->ID) {
			if (!$this->AcceptLinkID)
				$this->AcceptLinkID = SecureLinkRequest::generate_link_object('PermissionRequest', $this->ID, 'accept', date('Y-m-d', strtotime('+1 month')))->ID;
			if (!$this->RejectLinkID)
				$this->RejectLinkID = SecureLinkRequest::generate_link_object('PermissionRequest', $this->ID, 'reject', date('Y-m-d', strtotime('+1 month')))->ID;			
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
	
	public function write() {
		$data = Convert::raw2sql($_POST);
		if (!empty($data['newAssociation'])) {					
			parent::write();
		} else {
			parent::write();
		}
	}
	
	public function onAfterWrite() {		
		parent::onAfterWrite();
		
		$data = Convert::raw2sql($_POST);
		$writeCount = (int)Session::get('PermissionRequest_onAfterWriteCount' . $this->ID);
		$writeCount++;
		Session::set('PermissionRequest_onAfterWriteCount' . $this->ID, $writeCount);
		
		if ($writeCount != 2) {
			return true;
		}
		
		$member = Member::currentUser();
		
		if (!empty($data['newAssociation'])) {		
			$this->delete(); // Dont want to have a PermissionRequest in this case
			$association = new Association();
			$fields = $association->database_fields('Association');
		
			foreach ($fields as $field => $dbtype) {
				if (isset($data[$field])) {
					$association->$field = $data[$field];
				}
			}
						
			foreach (Translatable::get_allowed_locales() as $locale) {
				$association->setField('Name_' . $locale, $data['Name_'.$locale]);
			}
			$association->CreatorID = Member::currentUserID();
			$association->Status = 'New';
			if (in_array($association->MunicipalID, PermissionExtension::getMyMunicipals($member)))
				$association->Status = 'Active';
						
			if(isset($data['LogoID'])) {
				if($file = DataObject::get_by_id("File", (int) $data['LogoID'])) {
					$file->ClassName = 'AssociationLogo';
					$file->write();

					$association->LogoID = $file->ID;
				}
			}		
		
			$association->write(); // Will create ID
			$association->write(); // Will create accept/reject links and send messages
			
			return true;
		}		
			
		
		if (empty($data['AssociationID'])) {
			return true;
		}				
		
		
		$originalLocale = i18n::get_locale();
			
		if (is_numeric($data['AssociationID'])) {
			$association = DataObject::get_by_id('Association', $data['AssociationID']);			
		}
		if ($association) {	
			eCalendarExtension::SendMemberSelfNewRequestMessage($member, $association);
			
			$moderators = eCalendarExtension::FindClosestMembers($association, 'parent', 'Moderator', array($member->ID));		
			$permissiontype = 'Organizer';
			
			if ($moderators) {
			
				foreach ($moderators as $moderator) {					
					$this->sendPermissionRequest($moderator, $member, $association, $permissiontype);			
				}
				
				if ($moderators->Count() == 0) {
					$mun_moderators = $association->Municipal()->AssociationOrganizers();
					if ($mun_moderators->Count()) {
						foreach ($mun_moderators as $moderator) {
							$this->sendPermissionRequest($moderator, $member, $association, $permissiontype);	
						}
					}
					else {
						// Send to normal admins
						$admins = eCalendarExtension::FindAdministrators();
						if ($admins) {
							foreach ($admins as $admin) {
								$this->sendPermissionRequest($admin, $member, $association, $permissiontype);	
							}
						}
					}
				}
			}
		}

		i18n::set_locale($originalLocale);
		
	}
	
	public function sendPermissionRequest(&$moderator, &$member, &$association, $permissiontype) {
		$this->ignoreCanEdit = true;
		$currentLocale = $moderator->Locale; 
		i18n::set_locale($currentLocale);							

		$subject = _t('PermissionRequest.CREATEDNOTICE_SUBJECT', 'Permission request');
		$body = sprintf( 
				_t('PermissionRequest.CREATEDNOTICE_BODY1', 'User "%s" want to be a "%s" in association "%s".'),
				$member->FullName.' ( '.$member->Email.' )',
				_t('AssociationPermission.TYPE_'.strtoupper( $permissiontype ), $permissiontype),
				$association->Name
		)."\n\n";

		$body .= sprintf(_t('PermissionRequest.CREATEDNOTICE_BODY2', 'Click [url=%s]here[/url] to accept it or [url=%s]here[/url] to reject it.'), $this->AcceptLink(), $this->RejectLink());
		$msg = new IM_Message();	
		$msg->Subject = $subject;
		$msg->Body = $body;								
		$msg->ToID = $moderator->ID;
		$msg->send(false);		
		
		$this->ignoreCanEdit = false;
	}
}
?>
