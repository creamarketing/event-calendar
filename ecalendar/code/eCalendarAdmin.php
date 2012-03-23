<?php

require_once('Zend/Date.php');

class eCalendarAdmin extends LeftAndMain  {
	
	static $extensions = array(
		'eCalendarExtension()',
		'CreaDataObjectExtension',
		'PermissionExtension',
	);
	
	static $url_segment = 'ecalendar';
	
	static $menu_title = 'Event calendar';
	
	static $menu_priority = 3;
	
	public $view = 'default';
	
	static $allowed_views = array(
		'default',
			
		'editevents',
		'editcategories',
	
		'editassociations',		
	
		'editlanguages',
		'editmunicipals',
	
		'editorganizers',
		'editmoderators',
		
		'eventreport',
		'dataexport',
		'logreport',
		
		'editmessages',
		'handleregistrations'
	);	

	static $allowed_actions = array(		
		'UserInviteForm',
		'ApplyForMembershipForm',
			
		'EditPermissionRequestsForm',
		'EditUserInviteRequestsForm',
		
		'EditAssociationsForm',
		'EditAssociationsForm_New',
				
		'EditOrganizersForm',	
		'EditOrganizersForm_NotConfirmed',		
		
		'EditModeratorsForm',
	
		'EditEventsForm',
		'EditEventsForm_Mine',
		'EditEventsForm_Others',
		'EditEventsForm_Unhandled',
		'EditEventsForm_Preliminary',
		'EditEventsForm_History',
		'EditEventsForm_History_Mine',
		'EditEventsForm_History_Others',
		'EditEventsForm_Draft',
		'EditCategoriesForm',
				
		'EditMunicipalsForm',	
		'EditLocalesForm',			
		
		'InternalMessages',
		'EventReportForm',
		'DataExportForm',
		'LogReportForm',
		'LogEntryForm',
	
		'getReport',
		'eventPreview',
		'isEmailRegistered',
		'viewNetTicketEmails'
	);
	
	function emulateIE7() {
		return false;
	}	
	
	function defineMethods() {
		parent::defineMethods();
		foreach (self::$allowed_views as $view) {
			self::$allowed_actions[] = $view;
		}
	}
	
	
	public function CurrentView($view) {
		if ($this->view == $view) {
			return true;
		}
		return false;
	}
	/*
	 * Används för LeftAndMain menyn
	 */	
	function showInNavigation($classname = null) {
		/*
		 * OBS INTE ENS ADMIN SKA SE SAMMA VYER..
		 */
		$member = Member::CurrentUser();
		if ($member) {
		/* Väljer rättigheter beroende på objekt */
			switch ($classname) {							
				case 'Municipal':
					if ( count(PermissionExtension::getMyMunicipals()) > 0 ) {
						return true;
					}
				break;
				
				case 'Event':
					return true;	
				break;
				
				case 'EventCategory':
					return $this->isAdmin();
				break;
				
				case 'Association':
					if ($this->isAdmin()) {
						return true;
					}
									
					if ( count(PermissionExtension::getMyMunicipals()) > 0 ) {
						return true;
					}
					
					if ( count(PermissionExtension::getMyPermissions($member, 'Moderator')) > 0 ) {
						return true;
					}
				break;	
				
				case 'AssociationOrganizer':					
					return true;
				break;	
			
				case 'Reports':
					if ($this->isAdmin()) {
						return true;
					}					
					
					if ( count(PermissionExtension::getMyPermissions($member, 'Moderator')) > 0 ) {
						return true;
					}					
				break;
				
				case 'LogReport': 
				case 'DataExport':
					if ($this->isAdmin()) {
						return true;
					}		
				break;
				case 'Messages': 
					return true;
				break;
			
				case 'PermissionRequests':
					return true;
				break;
			
				case 'UserInviteRequests':
					return true;
				break;

				default:
					
				break;
				
			}
		
		}
		
		return false;
	}
		
	public function init() {	
		parent::init();		
		
		// remove combined base.js file, it messes up some javascript...
		if (isset(Requirements::backend()->combine_files['base.js'])) {
			unset(Requirements::backend()->combine_files['base.js']);
		} 
		
		$this->getECalendarRequirements();			
		
		// Booking Calendar admin stuff
		Requirements::javascript('ecalendar/javascript/eCalendarAdmin.js');
		Requirements::css('ecalendar/css/eCalendarAdmin.css');
		
		// extra css to make it look the same as on public page
		Requirements::css('themes/blackcandy/css/layout.css');
		Requirements::css('themes/blackcandy/css/form.css');
		Requirements::css('ecalendar/css/typography.css');
		
		Requirements::block(THIRDPARTY_DIR . '/jquery-ui/jquery-ui-1.8rc3.custom.js');
		Requirements::block(THIRDPARTY_DIR . '/jquery-ui/jquery.ui.dialog.js');
		Requirements::block(THIRDPARTY_DIR . '/jquery-ui-themes/smoothness/jquery-ui-1.8rc3.custom.css');
		Requirements::block('themes/blackcandy/css/typography.css');
		Requirements::block('cms/css/typography.css');
		
		// set view based on url parameter
		$urlAction = $this->urlParams['Action'];
		if (in_array($urlAction, self::$allowed_views)) {
			$this->view = $urlAction;
		}
		else {
			$this->view = 'default';
		}
				
		$member = Member::CurrentUser();
		if ($member) {
			if (in_array($member->Locale, Translatable::get_allowed_locales())) {
				Translatable::set_current_locale($member->Locale);
			}
		}
	}
	
	public function ApplyForMembershipLink() {			
		return $this->Link().'ApplyForMembershipForm/field/PermissionRequest/add';
		//$securityID = Session::get('SecurityID'); 
		///return $this->Link().'ApplyForMembershipForm?SecurityID='.$securityID;
	}
	
	public function EditProfileLink() {
		$member = Member::currentUser();
		$id = $member->ID;
		return $this->Link().'EditOrganizersForm/field/Organizers/item/'.$id.'/edit';
	}
	
