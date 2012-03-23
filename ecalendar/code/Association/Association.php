<?php

class Association extends DataObject {
  
	static $extensions = array(
	    'TranslatableDataObject',
	    'PermissionExtension',
		'CreaDataObjectExtension',
	  	'Hierarchy',
	);
  
	static $db = array(
		'Name'  => 'Varchar(250)',
		'Type' => 'Enum("Association,Company,Other", "Association")',
		'Status' => 'Enum("Active,New,Passive", "Active")',
		'PostalAddress' => 'Varchar', 
		'PostalCode' => 'Int',
		'PostalOffice' => 'Varchar',		
		'Phone' => 'Varchar(128)',
		'Email' => 'Varchar(128)',
		'Homepage' => 'Varchar(200)',
		'Note' => 'Text',
		'AcceptLinkID' => 'Int',
		'RejectLinkID' => 'Int',
		'AllowFeedPosts' => 'Boolean',
		'FeedIdentifier' => 'Varchar(128)'
	);
  
	static $has_one = array(
		'Parent' => 'Association',
		'Municipal' => 'Municipal',
		'Logo' => 'AssociationLogo',
		'Creator' => 'Member'
	);
  
	static $has_many = array(
		'Events'	=> 'Event',		
		'Children' => 'Association',
		'AssociationPermissions' => 'AssociationPermission', 
		'PermissionRequests' => 'PermissionRequest',
		'UserInviteRequests' => 'UserInviteRequest'
	);
 
	static $searchable_fields = array(
		'PostalAddress',
	);
  
	static $translatableFields = array(
		'Name',
	);  

	static $default_sort = 'Name ASC';
 	 
	static $defaults = array(
		'Type'	=> 'Association',
		'Status' => 'Active',
		'AllowFeedPosts' => false
	);
	
	static $sortfield_override = array(
		'CreatedNice' => 'Created',
		'LastEditedNice' => 'LastEdited'
	);
	
	public function getRequirementsForPopup() {		
		$this->extend('getRequirementsForPopup');
		Requirements::css('ecalendar/css/AssociationDialog.css');
		Requirements::javascript('thirdparty/tipsy-0.1.7/src/javascripts/jquery.tipsy.js');
		Requirements::css('thirdparty/tipsy-0.1.7/src/stylesheets/tipsy.css');
		Requirements::customScript('jQuery(function() { jQuery(".tipsy-hint").tipsy({fade: true, gravity: "w", html: true }); });');			
		Requirements::customScript('jQuery(function() { 
			jQuery("input[name=AllowFeedPosts]").change(function() { 
				if (jQuery(this).is(":checked")) 
					jQuery(this).parent().next().show(); 
				else 
					jQuery(this).parent().next().hide(); 
			}).trigger("change");
		});');
		
