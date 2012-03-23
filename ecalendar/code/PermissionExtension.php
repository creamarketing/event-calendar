<?php
/*
 *
Typer av rättigheter
	- Administrator för hela systemet (inklusive CMS)
	- Administrator för Evenemangskalendern endast
	- Kommunadministrator (inte automatiskt till underföreningar för dessa föreningar om de inte tillhör samma kommun)
	- Moderator för en eller flera föreningar (samt automatiskt moderator för dessa underföreningar)
	- Arrangör för en eller flera föreningar (inte automatiskt arrangör för dessa underförening)


* Kommunadministrator
	De kommuner användaren tilldelas ger rättigheter:
	- Att göra allt med de föreningar som hör till någon av dessa kommuner	
	- Att göra allt med de användare som hör till en förening som hör till någon av dessa kommuner
	!NOT- Att göra allt med de evenemang som hör till någon av dessa kommuner
	- Får ej ändra sin egen profil
	- Får inte sätta en annnan användare att bli kommunadmin för andra än sina egna kommuner
	- Kan välja en annan arrangör för ett evenemang som hör till någon av de föreningar man har rätt till

* Moderator
	De föreningar en användare tilldelas moderatorrättighet till:
	- Kan göra allt med dessa föreningar och underföreningar
	- Kan göra allt med användarna i dessa föreningar och underföreningar
	- Kan göra allt med evenemang för dessa föreningar och underföreningar, 
		MEN bara om man har publish permission eller de man själv skapat eller är arrangör i
	- Kan välja valfri arrangör ur de föreningar där man är moderator

* Arrangör
	De föreningar en användare tilldelas arrangörrättighet till:
	- Kan publicera evenemang för dessa föreningar (inte underföreningar)
	- Kan ändra evenemang för dessa föreningar ( -- ), 
		MEN bara om man har publish permission eller de man själv skapat eller är arrangör i
	- Kan eventuellt se användare i dessa föreningar (men inte ändra)	
 	- Kan inte välja arrangör never ever
 	
 ** KAN PUBLICERA (eller vad man kallar det) MÅSTE VARA IKRYSSAT FÖR ALLA UTOM HUVUDADMIN
 	ANNARS GÄLLER INGA AV RÄTTIGHETERNA
 */

class PermissionExtension extends DataObjectDecorator {

	public function requireDefaultRecords() {		
		$this->getOrCreateGroup(
				array('CMS_ACCESS_eCalendarAdmin', 'CMS_ACCESS_AssetAdmin', 'CMS_ACCESS_CMSMain'), 
				'eventadmins', _t('eCalendarAdmin.EVENTADMINS', 'Event calendar administrators')
		);
		$this->getOrCreateGroup('CMS_ACCESS_eCalendarAdmin', 'eventusers', _t('eCalendarAdmin.EVENTUSERS', 'Event calendar users'));
		$this->createSystemAdmin();
		$this->checkGroupUsers();
	}	
	
	private function createSystemAdmin() {
		$systemAdmin = DataObject::get_one('AssociationOrganizer', 'SystemAdmin = 1');
		$systemAdminGroup = DataObject::get_one('Group', "Code = 'eventadmins'");	
		
		if (!$systemAdmin) {
			$systemAdmin = new AssociationOrganizer();
			$systemAdmin->FirstName = 'System';
			$systemAdmin->Surname = 'Administrator';
			$systemAdmin->SystemAdmin = true;
			$systemAdmin->PermissionPublish = true;
			$systemAdmin->EmailVerified = date('Y-m-d H:i:s');
			$systemAdmin->write();
			
			$systemAdminGroup->Members()->add($systemAdmin);
		}
		else {
			if (!$systemAdmin->inGroup('eventadmins'))
				$systemAdminGroup->Members()->add($systemAdmin);
		}
	}
	