	public function EditPermissionRequestsForm() {		
		$where = array(); 
		$member = Member::currentUser();
		
		// Vilka föreningar är denna moderator i?
		if (!$this->isAdmin()) {				
			$where[] = "
				(
					PermissionRequest.AssociationID IN ('".implode("','", $this->getMyAssociations(null, 'moderators', true))."')
					OR  PermissionRequest.UserID = '$member->ID'
				)";
		}
				
		$where[] = "PermissionRequest.Status = 'New'";
		$where = implode(' AND ', $where);
	
		//$requests = DataObject::get('PermissionRequest', $where, 'Created');
		
		$fields = new FieldSet(
			$DOM = new DialogDataObjectManager(
				$this,
				'PermissionRequests',
				'PermissionRequest',
				array(
					'UserFullName' => _t('AssociationOrganizer.SINGULARNAME', 'User'),				
					'UserEmail' => _t('Member.EMAIL', 'Email'),
					'Association' => _t('Association.SINGULARNAME', 'Association'),				
					'AcceptLinkNice' => _t('PermissionRequest.ACCEPT', 'Accept'),
					'RejectLinkNice' => _t('PermissionRequest.REJECT', 'Reject')
				),
				null,
				$where
			)
		);
		
		$DOM->setAddTitle(_t('PermissionRequest.SINGULARNAME', 'Permission request'));
		$DOM->setPluralTitle(_t('PermissionRequest.PLURALNAME', 'Permission requests'));
		$DOM->setPermissions(array());
		$DOM->setActionsDisabled(true);
		$actions = new FieldSet();
		
		$DOM->setColumnWidths(array(
			'UserFullName' => '20',
			'UserEmail' => '25',
			'Association' => '35',
			'AcceptLinkNice' => '10',
			'RejectLinkNice' => '10'
		));
		
		return new Form($this, "EditPermissionRequestsForm", $fields, $actions);	
	}
	
	public function EditUserInviteRequestsForm() {		
		$where = array(); 
		$member = Member::currentUser();
		
		// Vilka föreningar är denna moderator i?
		if (!$this->isAdmin()) {				
			$where[] = "
				(
					UserInviteRequest.AssociationID IN ('".implode("','", $this->getMyAssociations(null, 'moderators', true))."')
					OR  UserInviteRequest.UserID = '$member->ID'
				)";
		}
				
		$where[] = "UserInviteRequest.Status = 'New'";
		$where = implode(' AND ', $where);
	
		$fields = new FieldSet(
			$DOM = new DialogDataObjectManager(
				$this,
				'UserInviteRequests',
				'UserInviteRequest',
				array(
					'UserFullName' => _t('AssociationOrganizer.SINGULARNAME', 'User'),				
					'UserEmail' => _t('Member.EMAIL', 'Email'),
					'Association' => _t('Association.SINGULARNAME', 'Association'),				
					'AcceptLinkNice' => _t('UserInviteRequest.ACCEPT', 'Accept'),
					'RejectLinkNice' => _t('UserInviteRequest.REJECT', 'Reject')
				),
				null,
				$where
			)
		);
		
		$DOM->setAddTitle(_t('UserInviteRequest.SINGULARNAME', 'User invite request'));
		$DOM->setPluralTitle(_t('UserInviteRequest.PLURALNAME', 'User invite requests'));
		$DOM->setPermissions(array());
		$DOM->setActionsDisabled(true);
		$actions = new FieldSet();
		
		$DOM->setColumnWidths(array(
			'UserFullName' => '20',
			'UserEmail' => '25',
			'Association' => '35',
			'AcceptLinkNice' => '10',
			'RejectLinkNice' => '10'
		));
		
		return new Form($this, "EditUserInviteRequestsForm", $fields, $actions);	
	}	
	
	public function ApplyForMembershipForm() {
		$fields = new FieldSet(
			$DOM = new DialogDataObjectManager(
				$this,
				'PermissionRequest',
				'PermissionRequest',
				array(
					'Association' => _t('Association.SINGULARNAME', 'Association'),				
				)
			)
		);
		
		$DOM->setAddTitle(_t('PermissionRequest.SINGULARNAME', 'Permission request'));
		$DOM->setPluralTitle(_t('PermissionRequest.PLURALNAME', 'Permission requests'));
		
		$actions = new FieldSet();
		
		return new Form($this, "ApplyForMembershipForm", $fields, $actions);	
	}
	
	public function UserInviteForm() {
		$fields = new FieldSet(
			$DOM = new DialogDataObjectManager(
				$this,
				'UserInviteRequest',
				'UserInviteRequest',
				array(
					'Association' => _t('Association.SINGULARNAME', 'Association'),				
				)
			)
		);
		
		$DOM->setAddTitle(_t('UserInviteRequest.SINGULARNAME', 'Invite request'));
		$DOM->setPluralTitle(_t('UserInviteRequest.PLURALNAME', 'Invite requests'));
		
		$actions = new FieldSet();
		
		return new Form($this, "UserInviteForm", $fields, $actions);
	}
	
	public function UserInviteLink() {			
		return $this->Link().'UserInviteForm/field/UserInviteRequest/add';
	}
	
	public function EditAssociationsForm_New() {
		return $this->EditAssociationsForm(null, null, "New");	
	}
	
	public function EditAssociationsForm($request = null, $dummy = null, $return = null) {	
		$where = array();		
		$member = Member::CurrentUser();
		if (eCalendarExtension::isAdmin()) {
			
		} else {
			$associationsids = PermissionExtension::getMyAssociations($member, 'organizers', true, $return == 'New' ? false : true);			
			if ($member) {
				$where[] = "(Association.ID IN ('".implode("','", $associationsids)."') OR Association.CreatorID = " . $member->ID . ')';
			} else { // overkill :)
				$where[] = "Association.ID = 0"; // Visa inga resultat
			}
			
		}	
			
		switch ($return) {		
			case 'New':
				$where[] = "Association.Status = 'New'";
			break;		
			default:
				
			break;		
		}
		
		$where = implode(' AND ', $where);

		if (eCalendarAdmin::showInNavigation('Association')) {		
			$fields = new FieldSet(				
				$DOM = new DialogDataObjectManager(
					$this, 
					'Associations', 
					'Association', 
					array( 
						'NameHierachyAsHTML' => _t('Association.NAME', 'Name'),							
						'Municipal' => _t('Municipal.SINGULARNAME', 'Municipal'),							
						'NiceStatus' => _t('Association.STATUS', 'Status'),						
						'NiceType' => _t('Association.TYPE', 'Type'),								
						'CreatedNice' => _t('Association.CREATED', 'Created'),		
						'LastEditedNice' => _t('Association.LASTEDITED', 'Last edited')
					),
					null,
					$where,
					'Name'
				)		
			);
			
			$DOM->setColumnWidths(array(
				'NameHierachyAsHTML' => '30',
				'Municipal' => '18',
				'NiceStatus' => '10',
				'NiceType' => '12',
				'CreatedNice' => '15',
				'LastEditedNice' => '15'
			));			
			
			$DOM->setHighlightConditions(array(
				array(
					"rule" => '$Status == \'Passive\'',
					"class" => 'association-passive',
				),
				array(
					"rule" => '$Status == \'New\'',
					"class" => 'association-new',
				)
			));				
				
			$DOM->setAddTitle(_t('Association.SINGULARNAME', 'Association'));
			$DOM->setStatusMode(true);
			if ($member->is_a('AssociationOrganizer') && $member->inGroup('eventusers'))
				$DOM->setCustomAddLink($this->ApplyForMembershipLink());
			
			switch ($return) {
				default:
					$values = singleton('Association')->dbObject('Type')->enumValues();
					foreach ($values as $key => &$value) {
						$value = _t('Association.TYPE_' . strtoupper($value), $value);
					}
					$DOM->setFilter('Association.Type', _t('Association.TYPE', 'Type'), $values);
					$DOM->addPermission('add');
				break;
				
				case 'New':
					$DOM->removePermission('add');
				break;			
			}
		
			$actions = new FieldSet();
			$return = "EditAssociationsForm".($return?'_'.$return:'');
			return new Form($this, $return, $fields, $actions);
		}	
	}
	