		if ($this->ID && $this->canChangeStatus()) {
			if ($this->Status == 'New')
				Requirements::customScript('top.setVisibleStatusButtons("[accept],[reject],[save]"); ');
			else if ($this->Status == 'Active')
				Requirements::customScript('top.setVisibleStatusButtons("[deactivate],[save]"); ');
			else if ($this->Status == 'Passive')
				Requirements::customScript('top.setVisibleStatusButtons("[activate],[save]"); ');
		}
	}	
	
	public function getChildren() {
		if ($this->ID > 0) {
			$children = DataObject::get('Association', "Association.ParentID = '".$this->ID."'");
			if ($children) {
				return $children;
			}
		} 
		return new DataObjectSet();
	}
	
	public function onBeforeWrite() {		
		parent::onBeforeWrite();
		
		$member = Member::CurrentUser();
		if ($member && $this->ID == 0) {
			$this->CreatorID = $member->ID;
		}
		
		// Homepage fix
		if (!empty($this->Homepage)) {
			if (!preg_match("/^(http|https)/i", $this->Homepage)) {
				$this->Homepage = 'http://' . $this->Homepage;
			}
		}
		
		if ( $this->isChanged('Status', 2) ) {
			$changedFields = $this->getChangedFields(true, 2);
			if ($changedFields['Status']['before'] != 'Active' && $changedFields['Status']['after'] == 'Active' ) {
				// Publish all events for the association WHERE connected user has publish permission
				$unpublishedEvents = $this->Events("Status = 'Preliminary'");
				if ($unpublishedEvents) {				
					foreach ($unpublishedEvents as $event) {
						$organizer = $event->Organizer();
						if ($organizer) {
							if ($organizer->CanPublish()) { // Have to check the user
								try {
									$event->Status = 'Accepted';
									$event->write();
								}
								catch (Exception $e) {

								}	
							}
						}
					}
				}
			}
		}
		
		// Create accept/reject links
		if ($this->ID) {
			if (!$this->AcceptLinkID)
				$this->AcceptLinkID = SecureLinkRequest::generate_link_object('HandleNewAssociation', $this->ID, 'accept', date('Y-m-d', strtotime('+1 month')))->ID;
			if (!$this->RejectLinkID)
				$this->RejectLinkID = SecureLinkRequest::generate_link_object('HandleNewAssociation', $this->ID, 'reject', date('Y-m-d', strtotime('+1 month')))->ID;			
		}
	}
	
	public function onAfterWrite() {
		parent::onAfterWrite();
		
		if (!$this->AcceptLinkID || !$this->RejectLinkID)
			return;
		
		$data = Convert::raw2sql($_POST);	
		$permissions = DataObject::get('AssociationPermission', "AssociationPermission.AssociationOrganizerID = '".$this->CreatorID."' AND AssociationPermission.AssociationID = '".$this->ID."'");
		if (!$permissions && $this->CreatorID > 0) { // old bug that no Creator when made from reg page
			$creator = DataObject::get_by_id('AssociationOrganizer', $this->CreatorID);
			
			// Event admins shound not be given permissions, they can modify it anyway
			if ($creator && !$creator->inGroup('eventadmins')) {
				$permission = new AssociationPermission();
				$permission->AssociationID = $this->ID;
				$permission->AssociationOrganizerID = $this->CreatorID;
				$permission->Type = 'Moderator';
				$permission->write();	
			}		
			
			// Send message to Association Moderator or Municipal moderators
			// NOTE! Already sending same kind of message when making account from RegistrationPage
			if ($this->Status == 'New' && eCalendarExtension::IsBackWebRequest()) {
												
				$organizer = DataObject::get_by_id('AssociationOrganizer', $this->CreatorID);
				$association = DataObject::get_by_id('Association', $this->ID);
				$sent = false;
			
				// Send message to self that we will be informed when the association is accepted or rejected
				$this->SendNewAssociationNotificationToSelf($organizer, $association);
				
				// Send to closest moderator of parent
				$moderators = eCalendarExtension::FindClosestMembers($association, 'parent', 'Moderator', array($this->CreatorID));
				if ($moderators) {
					foreach ($moderators as $moderator) {
						$this->SendNewAssociationNotification($moderator, $organizer, $association);
						$sent = true;
					}
				}					
				
				if ($sent == false) { // Send to Municipal moderator instead
					$moderators = $this->Municipal()->AssociationOrganizers();
					if ($moderators) {
						foreach ($moderators as $moderator) {				
							$this->SendNewAssociationNotification($moderator, $organizer, $association);
							$sent = true;
						}
					}
				}
				
				if ($sent == false) {
					$admins = eCalendarExtension::FindAdministrators();
					if ($admins) {
						foreach ($admins as $admin) {
							$this->SendNewAssociationNotification($admin, $organizer, $association);
							$sent = true;
						}
					}
				}
			}
		}
		
		if ($this->isChanged('Status', 2)) {
			$changedFields = $this->getChangedFields(true, 2);
			if ($changedFields['Status']['before'] == 'New' && $changedFields['Status']['after'] == 'Active') {
				$permissions = $this->AssociationPermissions();
				if ($permissions) {
					foreach ($permissions as $permission) {
						$this->SendAcceptedNotification($permission->AssociationOrganizer());
					}
				}				
			}
			else if ($changedFields['Status']['before'] == 'New' && $changedFields['Status']['after'] == 'Passive') {
				$permissions = $this->AssociationPermissions();
				if ($permissions) {
					foreach ($permissions as $permission) {
						$this->SendRejectedNotification($permission->AssociationOrganizer());
					}
				}								
			}
		}
	}
	
	protected function AcceptLink() {
		$linkRequest = DataObject::get_by_id('SecureLinkRequest', (int)$this->AcceptLinkID);
		if ($linkRequest)
			return $linkRequest->Link();
		return '';
	}
	
	protected function RejectLink() {
		$linkRequest = DataObject::get_by_id('SecureLinkRequest', (int)$this->RejectLinkID);
		if ($linkRequest)
			return $linkRequest->Link();
		return '';
	}	
	
	public function SendNewAssociationNotificationToSelf($organizer, $association) {		
		$subject = _t('NotificationTask.SELFNEWASSOCIATIONMSG_SUBJECT', 'New association created');
		$body = sprintf(
					_t('NotificationTask.SELFNEWASSOCIATIONMSG_BODY1', 'You have created a new association, %s %s.'), 
						strtolower(_t('Association.TYPE_'.strtoupper($association->Type), $association->Type)), 
						$association->Name) . "\n\n";
		$body .= _t('NotificationTask.SELFNEWASSOCIATIONMSG_BODY2', 'You will recieve a message when the request is accepted.'). "\n";		
		
		$msg = new IM_Message();
		$msg->ToID = $organizer->ID;
		$msg->FromID = 0;
		$msg->Subject = $subject;
		$msg->Body = $body;
		$msg->send(false);		
	}
	
	public function SendNewAssociationNotification(&$moderator, &$organizer, &$association) {
		$originalLocale = i18n::get_locale();
		$currentLocale = $moderator->Locale; 
		i18n::set_locale($currentLocale);			
		$subject = _t('NotificationTask.NEWASSOCIATIONNOTICEM_SUBJECT', 'New association created');
		$body = sprintf(_t('NotificationTask.NEWASSOCIATIONNOTICEM_BODY1', 'User %s have created a new association.'), $organizer->FullName.' ( '.$organizer->Email.' )')."\n\n";
		
		$body .= "[b]" . _t('Association.SINGULARNAME', 'Association')."[/b]\n";
		$body .= _t('Association.TYPE', 'Type') . ': ' . _t('Association.TYPE_'.strtoupper( $association->Type ), $association->Type)."\n";
		$body .= _t('Association.NAME', 'Name') . ': ' . $association->Name . "\n";
		$body .= _t('Municipal.SINGULARNAME', 'Municipality') . ': ' . $association->Municipal()->Name . "\n";
		$body .= _t('Event.ADDRESS', 'Address') . ': ' . $association->PostalAddress . ", " . $association->PostalCode . ", " . $association->PostalOffice . "\n";
		$body .= _t('Association.PHONE', 'Phone') . ': ' . $association->Phone . "\n";
		$body .= _t('Association.EMAIL', 'Email') . ': ' . $association->Email . "\n";
		$body .= _t('Association.HOMEPAGE', 'Homepage') . ': ' . $association->Homepage . "\n";
		$body .= _t('Association.LOGO', 'Logo') . ": \n";
		if ($association->LogoID) {
			$body .= "[img]" . $association->Logo()->PaddedImage(142, 80)->AbsoluteLink() . "[/img]\n";
		}
				
		$body .= "\n" . sprintf(_t('NotificationTask.NEWASSOCIATIONNOTICEM_BODY2', 'Click [url=%s]here[/url] to accept or [url=%s]here[/url] to reject.'), $this->AcceptLink(), $this->RejectLink()). " \n";
		
		$msg = new IM_Message();
		$msg->ToID = $moderator->ID;
		$msg->FromID = 0;
		$msg->Subject = $subject;
		$msg->Body = $body;
		$msg->send(false);		
		i18n::set_locale($originalLocale);
	}
	
	public function SendAcceptedNotification($user) {
		$originalLocale = i18n::get_locale();
		$currentLocale = $user->Locale; 
		i18n::set_locale($currentLocale);
		
		$subject = _t('Association.ACCEPTEDASSOCIATION_SUBJECT', 'Association accepted');
		$body = sprintf(_t('Association.ACCEPTEDASSOCIATION_BODY1', 'Association "%s" has been accepted and can be used for events.'), $this->Name);
		
		$msg = new IM_Message();
		$msg->ToID = $user->ID;
		$msg->FromID = 0;
		$msg->Subject = $subject;
		$msg->Body = $body;
		$msg->send(false);			
		
		i18n::set_locale($originalLocale);
	}
	
	public function SendRejectedNotification($user) {
		$originalLocale = i18n::get_locale();
		$currentLocale = $user->Locale; 
		i18n::set_locale($currentLocale);
		
		$subject = _t('Association.REJECTEDASSOCIATION_SUBJECT', 'Association rejected');
		$body = sprintf(_t('Association.REJECTEDASSOCIATION_BODY1', 'Association "%s" has been rejected and cannot be used for events.'), $this->Name);
		
		$msg = new IM_Message();
		$msg->ToID = $user->ID;
		$msg->FromID = 0;
		$msg->Subject = $subject;
		$msg->Body = $body;
		$msg->send(false);		
		
		i18n::set_locale($originalLocale);		
	}	
	
	public function getCreatedNice() {
		return date('d.m.Y H:i', strtotime($this->Created));
	}
  
	public function getLastEditedNice() {
  		return date('d.m.Y H:i', strtotime($this->LastEdited));
  	}
  
	public function getDOMTitle() {
  		return mb_strtolower(_t('Association.SINGULARNAME', 'Association')).' `'.$this->Name.'´';
	}
	
	public function getNiceStatus() {
		return _t('Association.STATUS_' . strtoupper($this->Status), $this->Status);
	}
  
	static public function toDropdownList($includePassives = true) {
		$filter = '';
		if ($includePassives == false)
			$filter = 'Association.Status = \'Active\'';
			
		$association_objs = DataObject::get('Association', $filter);
		$associations = array('' => _t('AdvancedDropdownField.NONESELECTED', '(None selected)'));
		if ($association_objs)
			foreach ($association_objs as $association) {
				$associations[$association->ID] = $association->getNameHierachy(false);
			}
		return $associations;
  }
  
	public function getPath() {
		$dataObjects = $this->getAncestors();
  		$paths = array();
  		if ($dataObjects) {
  			$ancestors = $dataObjects->toArray();  			
	  		foreach ($ancestors as $association) {
	  			$paths[] = $association->Name;
	  		}
	  		return implode(' - ', $paths);
  		}
  		
  		return '';
	}
  	
	public function forTemplate() {
		if ( $this->Name && $this->ID ) {
			return $this->getNameHierachyAsHTML();	
		} elseif ( !$this->ID ) {
			return '<span class="noassociation">'._t('Association.MISSING', 'Association not chosen').'</span>';
		} else {	
			return '';
		}
	}
	
	/* 
	* Namn på förening med hela hierarkin inkluderad som extra
	*/
	public function getNameHierachy($html = true, $include_status = false) {
		$path = $this->getPath();
		if ( strlen($path) > 0 ) {
			if ($html) {
				$path = ' &laquo;' . $path;
			} else {
				$path = ' - ' . $path;
			}
			
		}
	  	
		$statusinfo = ($this->Status == 'New' && $include_status)?' *'._t('Association.STATUS_NEW', 'New'):'';
	  	if ($html) {
	  		return ''.$this->Name.' <span style="color: #B7B7B7">'.$path.'</span>';
	  	} else {
	  		return $this->Name.$statusinfo.' '.$path;
	  	}
	}
	
	public function getNameHierachyAsTextWithStatus() {
		return $this->getNameHierachy(false, true);
	}
	
	public function getNameHierachyAsText() {
		return $this->getNameHierachy(false);
	}
	
	public function getNameHierachyAsHTML() {
		return $this->getNameHierachy(true);
	}	
	
	public function getNiceType() {
		return _t('Association.TYPE_' . strtoupper($this->Type), $this->Type);
	}
	
	public function getName() {
		return $this->getField('Name_' . i18n::get_locale());
	}
	
	protected function canChangeStatus() {
		$canChangeStatus = false;
		
		if ( !eCalendarExtension::isAdmin() ) {	
			$member = Member::CurrentUser();			
			$where_only = "(
				Association.ID IN ('".implode("','", PermissionExtension::getMyAssociations($member, 'moderators', true))."')
				OR Association.CreatorID = '".$member->ID."'
			)";
			
			$and_where_only = "AND ".$where_only;
			
			if ( $member instanceof AssociationOrganizer ) {
				if ( $member->canPublish() && $this->Status != 'New') { 
					$canChangeStatus = true;
				}
				else if ($this->Status == 'New' && in_array($this->MunicipalID, PermissionExtension::getMyMunicipals($member))) {
					$canChangeStatus = true;
				}
			}
			
		}
		else {		
			$canChangeStatus = true;		
		}		
		
		return $canChangeStatus;
	}
	
	public function getStandardFields( $generalTabTitle = null, $status = 'New' ) {
		$municipalArray = Municipal::toDropdownList();
		
		$statusfield = new OptionsetField(
			'Status', 
			_t('Association.STATUS', 'Status'),
       		array(
				'New' => _t('Association.STATUS_NEW', 'New'),
				'Active' => _t('Association.STATUS_ACTIVE', 'Active'), 
       			'Passive' => _t('Association.STATUS_PASSIVE', 'Passive'),       			
       		), 'Active'
		 );
    
		
		$fields = new FieldSet(      	
			$DTSet = new DialogTabSet('TabSet')
		);
				
		$generalTab = new Tab('GeneralTab', $generalTabTitle?$generalTabTitle:_t('Association.GENERALTAB', 'General'));
				
		$generalTab->push( new OptionsetField('Type', _t('Association.TYPE', 'Type'), 
				array(
					'Association' => _t('Association.TYPE_ASSOCIATION', 'Association'), 
					'Company' => _t('Association.TYPE_COMPANY', 'Company'),
					'Other' => _t('Association.TYPE_OTHER', 'Other')
				), 'Association'
			)
		);
		
		if (eCalendarExtension::isAdmin()) {
			$generalTab->push(new FieldGroup(
				new CheckboxField('AllowFeedPosts', _t('Association.ALLOWFEEDPOSTS', 'Allow posting via feeds') . '<br/>'),
				new TextField('FeedIdentifier', _t('Association.FEEDIDENTIFIER', 'Feed identity'))
			));
		}
		
		$generalTab->push( new TextField('Name', _t('Association.NAME', 'Name of the association') . ' <em>*</em>' . '<span class="tipsy-hint" title="' . _t('Association.HINT_NAME', 'Name of association, you will automatically be a moderator in this association.') . '"></span>') );
		
		$generalTab->push( new AdvancedDropdownField(
				'MunicipalID',
				_t('Municipal.SINGULARNAME', 'Municipal') . ' <em>*</em>',
				$municipalArray
			)
		);	
		
		$generalTab->push( new TextField('PostalAddress', _t('Association.POSTALADDRESS', 'Address')) );
		$generalTab->push( new NumericField('PostalCode', _t('Association.POSTALCODE', 'Postal code')) );
		$generalTab->push( new TextField('PostalOffice', _t('Association.POSTALOFFICE', 'Postal office')) );
		$generalTab->push( new TextField('Phone', _t('Association.PHONE', 'Phone')) );
		$generalTab->push( new EmailField('Email', _t('Association.EMAIL', 'Email')) );
		$generalTab->push( new TextField('Homepage', _t('Association.HOMEPAGE', 'Homepage')) );
		if ($this->ID > 0) {
			$generalTab->push( new TextAreaField('Note', _t('Association.NOTE', 'Note') . '<span class="tipsy-hint" title="' . _t('Association.HINT_NOTE', 'Only for internal use.') . '"></span>' ) );
		}
		$DTSet->push( $generalTab );
		
		$logoTab = new Tab(
			'LogoTab', 
			_t('Association.LOGOTAB', 'Logo'),
			$logoUploader = new ImageUploadField('Logo', '')
		);				
		
		$logoUploader->setUploadFolder('logos');
		$logoUploader->setVar('image_class', 'AssociationLogo');
		$logoUploader->removeImporting();
		$logoUploader->removeFolderSelection();
		$logoUploader->setBackend(false);
		$DTSet->push( $logoTab );
		
		//$member = Member::currentUser();
		//$myassociations = $this->getMyAssociations($member, 'moderators', true);
		// Blir oerhört jobbigt att lista ut bara de föreningar man har rätt till plus de som redan är tilldelade
		// AND (Association.ID IN ('".implode("'", $myassociations)."') OR Association.ID = AssociationPermission.AssociationID)";
		$and_where_only = ""; 
				
		if ($this->ID && $this->canChangeStatus())
			$fields->insertAfter($statusfield, 'Type');
		
		// Endast för admin för tillfället.
		if ($this->ID > 0 && $this->Status != 'New' && eCalendarExtension::IsAdmin() )  {			
			$groupTab = new Tab('SubAssociationsTab', _t('Association.SUBASSOCIATIONSTAB', 'Sub associations'),
	        $subAssociationDOM = new DialogHasManyDataObjectManager(
	            $this, 
	            'Children', 
	            'Association', 
	            array(
					'NiceType'	=> _t('Association.TYPE', 'Type'),
					'NameHierachyAsHTML' => '',
	            ),
	            null
				, 
				"Association.ID != {$this->ID} 
				 AND (Association.ID NOT IN (SELECT ParentID FROM Association) OR ParentID = {$this->ID})
				 $and_where_only
				"				
				)
			); // Borde inte gå att plocka en förening som har någon underförening, dvs. åtminstone ett sätt att begränsa oändlig loop.. om än lite begränsande
			
			if (eCalendarExtension::isAdmin()) {
				$subAssociationDOM->allowRelationOverride = true;
			}
			
			$subAssociationDOM->setStatusMode(true);
			$subAssociationDOM->removePermission(('add'));
								
			$DTSet->push( $groupTab );	
				
		} else {	
			$associations = DataObject::get('Association',  "Association.ID != {$this->ID} $and_where_only");
			if ($associations) {				
				$parentAssociationField= new AdvancedDropdownField(
					'ParentID',
					_t('Association.PARENTASSOCIATION', 'Parent association') . '<span class="tipsy-hint" title="' . _t('Association.HINT_PARENTASSOCIATION', 'If the new association is a a part of a larger association, you can select the head association from this list.') . '"></span>',
					$associations->map('ID', 'NameHierachyAsText', _t('AdvancedDropdownField.NONESELECTED', '(None selected)'))
				);
				
				$fields->insertBefore($parentAssociationField, 'Name');				
			}
		}		
		
		return $fields;
	}
	
	public function getCMSFields( ) {
				
		$fields = $this->getStandardFields( null, $this->Status );			
		$DTSet = $fields->fieldByName('TabSet');
		
		$where_only_my = "AssociationPermission.AssociationID = '".$this->ID."'";	
		$organizerTab = new Tab('UsersTab', _t('Association.ASSOCIATIONUSERSTAB', 'Users'),
			$dbmngr = new DialogHasManyDataObjectManager(
				$this, 
				'AssociationPermissions', 
				'AssociationPermission', 
				array(
					'NiceType' => _t('AssociationPermission.TYPE', 'Permissiontype'),
					'AssociationOrganizer.Title' => _t('AssociationOrganizer.NAME', 'Name'),
					'PermissionPublishIcon' => _t('AssociationOrganizer.PERMISSIONPUBLISH', 'Can publish'),
				),
				null,
				$where_only_my,
				null,
				'LEFT JOIN AssociationOrganizer ON AssociationPermission.AssociationOrganizerID = AssociationOrganizer.ID'		            
			)
		);
		
		$dbmngr->setMarkingPermission(false);
		$dbmngr->setPluralTitle(_t('Association.ASSOCIATIONUSERS', 'Users in association'));
		
		if ( $this->ID > 0 ) {
			$DTSet->push(	$organizerTab );
		}
						
		$this->extend('updateCMSFields', $fields);
	    
		return $fields;
	}  
  
	protected function onBeforeDelete() {
		parent::onBeforeDelete();
			
		// delete AssociationPermission associated with this Association
		if ($this->AssociationPermissions()) {
			foreach ($this->AssociationPermissions() as $associationPermission) {
				$associationPermission->delete();
			}
		}
		
		// delete PermissionRequests
		if ($this->PermissionRequests()) {
			foreach ($this->PermissionRequests() as $permissionRequest) {
				$permissionRequest->delete();
			}
		}
		
		// delete UserInviteRequests
		if ($this->UserInviteRequests()) {
			foreach ($this->UserInviteRequests() as $inviteRequest) {
				$inviteRequest->delete();
			}
		}			
		
		// delete logo
		if ($this->Logo() && $this->Logo()->exists())
			$this->Logo()->delete();
	}
	
	function getValidator() {  
		return null;
	}
	
	function getFullAddress() {
		$result = array();
		if (!empty($this->PostalAddress))
			$result[] = $this->PostalAddress;
		if (!empty($this->PostalCode))
			$result[] = $this->PostalCode;
		if (!empty($this->PostalOffice))
			$result[] = $this->PostalOffice;
		
		return implode(', ', $result);
	}
	
	function getDataExportInfo() {
		$result = array();
		
		$phone = trim($this->Phone);
		$homepage = trim($this->Homepage);
		$email = trim($this->Email);
		
		if (strlen($phone))
			$result[] = $phone;
		if (strlen($email))
			$result[] = $email;		
		if (strlen($homepage)) {
			if (substr($homepage, strlen($homepage)-1) == '/')
				$homepage = substr($homepage, 0, strlen($homepage)-1);
			$homepage = str_replace('http://', '', $homepage);
			$result[] = $homepage;
		}
		
		return implode(', ', $result);
	}
	
	function validate() {
		$data = Convert::raw2sql($_POST);
		
		$requiredFields = array(
				'Name_'.Translatable::default_locale() => 'Association.NAME',
				'MunicipalID' => 'Municipal.SINGULARNAME',
		);
		
		foreach ($requiredFields as $key => $value) {
			if (isset($data[$key]) && empty($data[$key]) ) {
				return new ValidationResult(false, sprintf(_t('DialogDataObjectManager.FILLOUT', 'Please fill out %s'), _t($value, $value)));
			}
		}		
		
		if (eCalendarExtension::isBackWebRequest() && !eCalendarExtension::isAdmin()) {
			# Check permission so not a association not having permission in! but only for hacking..
			$condition1 = !empty($data['Parent']['0'])?$data['Parent'][0]>0:0;
			$condition2 = $this->ID == 0;
			$condition3 = eCalendarExtension::IsMunicipalModerator();
					
			/*if (!$condition1 && $condition2 && !$condition3) {
				return new ValidationResult(false, sprintf(_t('DialogDataObjectManager.FILLOUT', 'Please fill out %s'), _t('Association.SINGULARNAME')));
			}*/
			
			// Kontrollerar så man inte sätter sig som underförening till en förening man inte har rätt till, dock ej så allvarligt kanske..
			$myassociations = PermissionExtension::getMyAssociations(null, 'organizers', true);
			if ($condition1 && $condition2 && $condition3) {
				if (!in_array($data['Parent']['0'], $myassociations)) {
					return new ValidationResult(false, sprintf(_t('eCalendarAdmin.ERROR_PERMISSION', 'Not allowed to set this value for %s'), _t('Associatian.SINGULARNAME', 'Association')));
				}
			}
			
			$member = Member::CurrentUser();		
			if ( $member instanceof AssociationOrganizer && !empty($data['Status'])) {
				if ( !$member->canPublish() || ($this->Status == 'New' && $data['Status'] == 'Active') ) { 
					return new ValidationResult(false, sprintf(_t('eCalendarAdmin.ERROR_PERMISSION', 'Not allowed to set this value for %s'), _t('Associatian.STATUS', 'Status')));
				}
			}
		}
		
		return parent::validate();
	}		
	
	static public function getIDFromEmail($email, $extraWhere = '') {
		$association = DataObject::get_one('Association', "Email = '" . $email . "' " . $extraWhere);
		if ($association) 
			return $association->ID;

		return 0;
	}
	
	static public function getIDFromFeedIdentifier($feedIdentifier, $extraWhere = '') {
		$association = DataObject::get_one('Association', "(FeedIdentifier = '" . $feedIdentifier . "' AND AllowFeedPosts = 1) " . $extraWhere);
		if ($association) 
			return $association->ID;

		return 0;
	}	
}

?>
