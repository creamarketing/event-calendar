<?php 
class AssociationPermission extends DataObject {
	static $db = array(
		'Type' => 'Enum("Organizer,Moderator", "Organizer")'
	);
	
	static $extensions = array(	   
	    'PermissionExtension',
		'eCalendarExtension',
		'TemporaryDataObjectOwner'
	);
 
	static $has_one = array(
		'AssociationOrganizer' => 'AssociationOrganizer',
		'Association' => 'Association',
	);
	
	static $defaults = array(
		'Type' => 'Organizer'
	);
	
	public function IsEditableByUser() {
		$extendedEdit = $this->extendedCan('canEdit', Member::CurrentUser());
		$normalEdit = $this->canEdit(Member::CurrentUser());
				
		if ($extendedEdit || ($normalEdit === null && $normal))
			return true;
		return false;
	}
			
	public function getNiceType() {
		return _t('AssociationPermission.TYPE_' . strtoupper($this->Type), $this->Type);		
	}
	
	public function getPermissionPublishIcon() {
		$organizer = $this->AssociationOrganizer();
		if ($this->Type == 'Organizer' || $this->Type == 'Moderator') {
			return $organizer->getPermissionPublishIcon();
		} else {
			$ico_url = 'ecalendar/images/user-comment-red.gif';	
			$img_alt = 'NO';		
			$html = "<img src=\"$ico_url\" border=\"0\" alt=\"$img_alt\">";
			return $html;
		}
	}

	public function getCMSFields() {
		$associations = array();
		$types = $this->dbObject('Type')->enumValues();
		foreach ($types as $key => $value) {
			$types[$key] = _t('AssociationPermission.TYPE_'.strtoupper($key), $value);
		}
		
		$where_only_my = '';
		if (!eCalendarExtension::isAdmin()) {	
			$member = Member::CurrentUser();			
			$myassociations = $this->getMyAssociations($member, 'moderators', true);
			$where_only_my = "(
				AssociationPermission.AssociationID IN ('".implode("','", $myassociations)."')
				OR AssociationOrganizer.CreatorID = '".$member->ID."'
			)";
			
			$dbAssocs = DataObject::get('Association', "Association.ID IN ('".implode("','", $myassociations)."')");
			if ($dbAssocs) {
				$associations = $dbAssocs->map();
			}

		} else {
			$associations = Association::toDropdownList();
		}
		
		$existingUserModPermissions = DataObject::get(
			'AssociationPermission', 
			"AssociationPermission.AssociationOrganizerID = '".$this->AssociationOrganizerID."' AND AssociationPermission.Type = 'Moderator'"			
		);
		
		if ( !$this->AssociationID && $existingUserModPermissions) { // Only when adding not when edit
			foreach ( $existingUserModPermissions as $existingUserModPermission ) {
				if ( isset( $associations[$existingUserModPermission->AssociationID]) ) {
					unset( $associations[$existingUserModPermission->AssociationID] );
				}
			}
		}
		
		$associationOrganizerField = new DialogHasOneDataObjectManager(
			$this, 
			'AssociationOrganizer', 
	       	'AssociationOrganizer', 
	      	array(		             	
	             	'Title' => _t('AssociationOrganizer.NAME', 'Name'),
					'PermissionPublishIcon' => _t('AssociationOrganizer.PERMISSIONPUBLISH', 'Can publish'),
	            	'Association' => _t('Association.SINGULARNAME', 'Association'),
	            ),
			null,
			$where_only_my,
			null,
			'LEFT JOIN AssociationPermission ON AssociationOrganizer.ID = AssociationPermission.AssociationOrganizerID'
		);	  
					
		$fields = new FieldSet(      	
			$DTSet = new DialogTabSet('TabSet',		
		      	$generalTab = new Tab(
		      		'GeneralTab', 
		      		_t('AssociationPermission.GENERALTAB', 'General'),		      				
		      		new OptionsetField(
		      			'Type', 
		      			_t('AssociationPermission.TYPE', 'Type'), 
			       		$types, 'Organizer'
			       	)		              
				)
			)
		);
		
		$record = $this->record;
		$params = Director::urlParams();
		
		if ( !isset($record['AssociationID']) 
			|| $params['Action'] == 'EditOrganizersForm'
			|| $params['Action'] == 'EditOrganizersForm_NotConfirmed' ) {
			
			$generalTab->push( 
				$associationField = new AdvancedDropdownField(
					'AssociationID', 
					_t('Association.SINGULARNAME', 'Association'),
					$associations
				)
			);		
		}
	
		if ( !isset($record['AssociationOrganizerID']) 
			|| $params['Action'] == 'EditAssociationsForm'
			|| $params['Action'] == 'EditAssociationsForm_New' ) {
			$generalTab->push( $associationOrganizerField );	
		}
		
		$this->extend('updateCMSFields', $fields);
	    