	public function EditEventsForm_Unhandled() {
		return $this->EditEventsForm(null, null, "Unhandled");	
	}	
	
	public function EditEventsForm_Preliminary() {
		return $this->EditEventsForm(null, null, "Preliminary");	
	}

	public function EditEventsForm_History() {
		return $this->EditEventsForm(null, null, "History");	
	}
	
	public function EditEventsForm_History_Mine() {
		return $this->EditEventsForm(null, null, "History_Mine");	
	}
	
	public function EditEventsForm_History_Others() {
		return $this->EditEventsForm(null, null, "History_Others");
	}	
	
	public function EditEventsForm_Mine() {
		return $this->EditEventsForm(null, null, "Mine");
	}		

	public function EditEventsForm_Others() {
		return $this->EditEventsForm(null, null, "Others");
	}			
	
	public function EditEventsForm_Draft() {
		return $this->EditEventsForm(null, null, "Draft");
	}	
	
	public function EditEventsForm($request = null, $dummy = null, $return = null) {					
				
		$where_and = array();
		$where_or = array();
		$where = null;

		$member = Member::currentUser();
		
		if (eCalendarExtension::isAdmin()) {
				
		} else {		
			// Vilka kommuner är denna admin i? 
			$mymunicipals = $this->getMyMunicipals();
			if (count($mymunicipals) > 0) {				
				$where_or[] = "Event.MunicipalID IN ('".implode("','", $mymunicipals)."')";
			}
			
			// Om en event i en förening som jag har tillgång till så kan man editera
			$myassociations = $this->getMyAssociations($member, 'organizers', true);					
			$where_or[] = "Event.AssociationID IN ('".implode("','", $myassociations)."')";						
			$where_or[] = 'OrganizerID = '.$member->ID;				
			$where_or[] = 'AssociationOrganizer.CreatorID = '.$member->ID.'';				
		} 		
		
		switch ($return) {		
			case 'Unhandled':
				$where_and[] = "Association.Status = 'Active'";
			case 'Preliminary':
				$where_and[] = "Event.Status = 'Preliminary'";
			break;		
		
			case 'Draft':
				$where_and[] = "(Event.Status = 'Draft' AND (OrganizerID = {$member->ID} OR Event.CreatorID = $member->ID))";
			break;
			
			case 'History':
				$where_and[] = "(DATE(Event.End) < CURDATE() AND Event.Status != 'Draft')";
			break;
		
			case 'Mine':
				$where_and[] = "(DATE(Event.End) >= CURDATE() AND Event.Status != 'Draft')";
				$where_and[] = "(Event.OrganizerID = '{$member->ID}' OR Event.CreatorID = '{$member->ID}')";
			break;
		
			case 'Others':
				$where_and[] = "(DATE(Event.End) >= CURDATE() AND Event.Status != 'Draft')";
				$where_and[] = "(Event.OrganizerID != '{$member->ID}' AND Event.CreatorID != '{$member->ID}')";
			break;
		
			case 'History_Mine':
				$where_and[] = "(DATE(Event.End) < CURDATE() AND Event.Status != 'Draft')";
				$where_and[] = "(Event.OrganizerID = '{$member->ID}' OR Event.CreatorID = '{$member->ID}')";
			break;
		
			case 'History_Others':
				$where_and[] = "(DATE(Event.End) < CURDATE() AND Event.Status != 'Draft')";
				$where_and[] = "(Event.OrganizerID != '{$member->ID}' AND Event.CreatorID != '{$member->ID}')";
			break;		
		
			default:
				$where_and[] = "(DATE(Event.End) >= CURDATE() AND Event.Status != 'Draft')";
			break;		
		}

		if (count($where_or) > 0) {
			$where_or = '('.implode(' OR ', $where_or).')';
			if (count($where_and) > 0) {
				$where = $where_or.' AND ('.implode(' AND ', $where_and).')';
			} else {
				$where = $where_or;
			}
		} elseif( count($where_and) > 0) {
			$where = implode(' AND ', $where_and);
		}
		
		$eventcolumns = array( 	
			'Title' => _t('Event.TITLE', 'Title'),						
			'OrganizerName' => _t('Event.ORGANIZERANDUSER', 'Association & User'),
			'Municipal' => _t('Municipal.SINGULARNAME', 'Municipal'),	
			'PeriodNice' => _t('Event.PERIOD', 'Start'),				
			'NiceStatus' => _t('Event.STATUS', 'Status'),
			'PublishedDateNice' => _t('Event.PUBLISHEDDATE', 'Published'),	
			'PreviewLink' => '&nbsp;'
		);
				
		$fields = new FieldSet(				
			$eventDOM = new DialogDataObjectManager(
				$this, 
				'Events', 
				'Event', 
				$eventcolumns,
				null,
				$where,
				'LastEdited DESC',
				'LEFT JOIN AssociationOrganizer ON Event.OrganizerID = AssociationOrganizer.ID LEFT JOIN Association ON Event.AssociationID = Association.ID'
			)	
		);
			
		$eventDOM->setAddTitle(_t('Event.SINGULARNAME', 'Event'));
		$eventDOM->setColumnWidths(array(
			'Title' => '28',
			'OrganizerName' => '16',
			'Municipal' => '16',
			'PeriodNice' => '14',
			'NiceStatus' => '8',
			'PublishedDateNice' => '12',
			'PreviewLink' => '6'
		));
		
		switch ($return) {
			default:
				$values = singleton('Event')->dbObject('Status')->enumValues();
				$arrayValues = array();
				foreach ($values as $key => $value) {
					if ($value != 'Draft')
						$arrayValues[$key] = _t('Event.STATUS_' . strtoupper($value), $value);
				}
				$eventDOM->setFilter('Event.Status', _t('Event.STATUS', 'Status'), $arrayValues);
				$eventDOM->addPermission('duplicate');
			break;
			
			case 'Others':		
				$eventDOM->addPermission('duplicate');
			case 'Unhandled':
			case 'Preliminary':
			case 'Draft':
				$eventDOM->removePermission('add');
			break;			
				
			case 'History': 
			case 'History_Mine':
			case 'History_Others':
				$eventDOM->removePermission('add');
				$eventDOM->addPermission('duplicate');
			break;
		}
		
		$eventDOM->setDraftMode(true);		
		
		$actions = new FieldSet();

		$return = "EditEventsForm".($return?'_'.$return:'');
		return new Form($this, $return, $fields, $actions);
		
	}