	private function checkGroupUsers() {
		// Conver Member to event administrators
		$memberGroup = DataObject::get_one('Group', "Code = 'eventadmins'");
		foreach ($memberGroup->Members() as $member) {
			if (!$member->is_a('AssociationOrganizer')) {
				$associationOrganizer = new AssociationOrganizer();
				$associationOrganizer->ID = $member->ID;
				$associationOrganizer->PermissionPublish = true;
				$associationOrganizer->EmailVerified = date('Y-m-d H:i:s');				
				$associationOrganizer->write();
				
				$member->ClassName = 'AssociationOrganizer';
				$member->write();				
			}
		}		
		
		// Conver Member to event user
		$memberGroup = DataObject::get_one('Group', "Code = 'eventusers'");
		foreach ($memberGroup->Members() as $member) {
			if (!$member->is_a('AssociationOrganizer')) {
				$associationOrganizer = new AssociationOrganizer();
				$associationOrganizer->ID = $member->ID;
				$associationOrganizer->EmailVerified = date('Y-m-d H:i:s');				
				$associationOrganizer->write();
				
				$member->ClassName = 'AssociationOrganizer';
				$member->write();
			}			
		}
	}
	
	private function getOrCreateGroup($permissionCode, $groupCode, $groupTitle) {
		// create the group, if it doesn't exist
		$memberGroup = DataObject::get_one('Group', "Code = '$groupCode'");
		if (!$memberGroup) {
			$group = new Group();
			$group->Code = $groupCode;
			$group->Title = $groupTitle;
			$group->write();

			if (is_array($permissionCode)) {
				foreach ($permissionCode as $permCode) {
					Permission::grant($group->ID, $permCode);
					DB::alteration_message(_t('eCalendarAdmin.GROUPCREATED', "Group $groupTitle created"), "created");
				}
			}
			else {
				Permission::grant($group->ID, $permissionCode);
				DB::alteration_message(_t('eCalendarAdmin.GROUPCREATED', "Group $groupTitle created"), "created");				
			}
		}
		else {
			// check that the existing group has the correct permission
			if (is_array($permissionCode)) {
				foreach ($permissionCode as $permCode) {
					$queryResult = DB::query("SELECT * FROM \"Permission\" WHERE \"GroupID\" = '$memberGroup->ID' AND \"Code\" LIKE '$permCode'");
					if ($queryResult->numRecords() == 0 ) {
						Permission::grant($memberGroup->ID, $permCode);
						DB::alteration_message(_t('eCalendarAdmin.PERMISSIONADDED', "Added permissions for existing group $groupTitle"), "created");
					}
				}
			}
			else {
				$queryResult = DB::query("SELECT * FROM \"Permission\" WHERE \"GroupID\" = '$memberGroup->ID' AND \"Code\" LIKE '$permissionCode'");
				if ($queryResult->numRecords() == 0 ) {
					Permission::grant($memberGroup->ID, $permissionCode);
					DB::alteration_message(_t('eCalendarAdmin.PERMISSIONADDED', "Added permissions for existing group $groupTitle"), "created");
				}				
			}
		}
	}		
	
	public function canView($member = null, $override_owner = null) {	
		/*
		 * Admin kan göra allt
		 */
		if (eCalendarExtension::isAdmin($member)) {
			return true;
		}
		
		if (!$member) {
			$member = Member::CurrentUser();
		}	
		
		if ($member) {
			/* Väljer rättigheter beroende på objekt */
			if (is_object($override_owner)) {
				$this->owner = $override_owner;
			}		
			$classname = get_class($this->owner);
			
			switch ($classname) {
				default:
					return $this->canEdit($member);
				break;
				
				
				case 'AssociationOrganizer':
					return $this->canEdit($member) || $member->ID == $this->owner->ID;
				break;
				
				case 'AssociationPermission':
				case 'Association':
					return $this->canEdit($member);
				break;											
				
				case 'PermissionRequest':
				case 'Event':
				case 'EventDate':
				case 'EventImage':					
				case 'EventFile':					
				case 'EventLink':					
				case 'LogEntry':	
				case 'LogEntry_FieldChange':								
					return true;
				break;
				 
			}
		
		}
		return false;
	}
	
