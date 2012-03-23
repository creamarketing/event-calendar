<?php

class EventReport extends ReportController {
	
	protected $orientation = 'landscape';
	
	protected function ReportOptionFields() {
		$fields = parent::ReportOptionFields();
		
		$organizersArray = array('' => _t('AdvancedDropdownField.NONESELECTED', '(None selected)'));
		$associationArray = array('' => _t('AdvancedDropdownField.NONESELECTED', '(None selected)'));
		$municipalsArray = array('' => _t('AdvancedDropdownField.NONESELECTED', '(None selected)'));
		
		$associations = null;
		$organizers = null;
		$municipals = Municipal::toDropdownList();
		
		/* -- Associations that current user have the permission to choose from */

		if (eCalendarExtension::isAdmin()) {	
			$associations = Association::toDropdownList();
			$organizers = AssociationOrganizer::toDropdownList(true);
			//$municipals = Municipal::toDropdownList();
		} else {
			$currentUser = Member::currentUser();			
			
			$where_assoc = '';
			$where_organizer = '';
			//$where_municipals = '';

			$myassociations = $currentUser->getMyAssociations(null, 'organizers', true);			
			$where_assoc.= "(
				Association.ID IN ('".implode("','", $myassociations)."')
			)";	

			$myusers = $currentUser->getMyUsers(null, 'moderators', true);
			$where_organizer.= "(
				AssociationOrganizer.ID IN ('".implode("','", $myusers['All'])."')
			)";

			/*$mymunicipals = $currentUser->getMyMunicipals();
			$where_municipals .= "(
				Municipal.ID IN ('".implode("','", $mymunicipals)."')
			)";*/
			
			$association_objs = DataObject::get('Association', $where_assoc);
			if ($association_objs) {
				$associations = $association_objs->map('ID', 'NameHierachyAsText');
			}	

			$organizers_obj = DataObject::get('AssociationOrganizer', $where_organizer);			
			if ($organizers_obj)
				$organizers = $organizers_obj->map('ID', 'Name');
			
			/*$municipals_obj = DataObject::get('Municipal', $where_municipals);
			if ($municipals_obj)
				$municipals = $municipals_obj->map('ID', 'Name');*/
		}
					
		if ($associations)
			$associationArray += $associations;
		if ($organizers)
			$organizersArray += $organizers;
		if ($municipals)
			$municipalsArray += $municipals;

		$statusArray = array(
			'' => _t('AdvancedDropdownField.NONESELECTED', '(None selected)'),
 			'Preliminary' => _t('Event.STATUS_PRELIMINARY', 'Preliminary'),
 			'Accepted' => _t('Event.STATUS_ACCEPTED', 'Accepted'),
 			'Rejected' => _t('Event.STATUS_REJECTED', 'Rejected'),
 			'Cancelled' => _t('Event.STATUS_CANCELLED', 'Cancelled'),
	 	);		

		if (count($associationArray))
			$fields->push(new AdvancedDropdownField('AssociationID', _t('Association.SINGULARNAME', 'Association'), $associationArray));		
		if (count($organizersArray))
			$fields->push(new AdvancedDropdownField('OrganizerID', _t('AssociationOrganizer.SINGULARNAME', 'User'), $organizersArray));
		if (count($municipalsArray))
			$fields->push(new AdvancedDropdownField('MunicipalID', _t('Municipal.SINGULARNAME', 'Municipality'), $municipalsArray));
		$fields->push(new AdvancedDropdownField('Status', _t('Event.STATUS', 'Status'), $statusArray));
				
		return $fields;
	}
	
	public function GenerateReportData() {
		$customFields = array();
		$dataWhere = array();
		$dataJoin[] = 'LEFT JOIN EventDate ON EventDate.EventID = Event.ID';		
		
		$where_assoc = '';
		$where_organizer = '';		
		$where_municipal = '';
		
		if (eCalendarExtension::isAdmin()) {	

		} else {
			$currentUser = Member::currentUser();			

			$myassociations = $currentUser->getMyAssociations(null, 'organizers', true);			
			$where_assoc.= "(
				AssociationID IN ('".implode("','", $myassociations)."')
			)";	

			$myusers = $currentUser->getMyUsers(null, 'moderators', true);
			$where_organizer.= "OrganizerID IN ('".implode("','", $myusers['All'])."')";

			/*$mymunicipals = $currentUser->getMyMunicipals();
			$where_municipal .= "(
				MunicipalID IN ('".implode("','", $mymunicipals)."')
			)";*/
		}		
		
		if (!empty($this->data['StartDate'])) {
			$customFields['Start'] = $this->data['StartDate'];
			$date = new Zend_Date($this->data['StartDate'], 'dd.MM.yyyy', i18n::get_locale());
			
			$dataWhere[] = "EventDate.Date >= '" . $date->toString('yyyy-MM-dd') . "'";
		}
		if (!empty($this->data['EndDate'])) {
			$customFields['End'] = $this->data['EndDate'];
			$date = new Zend_Date($this->data['EndDate'], 'dd.MM.yyyy', i18n::get_locale());
			
			$dataWhere[] = "EventDate.Date <= '" . $date->toString('yyyy-MM-dd') . "'";
		}	
		if (!empty($this->data['Status'])) {
			$sqlSafeStatus = Convert::raw2sql($this->data['Status']);
			$dataWhere[] = "(Status = '$sqlSafeStatus' AND Status != 'Draft')";
		}
		else 
			$dataWhere[] = "Status != 'Draft'";
		
		if (!empty($this->data['AssociationID'])) {
			$dataWhere[] = "AssociationID = " . (int)$this->data['AssociationID'];
		}
		if (!empty($this->data['OrganizerID'])) {
			$dataWhere[] = "OrganizerID = " . (int)$this->data['OrganizerID'];
		}		
		if (!empty($this->data['MunicipalID'])) {
			$dataWhere[] = "MunicipalID = " . (int)$this->data['MunicipalID'];
		}				

		if (!empty($where_organizer))
			$dataWhere[] = $where_organizer;
		if (!empty($where_assoc))
			$dataWhere[] = $where_assoc;		
		/*if (!empty($where_municipal))
			$dataWhere[] = $where_municipal;*/
				
		$events = DataObject::get('Event', implode(' AND ', $dataWhere), 'Created', implode(' ', $dataJoin));
		if ($events) {
			$events->sort('Start', 'DESC');			
			$customFields['Events'] = $events;
		}
		
		return $this->renderWith('Reports/EventReport', $customFields);
	}
	
}

?>