	public function CountEvents($event_status = null) {	
				
		$where_and = array();
		$where_or = array();
		$where = null;

		$member = Member::currentUser();
		
		if (eCalendarExtension::isAdmin()) {
				
		} else {		
			// Vilka kommuner är denna admin i? 
			$mymunicipals = $this->getMyMunicipals();
			if (count($mymunicipals) > 0) {				
				$where_or[] = "Event.MunicipalID IN ('".implode("','", $mymunicipals)."')";
			}
			
			// Om en event i en förening som jag har tillgång till så kan man editera
			$myassociations = $this->getMyAssociations($member, 'organizers', true);					
			$where_or[] = "Event.AssociationID IN ('".implode("','", $myassociations)."')";						
			$where_or[] = 'OrganizerID = '.$member->ID;				
			$where_or[] = 'AssociationOrganizer.CreatorID = '.$member->ID.'';				
		} 		
		
		switch ($event_status) {		
			case 'Preliminary':
				$where_and[] = "Event.Status = 'Preliminary'";
			break;		
		
			case 'Draft':
				$where_and[] = "(Event.Status = 'Draft' AND (Event.OrganizerID = '{$member->ID}' OR Event.CreatorID = '$member->ID'))";
			break;
			
			case 'History':
				$where_and[] = "(DATE(Event.End) < CURDATE() AND Event.Status != 'Draft')";
			break;
		
			case 'History_Mine': 
				$where_and[] = "(DATE(Event.End) < CURDATE() AND Event.Status != 'Draft')";
				$where_and[] = "(Event.OrganizerID = '{$member->ID}' OR Event.CreatorID = '{$member->ID}')";
			break;
		
			case 'History_Others':
				$where_and[] = "(DATE(Event.End) < CURDATE() AND Event.Status != 'Draft')";
				$where_and[] = "(Event.OrganizerID != '{$member->ID}' AND Event.CreatorID != '{$member->ID}')";
			break;		
		
			case 'Mine':
				$where_and[] = "(DATE(Event.End) >= CURDATE() AND Event.Status != 'Draft')";
				$where_and[] = "(Event.OrganizerID = '{$member->ID}' OR Event.CreatorID = '{$member->ID}')";
			break;
		
			case 'Others':
				$where_and[] = "(DATE(Event.End) >= CURDATE() AND Event.Status != 'Draft')";
				$where_and[] = "(Event.OrganizerID != '{$member->ID}' AND Event.CreatorID != '{$member->ID}')";
			break;
		
			default:
				$where_and[] = "(DATE(Event.End) >= CURDATE() AND Event.Status != 'Draft')";
			break;		
		}

		if (count($where_or) > 0) {
			$where_or = '('.implode(' OR ', $where_or).')';
			if (count($where_and) > 0) {
				$where = $where_or.' AND ('.implode(' AND ', $where_and).')';
			} else {
				$where = $where_or;
			}
		} elseif( count($where_and) > 0) {
			$where = implode(' AND ', $where_and);
		}
		
		$events = DataObject::get('Event', $where, 'LastEdited DESC', 'LEFT JOIN AssociationOrganizer ON Event.OrganizerID = AssociationOrganizer.ID');
		
		if ($events && $events->Count() > 0)
			return $events->Count();	
		
		return 0;
	}	

	public function EditCategoriesForm() {
		$fields = new FieldSet(
			$DOM = new DialogDataObjectManager(
				$this,
				'Categories',
				'EventCategory',
				array(
					'Name' => _t('EventCategory.NAME', 'Name'),
					'AlwaysLast' => _t('EventCategory.ALWAYSLAST', 'Always last')
				),
				null,
				'EventCategory.Inactive = 0',
				'Name'
			)
		);
		
		$DOM->setFieldCasting(array(
			'AlwaysLast' => 'Boolean->Nice'
		));	
		
		$DOM->setAddTitle(_t('EventCategory.SINGULARNAME', 'Event categories'));
		$DOM->setPluralTitle(_t('EventCategory.PLURALNAME', 'Event category'));
		
		$actions = new FieldSet();
		
		return new Form($this, "EditCategoriesForm", $fields, $actions);
	}
	
	public function EditLocalesForm() {
		$fields = new FieldSet(
			$DOM = new DialogDataObjectManager(
				$this,
				'Languages',
				'CalendarLocale',
				array(
					'NiceName' => _t('CalendarLocale.NAME', 'Name'),
					'IsDefault' => _t('CalendarLocale.ISDEFAULT', 'Default'),
				)
			)
		);

		$DOM->setFieldCasting(array(
			'IsDefault' => 'Boolean->Nice'
		));			
		
		$DOM->setAddTitle(_t('CalendarLocale.SINGULARNAME', 'Calendar language'));
		$DOM->setPluralTitle(_t('CalendarLocale.PLURALNAME', 'Calendar languages'));
		
		$actions = new FieldSet();
		
		return new Form($this, "EditLocalesForm", $fields, $actions);
	}

	public function EditMunicipalsForm() {
		if (eCalendarExtension::isAdmin()) {
			$where = null;			
		} else {
			$where = "Municipal.ID IN ('".implode("','", PermissionExtension::getMyMunicipals())."')";
		}
		
		$fields = new FieldSet(
			$DOM = new DialogDataObjectManager(
				$this,
				'Municipals',
				'Municipal',
				array(
					'Name' => _t('Municipal.NAME', 'Name'),
					'AlwaysLast' => _t('EventCategory.ALWAYSLAST', 'Always last')
				),
				null,
				$where,
				'Name'
			)
		);
		
		$DOM->setFieldCasting(array(
			'AlwaysLast' => 'Boolean->Nice'
		));			
		
		$DOM->setAddTitle(_t('Municipal.SINGULARNAME', 'Municipal'));
		$DOM->setPluralTitle(_t('Municipal.PLURALNAME', 'Municipals'));
		
		$actions = new FieldSet();
		
		return new Form($this, "EditMunicipalsForm", $fields, $actions);
	}
		
	public function EditOrganizersForm_NotConfirmed() {
		return $this->EditOrganizersForm(null, null, "NotConfirmed");	
	}
	