	public function canEdit($member = null, $override_owner = null) {					
		/*
		 * Admin kan göra allt
		 */
		if (eCalendarExtension::isAdmin($member)) {
			return true;
		}		
	
		if (!$member) {
			$member = Member::CurrentUser();
		}
		
		if ($member && $member instanceof AssociationOrganizer) {
			/* Väljer rättigheter beroende på objekt */
			if (is_object($override_owner)) {
				$this->owner = $override_owner;
			}		
			$classname = get_class($this->owner);
			
			switch ($classname) {
				
				default:	
					return null; // falls back to normalpermissions			
				break;

				case 'AssociationPermission':
					
					if ( !$member->canPublish() ) { // VIKTIGT!!
						return false;
					}
					
					$condition1 = false; 						
					// Vilka föreningar är denna moderator i?	
					$myassociations = $this->getMyAssociations($member, 'moderators', true);
										
					if (in_array($this->owner->Association()->ID, $myassociations)) {
						$condition1 = true;
					} elseif ($this->owner->ID == 0) { // Gör så skapa ny synns
						$condition1 = true;
					}
										
					if ( $condition1 ) {
						return true;
					}	
				break;
				
				case 'Association':					
					$condition1 = false; 					
					
					// jag som skapat
					if ($this->owner->CreatorID == $member->ID) {
						return true;
					}
					
					// Vilka föreningar är denna moderator i?	
					$myassociations = $this->getMyAssociations($member, 'moderators', true);
					if (in_array($this->owner->ID, $myassociations)) {
						$condition1 = true;
					} elseif ($this->owner->ID == 0) { // Gör så skapa ny synns
						$condition1 = true;
					}
										
					if ( $condition1 ) {
						return true;
					}	
									
				break;				
			
				case 'PermissionRequest':				
					$association = $this->owner->Association();
					if ($association && $this->canEdit($member, $association)) {
						return true;
					}
					
					$myassociations = $this->getMyAssociations(null, 'moderators', true);
						if ( in_array($association->ID, $myassociations) ) {
						return true;
					}					
				break;
			
			
				case 'AssociationOrganizer':					
					$condition1 = ( $this->owner->ID == 0 ); // det är en ny
					$condition2 = $this->owner->CreatorID == $member->ID; // jag som skapat
					$condition3 = $this->owner->ID == $member->ID; // får alltid editera sig själv
										
					if ( $condition1 || $condition2 || $condition3 ) {
						return true;
					}
					
					if ( !$member->canPublish() ) { // VIKTIGT!!
						return false;
					}					
					
					if ( eCalendarExtension::isMunicipalModerator($this->owner) ) {
						return false;
					}
					
					if ($this->owner->ID > 0 && $member instanceof AssociationOrganizer) {	
						$mun_permissions = $this->owner->MunicipalPermissions()->map('ID', 'ID');	
						// Surprising that ID is ID of Municipal? but so it is		
						if ( count($mun_permissions) > 0 ) { // Om personen kommunadmin							
							if ( count( $member->MunicipalPermissions("MunicipalID IN ('".implode("','", $mun_permissions)."')") ) == 0 ) {
								return false; //Om inte mina rättigheter finns i någon av dessa kommuner
							}	
						}
						
						$associationPermissions = null;					
						
						// Om en person med rättigheter i en förening som jag har tillgång till så kan man editera
						// Borde bli automatiskt även rätt om jag är kommunadmin i en kommun där föreningen finns (med dess arrangörer) 
						$myassociations = $this->getMyAssociations($member, 'moderators', true);					
						if (count($myassociations) > 0) { // IMPORTANT!!! Else will return all!
							$associationPermissions = $this->owner->AssociationPermissions("AssociationPermission.AssociationID IN ('".implode("','", $myassociations)."')");				
						}
						
						if ( $associationPermissions && $associationPermissions->exists()) {	
							return true;										
						}	
					}				
											
				break;
				
				
				case 'Event':					
					
					// Om arrangören av denna event
					$organizer = $this->owner->Organizer();					
					if ( $organizer ) {
						if ($organizer->ID == $member->ID) {
							return true; 						
						}
					}		
					// Om den som skapat denna event
					$creator = $this->owner->Creator();				
					if ( $creator ) {
						if ($creator->ID == $member->ID) {
							return true; 						
						}
					}		
					
					if ( $member instanceof AssociationOrganizer ) {
						if ( !$member->CanPublish() ) { // OBS, annars kan man ju börja editera andra evenemang utan att vara godkänd!!
							return false;
						}
					} else {
						return false;
					}
					
					// Vilka kommuner är denna admin i? 
					$mymunicipals = $this->getMyMunicipals();
					if (count($mymunicipals) && $this->owner->Municipal()) {
						if ( in_array($this->owner->Municipal()->ID, $mymunicipals) ) {
							return true; // return makes the system faster cause next step might take more time
						}
					}
					
					// Om en event i en förening som jag har tillgång till så kan man editera
					$myassociations = $this->getMyAssociations($member, 'organizers', true);
									
					$association = $this->owner->Association();
					if ($association) {					
						if ( in_array($association->ID, $myassociations) && $this->owner->ID > 0) {						
							return true;
						} elseif ($this->owner->ID == 0) { // Gör så skapa ny synns
							return true;
						}												
					}									
			
				break;
				
				
				case 'EventImage':
				case 'EventDate':					
				case 'EventFile':					
				case 'EventLink':					
								
					$event = $this->owner->Event();				
					if ($event) {									
						return $this->canEdit($member, $event);
					}					
				break;
				
				case 'Municipal':
					if ( in_array($this->owner->ID, $this->getMyMunicipals()) ) {
						return true;
					}
				break;
				case 'LogEntry': 
				case 'LogEntry_FieldChange':
					return false;
				break;
			}
		
		}
		return false;		
	}
	