		return $fields;
	}
	
	public function validate() {
		$data = Convert::raw2sql($_POST);
		if ($this->isBackWebRequest() && !eCalendarExtension::isAdmin()) {
			$myassociations = $this->getMyAssociations(null, 'moderators', true);
			if (!$this->AssociationID && !empty($data['AssociationID']) ) {
				$association = DataObject::get_by_id('Association', $data['AssociationID']);	
				$member = Member::currentUser();
				if ( !in_array($data['AssociationID'], $myassociations) && $association->CreatorID != $member->ID) {
					return new ValidationResult(false, sprintf(_t('eCalendarAdmin.ERROR_PERMISSION', 'Not allowed to set this value for %s'), _t('Association.SINGULARNAME', 'Association')));
				}
			}
		}
		
		if (!$this->AssociationID && isset($data['AssociationID']) && empty($data['AssociationID'])) {
			return new ValidationResult(false, sprintf(_t('eCalendarAdmin.ERROR_PERMISSION', 'Not allowed to set this value for %s'), _t('Association.SINGULARNAME', 'Association')));
		}
		
		if (!$this->Type && (!isset($data['Type']) || !strlen($data['Type']))) {
			return new ValidationResult(false, sprintf(_t('DialogDataObjectManager.FILLOUT', 'Please fill out %s'), _t('AssociationPermission.TYPE', 'Type')));
		}		
				
		return parent::validate();
	}
	
	public function getValidator() {
		return null;
	}
	
	public function onAfterWrite() {
		// SORT BY CREATED ASC!
		// Deleting multiple permissions for same user in same organisation
		$samepermissions = DataObject::get(
				'AssociationPermission', 
				"AssociationPermission.AssociationID = '".$this->AssociationID."' AND AssociationPermission.AssociationOrganizerID = '".$this->AssociationOrganizerID."'", 
				'Created ASC'
		);
		if ( $samepermissions->Count() > 1 ) {
			foreach ($samepermissions as $permission ) {
				$permission->delete(); // Only deleting max one "dublett" per run.
				break;				
			}
		}

		parent::onAfterWrite();	
	}
	
	public function onLogCreate($logItem) {
		$logItem->AddChangedField('Type', '', $this->Type, 'AssociationPermission.TYPE');
		$logItem->AddChangedField('AssociationOrganizerID', '', $this->AssociationOrganizerID);
		$logItem->AddChangedField('AssociationOrganizer', '', $this->AssociationOrganizer()->FullName, 'AssociationOrganizer.SINGULARNAME');
		$logItem->AddChangedField('AssociationID', '', $this->AssociationID);
		$logItem->AddChangedField('Association', '', $this->Association()->Name, 'Association.SINGULARNAME');
	}
	
	public function onLogEdit($logItem) {
		$hasRealChanges = false;
		
		$changedFields = $this->getChangedFields(true, 2);
		if (isset($changedFields['Type'])) {
			$logItem->AddChangedField('Type', $changedFields['Type']['before'], $changedFields['Type']['after'], 'AssociationPermission.TYPE');
			$hasRealChanges = true;
		}
		if (isset($changedFields['AssociationOrganizerID'])) {
			$logItem->AddChangedField('AssociationOrganizerID', $changedFields['AssociationOrganizerID']['before'], $changedFields['AssociationOrganizerID']['after']);
			
			$beforeObject = DataObject::get_by_id('AssociationOrganizer', (int)$changedFields['AssociationOrganizerID']['before']);
			$afterObject = DataObject::get_by_id('AssociationOrganizer', (int)$changedFields['AssociationOrganizerID']['after']);
			$logItem->AddChangedField('AssociationOrganizer', ($beforeObject ? $beforeObject->FullName : ''), ($afterObject ? $afterObject->FullName : ''), 'AssociationOrganizer.SINGULARNAME');
			$hasRealChanges = true;
		}
		if (isset($changedFields['AssociationID'])) {
			$logItem->AddChangedField('AssociationID', $changedFields['AssociationID']['before'], $changedFields['AssociationID']['after']);
			
			$beforeObject = DataObject::get_by_id('Association', (int)$changedFields['AssociationID']['before']);
			$afterObject = DataObject::get_by_id('Association', (int)$changedFields['AssociationID']['after']);
			$logItem->AddChangedField('Association', ($beforeObject ? $beforeObject->Name : ''), ($afterObject ? $afterObject->Name : ''), 'Association.SINGULARNAME');
			$hasRealChanges = true;
		}		
		return $hasRealChanges;
	}	
	
	public function onLogDelete($logItem) {
		$logItem->AddChangedField('Type', '', $this->Type, 'AssociationPermission.TYPE');
		$logItem->AddChangedField('AssociationOrganizerID', '', $this->AssociationOrganizerID);
		$logItem->AddChangedField('AssociationOrganizer', '', $this->AssociationOrganizer()->FullName, 'AssociationOrganizer.SINGULARNAME');
		$logItem->AddChangedField('AssociationID', '', $this->AssociationID);
		$logItem->AddChangedField('Association', '', $this->Association()->Name, 'Association.SINGULARNAME');
	}
}

?>