	/*
	 * How many parameters are used by silverstripe I dont know for sure currently :/
	 */
	public function EditOrganizersForm($request = null, $dummy = null, $return = null) {	
		$where = array(); 
		$member = Member::currentUser();
		
		// Vilka föreningar är denna moderator i?
		if (!$this->isAdmin()) {				
			$where[] = "
				(
					AssociationPermission.AssociationID IN ('".implode("','", $this->getMyAssociations(null, 'organizers', true))."')
				OR 
					AssociationPermission.AssociationOrganizerID = '".$member->ID."'
				OR 
					CreatorID = '".$member->ID."'
				)";
		}
		
		
		switch ($return) {
			default:
				
			break;
			
			case 'NotConfirmed':
				$where[] = 'PermissionPublish = false';
			break;			
		}
				
		$fields = new FieldSet(
			$DOM = new DialogDataObjectManager(
				$this,
				'Organizers',
				'AssociationOrganizer',
				array(
					'PermissionPublishIcon' => _t('AssociationOrganizer.PERMISSIONPUBLISH', 'Can publish'),
					'FullName' => _t('Member.NAME', 'Full name'),
					'Email' => _t('Member.EMAIL', 'Email'),					
					'UserRoles' => _t('AssociationOrganizer.USERROLES', 'Roles'),	
					'CreatedNice' => _t('AssociationOrganizer.CREATED', 'Created'),
					'LastEditedNice' => _t('AssociationOrganizer.LASTEDITED', 'LastEdited'),
					'EmailVerifiedNice' => _t('AssociationOrganizer.EMAILVERIFIED', 'Email verified'),					
				),
				null,
				implode(' AND ', $where),
				'Created DESC',
				'LEFT JOIN AssociationPermission ON AssociationOrganizer.ID = AssociationPermission.AssociationOrganizerID'
			)
		);		
		
		$DOM->setAddTitle(_t('AssociationOrganizer.SINGULARNAME', 'Organizer'));
		$DOM->setPluralTitle(_t('AssociationOrganizer.PLURALNAME', 'Organizer'));
		
		$DOM->setColumnWidths(array(
			'PermissionPublishIcon' => '5',
			'FullName' => '16',
			'Email' => '20',
			'UserRoles' => '32',
			'CreatedNice' => '9',
			'LastEditedNice' => '9',
			'EmailVerifiedNice' => '9'
		));			
		
		switch ($return) {
			default:
				$filterWhere = '';
				if (!$this->isAdmin()) {				
					$filterWhere = "
					(
						AssociationPermission.AssociationID IN ('".implode("','", $this->getMyAssociations(null, 'organizers', true))."')
					OR 
						AssociationPermission.AssociationOrganizerID = '".$member->ID."'
					OR 
						CreatorID = '".$member->ID."'
					)";
				}
				$filterAssociations = DataObject::get('Association', $filterWhere, '', 'LEFT JOIN AssociationPermission ON Association.ID = AssociationPermission.AssociationID');
				if ($filterAssociations) {
					$values = array();
					foreach ($filterAssociations as $filterAssociation) {
						$values[$filterAssociation->ID] = $filterAssociation->Name;
					}
					$DOM->setFilter('AssociationPermission.AssociationID', _t('Association.SINGULARNAME', 'Association'), $values);
				}				
			break;
			
			case 'NotConfirmed':
				$DOM->removePermission('add');
			break;			
		}
					
		$actions = new FieldSet();
				
		$return = "EditOrganizersForm".($return?'_'.$return:'');
		return new Form($this, $return, $fields, $actions);
	}
	
	
	
	public function SendEmail($subject, $message, $to) {
		$from = IM_Controller::$default_email_address;
		// create mail
		// Ensure subject is correctly encoded
		$email = new Email($from, trim($to), '=?UTF-8?B?' . base64_encode($subject) . '?=', $message);
		// send mail
		try {
			$email->send();
			return true;
		}
		catch (Exception $e) {
			// silently catch email sending errors...
			return false;
		}
	}
		
	public function getReport() {
		if (!self::isAdmin()) return;
		
		$safeData = Convert::raw2sql($_GET);
		
		$pdf = (isset($safeData['PDF']) && $safeData['PDF'] === 'true') ? true : false;
		$type = isset($safeData['ReportType']) ? $safeData['ReportType'] : '';
		
		$startDate = empty($safeData['StartDate']) ? null : strtotime($safeData['StartDate']);
		$endDate = empty($safeData['EndDate']) ? null : strtotime($safeData['EndDate']);
		$teacherID = (int)$safeData['Teacher'];
		
		Requirements::clear();
			
		$response = new SS_HTTPResponse('Unknown report');
		$response->addHeader("Content-type", "text/html");
		return $response;
	}
	
	protected function UnreadMessages() {
		return IM_Controller::UnreadMessage();
	}
	
	public function InternalMessages() {	
		return new IM_Controller($this, 'InternalMessages');
	}	
	
	public function EventReportForm() {
		$report = new EventReport($this, 'EventReportForm');
		return $report;
	}
	
	public function DataExportForm() {
		if (!$this->showInNavigation("DataExport")) 
			return '';
		
		$report = new DataExport($this, 'DataExportForm');
		return $report;
	}	
	
	public function LogEntryForm() {
		if (!$this->showInNavigation("LogReport")) 
			return '';
		
		$fields = new FieldSet(
			$logDOM = new DialogDataObjectManager(
				$this,
				'LogEntries',
				'LogEntry',
				array(
					'NiceTime' => _t('LogEntry.DATE', 'Date'),
					'NiceUser' => _t('AssociationOrganizer.SINGULARNAME', 'User'),
					'NiceType' => _t('LogEntry.TYPE', 'Type'),
					'ItemID' => 'ID',
					'NiceAction' => _t('LogEntry.ACTION', 'Action'),
					'LogComment' => _t('LogEntry.COMMENT', 'Comment')
				),
				null,
				'',
				'ID DESC'
			)
		);
		
		$logDOM->removePermission('add');
		$logDOM->removePermission('edit');
		$logDOM->removePermission('remove');
		$logDOM->setPluralTitle(_t('LogEntry.PLURALNAME', 'Log entries'));
		
		$query = new SQLQuery('Type', array('LogEntry'), '', '', 'Type');
		$result = $query->execute();
		if ($result->numRecords()) {
			$values = array();
			
			foreach ($result as $row) {
				$values[$row['Type']] = _t($row['Type'] . '.SINGULARNAME', $row['Type']);
			}
			$logDOM->setFilter('LogEntry.Type', _t('LogEntry.TYPE', 'Type'), $values);
		}
		
		$logDOM->setColumnWidths(array(
			'NiceTime' => '15',
			'NiceUser' => '20',
			'NiceType' => '15',
			'ItemID' => '6',
			'NiceAction' => '10',
			'LogComment' => '34'
		));
		
		$actions = new FieldSet();
		
		return new Form($this, "LogEntryForm", $fields, $actions);
	}
	
