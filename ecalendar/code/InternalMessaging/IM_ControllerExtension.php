<?php

class IM_ControllerExtension extends Extension {	

    function extraStatics() {        
		return array(            
			'db' => array(
			),            
			'has_one' => array(
				), 
			);    
	}	
	
	public function updateRecipients(&$recipientsDropdownArray) {
		$recipientsDropdownArray = array();
		$recipientsDropdownArray['MarkAll'] = array('id' => 'markall', 'class' => 'recipient-group mark-all', 'text' => '- ' . _t('IM_Controller.MARKALL', 'Mark all') . ' -');
		$recipientsDropdownArray['UnmarkAll'] = array('id' => 'unmarkall', 'class' => 'recipient-group unmark-all', 'text' => '- ' . _t('IM_Controller.UNMARKALL', 'Unmark all') . ' -');		
				
		$members = DataObject::get('Member');
		
		$organizers = new DataObjectSet();
		$moderators = new DataObjectSet();
		$associations = new DataObjectSet();
		
		// Members
		if (eCalendarExtension::isAdmin()) {
			foreach ($members as $member) {
				if ($member instanceof AssociationOrganizer) {
					$permissions = $member->AssociationPermissions();
					if ($permissions) {
						foreach ($permissions as $perm) {
							if ($perm->Type == 'Organizer') {
								$organizers->push($member);
								break;
							}
						}
						foreach ($permissions as $perm) {
							if ($perm->Type == 'Moderator') {
								$moderators->push($member);
								break;
							}
						}					
					}
				}
			}
		}
		else {
			$currentUser = Member::currentUser();
			$myUsers = $currentUser->getMyUsers(null, 'organizers', true);

			if ($myUsers['Organizer']) 
				$organizers = DataObject::get('AssociationOrganizer', "AssociationOrganizer.ID IN ('".implode("','", $myUsers['Organizer'])."')");

			if ($myUsers['Moderator']) 
				$moderators = DataObject::get('AssociationOrganizer', "AssociationOrganizer.ID IN ('".implode("','", $myUsers['Moderator'])."')");		
		}
			
		if ($organizers->Count()) {
			$organizers->sort('Name');
			$recipientsDropdownArray['Organizers'] = array('id' => 'organizers', 'class' => 'recipient-group', 'text' => _t('AssociationOrganizer.SINGULARNAME', 'User'));
			$recipientsDropdownArray += $organizers->toDropdownMap('ID', 'Name');
		}		
		if ($moderators->Count()) {
			$moderators->sort('Name');
			$recipientsDropdownArray['Moderators'] = array('id' => 'moderators', 'class' => 'recipient-group', 'text' => _t('AssociationOrganizer.MODERATORS', 'Moderators'));
			$recipientsDropdownArray += $moderators->toDropdownMap('ID', 'Name');
		}	
		
		// Associations
		/* -- Associations that current user have the permission to choose from */

		if (eCalendarExtension::isAdmin()) {	
			$associations = DataObject::get('Association');
		} else {
			$currentUser = Member::currentUser();
			
			$where_assoc = '';
			$where_organizer = '';

			$myassociations = $currentUser->getMyAssociations(null, 'organizers', true);			
			$where_assoc.= "(
				Association.ID IN ('".implode("','", $myassociations)."')
			)";	

			$mymunicipals = $currentUser->getMyMunicipals();
			
			$associations = DataObject::get('Association', $where_assoc);
		}
		
		if ($associations && $associations->Count()) {
			$associations->sort('Name');
			$recipientsDropdownArray['Associations'] = array('id' => 'associations', 'class' => 'recipient-group', 'text' => _t('Association.PLURALNAME', 'Associations'));
			
			foreach ($associations->toDropdownMap('ID', 'Name') as $key => $value) 
				$recipientsDropdownArray += array('Association_' . $key => $value);
		}
	}
}

?>