	#TODO: Hur ska man vet om man får skapa en ny förening rent allmänt???
	public function canCreate($member = null, $override_owner = null) {			
		if (is_object($override_owner)) {
			$this->owner = $override_owner;
		}		
		$classname = get_class($this->owner);
		
		/*
		 * Admin kan göra allt
		 */
		if (eCalendarExtension::isAdmin($member)) {
			return true;
		}
		
		if (!$member) {
			$member = Member::CurrentUser();
		}	
		
		if ($member) {
			/* Väljer rättigheter beroende på objekt */
			
			$classname = get_class($this->owner);
			switch ($classname) {
				default:
					return null; // falls back to normalpermissions	
				break;
				
				case 'Association':
				case 'AssociationOrganizer':
					if ( !$member->canPublish() ) { // VIKTIGT!!
						return false;
					}
					$condition1 = false;						

					$myassociations = $this->getMyAssociations($member, 'moderators', true);					
					if (count($myassociations) > 0) {
						$condition1 = true;
					}
					
					return ( $condition1 );			
				break;						
		
				case 'AssociationPermission':
					if ( !$member->canPublish() ) { // VIKTIGT!!
						return false;
					}
				case 'Event':
				case 'EventDate':				
				case 'EventImage':
				case 'EventFile':					
				case 'EventLink':	
					if ($this->owner->ID > 0) {
						return $this->canEdit($member);
					}
					return true;
				break;
				
				case 'LogEntry': 
				case 'LogEntry_FieldChange':
					return true;
				break;			
			}		
		}
		
		return false;
	}
	
	// Endast den som har skapat en förening kan ta bort den eller admin, samma gäller moderatorer (räcker det??)