	public function LogReportForm() {
		if (!$this->showInNavigation("LogReport")) 
			return '';
		
		$report = new LogReport($this, 'LogReportForm');
		return $report;
	}	

	public function eventPreview() {
		Requirements::clear();
		
		$inputData = $_POST;
		$inputLocale = 'en_US'; // Used to grab data from the current event, can be other than $previewLocale
		$previewLocale = 'en_US';	// Used for titles etc..
		
		// Languages
		$event['Languages'] = new DataObjectSet();
		if (!empty($inputData['Languages'])) {
			$explodedLanguages = explode(',', $inputData['Languages']);
			foreach ($explodedLanguages as $languageID) {
				$language = DataObject::get_by_id('CalendarLocale', (int)$languageID);
				if ($language)
					$event['Languages']->push($language);
			}
			$inputLocale = $event['Languages']->First()->Locale;
			$previewLocale = $inputLocale;
		}
		if (!empty($inputData['PreviewLanguage'])) {
			$languageID = $inputData['PreviewLanguage'];
			$language = DataObject::get_by_id('CalendarLocale', (int)$languageID);
			if ($language) {
				if ($event['Languages']->find('Locale', $language->Locale)) 
					$inputLocale = $language->Locale;

				$previewLocale = $language->Locale;
			}
		}
		
		$event = array();
		$event['Title'] = isset($inputData['Title_' . $inputLocale]) ? $inputData['Title_' . $inputLocale] : '';
		$event['EventTextShort'] = isset($inputData['EventTextShort_' . $inputLocale]) ? $inputData['EventTextShort_' . $inputLocale] : '';
		$event['EventText'] = isset($inputData['EventText_' . $inputLocale]) ? nl2br($inputData['EventText_' . $inputLocale]) : '';
		$event['Place'] = isset($inputData['Place_' . $inputLocale]) ? $inputData['Place_' . $inputLocale] : '';
		$event['PriceText'] = isset($inputData['PriceText_' . $inputLocale]) ? nl2br($inputData['PriceText_' . $inputLocale]) : '';
		$event['Homepage'] = isset($inputData['Homepage']) ? $inputData['Homepage'] : '';
		$event['ShowGoogleMap'] = isset($inputData['ShowGoogleMap']) ? 1 : 0;
		$event['GoogleMAP'] = isset($inputData['GoogleMAP']) ? $inputData['GoogleMAP'] : '';
		$event['GoogleMAPEncoded'] = urlencode($event['GoogleMAP']);
		
		// Sets locale to use for related objects
		i18n::set_locale($previewLocale);
		Translatable::set_current_locale($previewLocale);
		
		// Categories
		$event['Categories'] = new DataObjectSet();
		if (!empty($inputData['Categories'])) {
			$explodedCategories = explode(',', $inputData['Categories']);
			foreach ($explodedCategories as $categoryID) {
				$category = DataObject::get_by_id('EventCategory', (int)$categoryID);
				if ($category)
					$event['Categories']->push($category);
			}
		}
		// Organizer
		if (!empty($inputData['OrganizerID'])) {
			$organizer = DataObject::get_by_id('AssociationOrganizer', (int)$inputData['OrganizerID']);
			if ($organizer) 
				$event['Organizer'] = $organizer;
		}
		// Association
		if (!empty($inputData['AssociationID'])) {
			$association = DataObject::get_by_id('Association', (int)$inputData['AssociationID']);
			if ($association) {
				$event['Association'] = $association;
				if ($association->Logo())
					$event['AssociationLogo'] = $association->Logo()->PaddedImage(142, 80);
			}
		}		
		// Municipality
		if (!empty($inputData['MunicipalID'])) {
			$municipality = DataObject::get_by_id('Municipal', (int)$inputData['MunicipalID']);
			if ($municipality) 
				$event['Municipal'] = $municipality;
		}		
		// Event dates
		$event['Dates'] = new DataObjectSet();
		if (!empty($inputData['EventDates'])) {
			$datesArray = explode(',', $inputData['EventDates']);
			if (is_array($datesArray)) {	
				foreach ($datesArray as $date) {
					$explodedDate = explode(' ', $date);
					$explodedTimespan = explode('-', $explodedDate[1]);
					$dateID = $explodedDate[2];

					$date = $explodedDate[0];
					$start = $explodedTimespan[0];
					$end = $explodedTimespan[1];

					$eventDate = new EventDate();
					$eventDate->Date = $date;
					$eventDate->StartTime = $start;
					$eventDate->EndTime = $end;
				
					$event['Dates']->push($eventDate);
				}
			}
			
			$event['Dates']->sort('SortStartTime');
			$event['OtherDates'] = ($event['Dates']->Count() > 1) ? true : false;
		}
		// Images
		$event['Images'] = new DataObjectSet();
		if (!empty($inputData['EventImages']) && !empty($inputData['EventImages']['selected'])) {
			$imagesArray = explode(',', $inputData['EventImages']['selected']);
			if (is_array($imagesArray)) {
				foreach ($imagesArray as $imageID) {
					$image = DataObject::get_by_id('EventImage', (int)$imageID);
					if ($image && $image->Image() && $image->Image()->exists())
						$event['Images']->push($image->Image()->PaddedImage(180, 180));
				}
			}
		}
		
		// Files
		$event['Files'] = new DataObjectSet();
		if (!empty($inputData['EventFiles']) && !empty($inputData['EventFiles']['selected'])) {
			$filesArray = explode(',',$inputData['EventFiles']['selected']);
			if (is_array($filesArray)) {
				foreach ($filesArray as $fileID) {
					$file = DataObject::get_by_id('EventFile', (int)$fileID);
					if ($file && $file->File() && $file->File()->exists()) {
						if (!$file->OnlySelectedLocales || count($file->Locales("Locale = '$previewLocale'")))
							$event['Files']->push($file);
					}
				}
			}
		}	
		// Links
		$event['Links'] = new DataObjectSet();
		if (!empty($inputData['EventLinks']) && !empty($inputData['EventLinks']['selected'])) {
			$linksArray = explode(',', $inputData['EventLinks']['selected']);
			if (is_array($linksArray)) {
				foreach ($linksArray as $linkID) {
					$link = DataObject::get_by_id('EventLink', (int)$linkID);
					if ($link && (!$link->OnlySelectedLocales || count($link->Locales("Locale = '$previewLocale'"))))
						$event['Links']->push($link);
				}
			}
		}		
		
		if ($event['Files']->Count() > 0 || $event['Links']->Count() > 0) {
			$event['hasAttachments'] = true;
		} else {
			$event['hasAttachments'] = false;
		}		
		
		$data['Event'] = new ArrayData($event);	
		return singleton('ViewableData')->renderWith('EventPreview', $data);
	}
	
	protected function UnhandledRegistrations() {
		$unhandled = 0;
		
		$unhandled += $this->UnhandledEvents();
		$unhandled += $this->UnhandledAssociations();
		$unhandled += $this->UnhandledAssociationOrganizers();
		$unhandled += $this->UnhandledPermissionRequests();
		$unhandled += $this->UnhandledUserInviteRequests();
		
		return $unhandled;
	}
	
	protected function UnhandledEvents() {
		$where_and = array();
		$where_or = array();
		$where = null;

		$member = Member::currentUser();
		
		if (eCalendarExtension::isAdmin()) {
			
		} else {		
			// Vilka kommuner är denna admin i? 
			$mymunicipals = $this->getMyMunicipals();
			if (count($mymunicipals) > 0) {				
				$where_or[] = "Event.MunicipalID IN ('".implode("','", $mymunicipals)."')";
			}
			
			// Om en event i en förening som jag har tillgång till så kan man editera
			$myassociations = $this->getMyAssociations($member, 'organizers', true);
			$where_or[] = "Event.AssociationID IN ('".implode("','", $myassociations)."')";						
			$where_or[] = 'OrganizerID = '.$member->ID;				
			$where_or[] = 'AssociationOrganizer.CreatorID = '.$member->ID.'';
		} 		
		
		$where_and[] = "Event.Status = 'Preliminary'";
		$where_and[] = "Association.Status = 'Active'";

		if (count($where_or) > 0) {
			$where_or = '('.implode(' OR ', $where_or).')';
			if (count($where_and) > 0) {
				$where = $where_or.' AND ('.implode(' AND ', $where_and).')';
			} else {
				$where = $where_or;
			}
		} elseif( count($where_and) > 0) {
			$where = implode(' AND ', $where_and);
		}
		
		$events = DataObject::get('Event', $where, 'LastEdited DESC', 'LEFT JOIN AssociationOrganizer ON Event.OrganizerID = AssociationOrganizer.ID LEFT JOIN Association ON Event.AssociationID = Association.ID');
		
		if ($events && $events->Count() > 0)
			return $events->Count();
		
		return 0;
	}
	
	protected function UnhandledAssociations() {
		$where = array();		
		if (eCalendarExtension::isAdmin()) {
			
		} else {
			$member = Member::CurrentUser();
			$associationsids = PermissionExtension::getMyAssociations($member, 'moderators', true);			
			if ($member) {
				$where[] = "(Association.ID IN ('".implode("','", $associationsids)."') OR Association.CreatorID = " . (int)$member->ID . ')';
			} else { // overkill :)
				$where[] = "Association.ID = 0"; // Visa inga resultat
			}
			
		}	
			
		$where[] = "Association.Status = 'New'";
		
		$where = implode(' AND ', $where);
		
		$associations = DataObject::get('Association', $where, 'Name');

		if ($associations && $associations->Count() > 0)
			return $associations->Count();
		
		return 0;
	}
	
	protected function UnhandledPermissionRequests() {
		$where = array(); 
		$member = Member::currentUser();
		
		// Vilka föreningar är denna moderator i?
		if (!$this->isAdmin()) {				
			$where[] = "
				(
					PermissionRequest.AssociationID IN ('".implode("','", $this->getMyAssociations(null, 'moderators', true))."')
					OR PermissionRequest.UserID = '$member->ID'
				)";
		}
				
		$where[] = "PermissionRequest.Status = 'New'";
		
		$where = implode(' AND ', $where);
		
		$requests = DataObject::get('PermissionRequest', $where, 'Created');
		
		if ($requests && $requests->Count() > 0)
			return $requests->Count();
		
		return 0;
	}
	
	protected function UnhandledUserInviteRequests() {
		$where = array(); 
		$member = Member::currentUser();
		
		// Vilka föreningar är denna moderator i?
		if (!$this->isAdmin()) {				
			$where[] = "
				(
					UserInviteRequest.AssociationID IN ('".implode("','", $this->getMyAssociations(null, 'moderators', true))."')
					OR UserInviteRequest.UserID = '$member->ID'
				)";
		}
				
		$where[] = "UserInviteRequest.Status = 'New'";
		
		$where = implode(' AND ', $where);
		
		$requests = DataObject::get('UserInviteRequest', $where, 'Created');
		
		if ($requests && $requests->Count() > 0)
			return $requests->Count();
		
		return 0;
	}	
	
	protected function UnhandledAssociationOrganizers() {
		$where = array(); 
		$member = Member::currentUser();
		
		// Vilka föreningar är denna moderator i?
		if (!$this->isAdmin()) {				
			$where[] = "
				(
					AssociationPermission.AssociationID IN ('".implode("','", $this->getMyAssociations(null, 'organizers', true))."')
				OR 
					AssociationOrganizer.ID = '".$member->ID."'
				)";
		}
				
		$where[] = 'PermissionPublish = 0';
		
		$where = implode(' AND ', $where);
		
		$organizers = DataObject::get('AssociationOrganizer', $where, 'Created', 'LEFT JOIN AssociationPermission ON AssociationOrganizer.ID = AssociationPermission.AssociationOrganizerID');
		
		if ($organizers && $organizers->Count() > 0)
			return $organizers->Count();
		
		return 0;
	}	
	