	public function canDelete($member = null, $override_owner = null) {
		if (is_object($override_owner)) {
			$this->owner = $override_owner;
		}		
		$classname = get_class($this->owner);
				
		/*
		 * Admin kan göra allt .. NÄSTAN ;)
		 */
		if ($classname == 'CalendarLocale') {
			return false;
		}
		
		if (eCalendarExtension::isAdmin($member)) {
			return true;
		}
		
		if (!$member) {
			$member = Member::CurrentUser();
		}	
		
		if ($member && $member instanceof AssociationOrganizer) {
			/* Väljer rättigheter beroende på objekt */	
			
			switch ($classname) {
				default:
					return null; // falls back to normalpermissions
				break;
				
				case 'AssociationPermission':
					return $this->canEdit($member);
				break;
				
				case 'Association':
					// Borde man kanske ändå få ta bort organisationer som moderator?
					$condition1 = ( $this->owner->CreatorID == $member->ID );	
					$condition2 = false;

					$myassociations = $this->getMyAssociations($member, 'municipaladmins');					
					if ( in_array($this->owner->ID, $myassociations) && $this->owner->ID > 0) {						
						$condition2 = true;
					}												
					
					return ($condition1 || $condition2);
				break;
						
				case 'AssociationOrganizer':
					$condition1 = $this->canEdit($member);
					$condition2 = ($this->owner->ID != $member->ID);	
					$condition3 = (eCalendarExtension::IsMunicipalModerator() || eCalendarExtension::isAdmin($member));
					$condition4 = (!$this->owner->NumVisit && $this->owner->CreatorID == $member->ID); // We can delete as long has he hasn't logged in if we are the creator
					
					if ($condition1 && $condition2 && ($condition3 || $condition4))
						return true;
				break;
				
				case 'Event':																			
				case 'EventImage':
				case 'EventDate':
				case 'EventFile':					
				case 'EventLink':						
					return $this->canEdit($member);
				break;
				case 'LogEntry': 
				case 'LogEntry_FieldChange':
					return false;
				break;			
			}
		
		}
		return false;
	
	}
		
	/*
	 * Virtual permissiongroups (some of them), this check just to preven coding errors a bit
	 */
	public function checkValidGroup($group) {
		$valid = false;
		switch ($group) {
			case 'moderators':
			case 'organizers':
			case 'municipaladmins':
			case 'eventusers':
			case 'eventadmins':
			case 'administrators':
				$valid = true;
			break;			
		}
		
		if ($valid == false) {
			throw new Exception('Invalid member group "'. $group.'"!');
			return false;
		}
		
		return $group;
	}
	
	public function checkValidPermType($type) {	
		$types = singleton('AssociationPermission')->dbObject('Type')->enumValues();
		if (isset($types[$type])) {
			return true;
		}
		
		throw new Exception('Invalid permission type "'. $type.'"!');
		return false;		
	}
	
	/*
	 * OBS! För admin returnerar inga associations..
	 * $atleast = true -> Returnerar även sådana föreningar man är mer än t.ex. arrangör i
	 */
	public function getMyAssociations($member = null, $permissiongroup = 'organizers', $atleast = false, $excludeNew = false) {		
		$permissiongroup = self::checkValidGroup($permissiongroup);
		if (!$permissiongroup) {
			return array();
		}
		
		if (!$member) {		
			$member = Member::CurrentUser();
		}
		
		$member = DataObject::get_by_id('AssociationOrganizer', $member->ID);
		if (!$member) {
			return array();
		}
		
		$myassociations = array();		
		switch ($permissiongroup) {		

			case 'organizers': // endast organisatör i en förening (inte automatiskt underföreningarna)
				$permissions = $member->AssociationPermissions("AssociationPermission.Type = 'Organizer'");
				if ($permissions) {
					foreach ($permissions as $permission) {								
						$status = $permission->Association()->Status;
						if ($status != 'New' && $status != '' || $excludeNew == false) { // Om den inte är godkänd exkludera
							$myassociations[$permission->Association()->ID] = $permission->Association()->ID;
						}						
					}				
				}
			
				if ($atleast == false) {
					break;
				}
			
			case 'moderators': // moderatör även i alla underföreningar till alla föreningar				
				$permissions = $member->AssociationPermissions("AssociationPermission.Type = 'Moderator'");				
				if ($permissions) {				
					foreach ($permissions as $permission) {
						$allchildren = null;
						$status = $permission->Association()->Status;
						if (($status != 'New' && $status != '') || $excludeNew == false) { // Om den inte är godkänd exkludera, underföreningarna får man tillgång till dock
							$myassociations[$permission->Association()->ID] = $permission->Association()->ID;
							$allchildren = eCalendarExtension::getChildrenIDRecursive($permission->Association());
						}						
						if ($allchildren) {
							if ($excludeNew == true) {
								foreach ($allchildren as $associationId) {
									$association = DataObject::get_by_id('Association', $associationId);
									if ($association->Status == 'New' || $association->Status == '') {
										unset($allchildren[$associationId]);
									}
								}
							}								
							$myassociations = array_merge($myassociations, $allchildren);
						}						
					}				
				}
				
				if ($atleast == false) {
					break;
				}		
			
			
			case 'muncipaladmins': // alla föreningar som är i den kommun som tillhör de kommuner man är admin i
				$municipals = $member->MunicipalPermissions();
				$associations = null;
				if ($municipals) {					
					$municipalids = $municipals->map('ID', 'ID');							
					if ($municipalids) {
						$associations = DataObject::get('Association', "Association.MunicipalID IN('".implode("','", $municipalids)."')");
						if ($associations) 
							$myassociations = array_merge($myassociations, $associations->column('ID'));
					}
				}
			break;

			
			default:
				
			break;
		}
		
		return $myassociations; 		
	}
	