	protected function FrontPageLinks() {
		$links = new DataObjectSet();
		$unreadMessages = $this->UnreadMessages();
		$unhandledRegistrations = $this->UnhandledRegistrations();
			
		if ($this->showInNavigation('Event')) {
			$links->push(new ArrayData(array(
				'Link' => $this->Link() . 'editevents',
				'LinkHeader' => _t('LinkEvents.HEADER', 'Events'),
				'LinkHelp' => _t('LinkEvents.HELP', 'You can add, edit, delete events from here.'),
				'LinkIcon' => 'ecalendar/images/link-events.png'
			)));	
		}
		
		if ($this->showInNavigation('Association')) {
			$links->push(new ArrayData(array(
				'Link' => $this->Link() . 'editassociations',
				'LinkHeader' => _t('LinkAssociations.HEADER', 'Associations'),
				'LinkHelp' => _t('LinkAssociations.HELP', 'You can add, view or edit associations from here.'),
				'LinkIcon' => 'ecalendar/images/link-associations.png'
			)));
		}
		
		if ($this->showInNavigation('AssociationOrganizer')) {
			$links->push(new ArrayData(array(
				'Link' => $this->Link() . 'editorganizers',
				'LinkHeader' => _t('LinkOrganizers.HEADER', 'Organizers'),
				'LinkHelp' => _t('LinkOrganizers.HELP', 'You can add, view or edit organizers from here.'),
				'LinkIcon' => 'ecalendar/images/link-users.png'
			)));		
		}
		
		if ($this->showInNavigation('Messages')) {		
			$links->push(new ArrayData(array(
				'Link' => $this->Link() . 'editmessages',
				'LinkHeader' => _t('LinkMessages.HEADER', 'Messages') . ($unreadMessages > 0 ? ' (' . $unreadMessages . ')' : ''),
				'LinkHelp' => _t('LinkMessages.HELP', 'You can send internal messages to members within all your associations. You can of course receive messages from them too.'),
				'LinkIcon' => 'ecalendar/images/link-messages.gif'
			)));		
		}
		
		if ($this->UnhandledRegistrations() && ($this->showInNavigation('AssociationOrganizer') || $this->showInNavigation('Association') || showInNavigation('Event'))) {
			$links->push(new ArrayData(array(
				'Link' => $this->Link() . 'handleregistrations',
				'LinkHeader' => _t('LinkUnhandledRegistrations.HEADER', 'Unhandled registrations') . ($unhandledRegistrations > 0 ? ' (' . $unhandledRegistrations . ')' : ''),
				'LinkHelp' => _t('LinkUnhandledRegistrations.HELP', 'From here you can see all newly registrered events, associations and users who need to get accepted or rejected.'),
				'LinkIcon' => 'ecalendar/images/link-unhandled.png'
			)));		
		}
			
		$links->push(new ArrayData(array(
			'Link' => $this->EditProfileLink(),
			'LinkClass' => 'popup-button',
			'LinkExtra' => 'alt="add" title="' . _t('eCalendarAdmin.EDITPROFILE', 'Edit profile') . '"',
			'LinkHeader' => _t('LinkEditProfile.HEADER', 'User profile'),
			'LinkHelp' => _t('LinkEditProfile.HELP', 'You can edit your user profile here.'),
			'LinkIcon' => 'ecalendar/images/link-editprofile.png'
		)));
		
		$member = Member::currentUser();
		if ($member && $member->is_a('AssociationOrganizer') && !$member->inGroup('eventadmins')) {
			$links->push(new ArrayData(array(
				'Link' => $this->ApplyForMembershipLink(),
				'LinkClass' => 'popup-button',
				'LinkExtra' => 'alt="add" title="' . _t('eCalendarAdmin.APPLYFORMEMBERSHIP','Apply for role in another association') . '"',
				'LinkHeader' => _t('LinkApplyMembership.HEADER', 'Apply for membership'),
				'LinkHelp' => _t('LinkApplyMembership.HELP', 'You can apply for membership in another association here. If the desired association doesn\'t exist, you can also create it from here.'),
				'LinkIcon' => 'ecalendar/images/link-applymembership.png'
			)));
		}
		
		return $links;
	}
	
	protected function IsReportsOpen() {
		if ($this->view == 'eventreport' || $this->view == 'logreport' || $this->view == 'dataexport')
			return true;
		return false;
	}
	
	protected function IsSystemSettingsOpen() {
		if ($this->view == 'editcategories' || $this->view == 'editmunicipals' || $this->view == 'editlanguages')
			return true;
		return false;
	}
	
	public function isEmailRegistered() {
		$json["valid"] = true;
		
		if (!isset($_POST['value']) || !isset($_GET['id'])) {
			$json["valid"] = false;
			$json["message"] = 'Email is missing';
		}
		else {
			$existing = DataObject::get_one('Member', "Member.Email = '" . Convert::raw2sql(strtolower($_POST['value'])) .  "' AND Member.ID != '" . (int)$_GET['id'] . "'");
			if ($existing) {
				$json["valid"] = false;
				
				if (eCalendarExtension::isAdmin($existing))
					$json["message"] = _t('RegistrationPage.EMAILALREADYEXISTS', 'This email address has already been registered');
				else
					$json["message"] = sprintf(_t('AssociationOrganizer.EMAILALREADYEXISTS_INVITE' , 'This email address has already been registered, click <a class="invite-user-link" href="%s" title="Invite user">here</a> if you want to invite the existing user to an association.'), Controller::join_links($this->UserInviteLink(), '?userID=' . $existing->ID));
			}
		}
		
		$response = new SS_HTTPResponse(json_encode($json));
		$response->addHeader("Content-type", "application/json");
		return $response;				
	}	
	
	public function viewNetTicketEmails() {
		if (!eCalendarExtension::isAdmin())
			return;
		
		$startDate = '2012-02-01';date('Y-m-d', time());
		$where = "Event.Status = 'Accepted' AND Association.Status = 'Active' AND EventDate.Date >= '$startDate' AND NetTicket_PublishTo = 1";
		$join = "LEFT JOIN EventDate ON (EventDate.EventID = Event.ID AND EventDate.Date >= '$startDate') LEFT JOIN Association ON Association.ID = Event.AssociationID";
		
		$netticketEvents = DataObject::get('Event', $where, '', $join);
		
		if ($netticketEvents) {
			$origLocale = i18n::get_locale();
			
			echo '
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.1//EN" "http://www.w3.org/TR/xhtml11/DTD/xhtml11.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
<meta http-equiv="Content-type" content="text/html; charset=utf-8" />
<meta http-equiv="Content-language" content="fi_FI" /></head><body>';
			
			i18n::set_locale('sv_SE');
			
			foreach ($netticketEvents as $event) {
				if ($event->End <= date('Y-m-d H:i:s'))
					continue;
				
				$origLocale = i18n::get_locale();

				echo singleton('DataObject')->renderWith('NetTicket_EmailNotification', array(
					'Organizer' => $event->Organizer(), 
					'Association' => $event->Association(),
					'Event' => $event
				));
/*				$email = new Email();
				$email->setTemplate('NetTicket_EmailNotification');
				$email->populateTemplate(array(
					'Organizer' => $this->Organizer(), 
					'Association' => $this->Association(),
					'Event' => $this
				));
				$email->setTo(self::$NetTicket_EmailAddress);
				$email->setSubject('=?UTF-8?B?' . base64_encode(_t('NetTicket_EmailNotification.SUBJECT', 'Tickets for an event') . ' "' . $this->Title . '"') . '?=');
				if (IM_Controller::$default_email_address != '')
					$email->setFrom(IM_Controller::$default_email_address);

				try {
					@$email->send();
				}
				catch (Exception $e) {
					// silently catch email sending errors...
				}*/
			
				//$event->onPublish();
				//echo 'Send email for event ' . $event->ID . '<br/>';
			}
			
			echo '</body></html>';
			
			i18n::set_locale($origLocale);					
		}
		else
			echo 'No events';
	}
}
		