	public function getMyMunicipals($member = null) {
		if (!$member) {		
			$member = Member::CurrentUser();
			if (!$member) 
				return array();			
		}
		
		$member = DataObject::get_by_id('AssociationOrganizer', $member->ID);
		if (!$member) {
			return array();
		}
		
		$mymunicipals = array();	
		$municipals = $member->MunicipalPermissions();
		if ($municipals) {
			$mymunicipals = $municipals->map('ID', 'ID');			
		}
		
		return $mymunicipals;		
	}
	
	public function getMyPermissions($member = null, $type = null) {
		if (!$member) {		
			$member = Member::CurrentUser();
		}
		
		$member = DataObject::get_by_id('AssociationOrganizer', $member->ID);
		if (!$member) {
			return null;
		}
		
		$permissions = array();	
		if ($type == null) {
			$permissions = $member->AssociationPermissions();
		} elseif (self::checkValidPermType($type)) {
			$permissions = $member->AssociationPermissions("Type = '$type'");
		}
				
		return $permissions;		
	}
	
	/*
	 * This one is very special
	 * - First list associations I have the specific permissions to ($permissiongroup)
	 * - For every of these associations return users grouped by permission
	 */
	public function getMyUsers($member = null, $permissiongroup = 'organizers', $atleast = false) {
		$myassociations = $this->getMyAssociations($member, $permissiongroup, $atleast);
		
		$myusers = array(
			'Organizer' => array(), 
			'Moderator' => array(),
			'All' => array()
		);
		
		if ($myassociations) {
			foreach ($myassociations as $associationId) {
				$permissions = DataObject::get('AssociationPermission', "AssociationPermission.AssociationID = '".$associationId."'");
				if ($permissions) {
					foreach ($permissions as $permission) {
						if (!isset($myusers[$permission->Type])) {
							$myusers[$permission->Type] = array();
						}
						$myusers[$permission->Type][$permission->AssociationOrganizer()->ID] = $permission->AssociationOrganizer()->ID;	
						$myusers['All'][$permission->AssociationOrganizer()->ID] = $permission->AssociationOrganizer()->ID;	
					}
				}
			}
		}
		
		return $myusers;
	}
	
	public function getAssociationUsers($member = null, $association, $permissiongroup = 'organizers', $atleast = false) {			
		$myusers = array(
			'Organizer' => array(), 
			'Moderator' => array(),
			'All' => array()
		);
			
		$permissions = DataObject::get('AssociationPermission', "AssociationPermission.AssociationID = '".$association->ID."'");
		if ($permissions) {
			foreach ($permissions as $permission) {
				if (!isset($myusers[$permission->Type])) {
					$myusers[$permission->Type] = array();
				}
				$myusers[$permission->Type][$permission->AssociationOrganizer()->ID] = $permission->AssociationOrganizer()->ID;	
				$myusers['All'][$permission->AssociationOrganizer()->ID] = $permission->AssociationOrganizer()->ID;	
			}
		}
		
		return $myusers;
	}
}

?>