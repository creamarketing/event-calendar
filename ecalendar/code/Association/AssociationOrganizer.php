<?php

class Member_AssociationOrganizer extends DataObjectDecorator {
	public function canLogIn($result) {					
		if ($result->valid() && $this->owner instanceof AssociationOrganizer && !eCalendarExtension::isAdmin()) {
					
			// Cant login with a unverified email
			if (!strlen($this->owner->EmailVerified)) {
				$result->error( sprintf( 
						_t('RegistrationPage.CHECKYOURMAIL', 'You have registered for Ostrobothnia Eventcalendar but not yet verified your e-mail address. Please check your emails and click the link in the message with the subject "<strong>%s</strong>".'),
						eCalendarExtension::getEmailVerifySubject()
					)
				);
			} /*else { // Dont want to show messages more than once			
				// Cant login if we have registered to a new association, until that one is accepted and if we have only one association
				$permissions = $this->owner->AssociationPermissions();
				if ($permissions && $permissions->Count() == 1) {
					foreach ($permissions as $perm) {
						$association = $perm->Association();
						if ($association && $association->Status == 'New') {
							$result->error(sprintf(_t ('RegistrationPage.ERRORPENDINGASSOCIATION',
													   "You cannot log into your account at this moment.\n<strong>'%s'</strong> has not yet been confirmed by an moderator/administrator."), 
											$association->Name));
						}
					}		
				}
			}*/
		}
		
		return $result;
	}
}
	
class AssociationOrganizer_ForgotPasswordEmail extends Member_ForgotPasswordEmail {
    protected $from = '';  // setting a blank from address uses the site's default administrator email
    protected $subject = '';
    protected $ss_template = 'AssociationOrganizer_ForgotPasswordEmail';
    
    function __construct() {
		parent::__construct();
    	
    }
	
	public function send($messageID = null) {
		if ($this->template_data->Locale)
			i18n::set_locale($this->template_data->Locale);
		
		$this->setSubject('=?UTF-8?B?' . base64_encode(_t('Member.SUBJECTPASSWORDRESET', "Your password reset link")) . '?=');
		
		return parent::send($messageID);
	}
}

class AssociationOrganizer extends Member {
	
	static $extensions = array(
		'PermissionExtension',
		'CreaDataObjectExtension'
	);
	
	static $db = array(
		'PermissionPublish' => 'Boolean',		
		'Phone' => 'Varchar(25)',
		'Hash' => 'Varchar(80)',
		'EmailVerified' => 'SS_Datetime',
		'WillBeDeleted' => 'Date',		
		'SystemAdmin' => 'Boolean'
	);
	
	static $has_one = array(		
		'ModeratorVerified' => 'AssociationOrganizer',
		'Creator' => 'Member',		
	);
	
	static $has_many = array(
		'Events' => 'Event',
		'AssociationPermissions' => 'AssociationPermission',
		'Notifications' => 'Notification',
		'PermissionRequests' => 'PermissionRequest',
		'UserInviteRequests' => 'UserInviteRequest'
	);
	
	static $many_many = array(			
		'MunicipalPermissions' => 'Municipal',	
	);
	
	
	static $searchable_fields = array(
	);
	
	static $defaults = array(
		'PermissionPublish' => false,
		'SystemAdmin' => false
	);
	
	static $summary_fields = array(
	  'CreatedNice',	
	  'FirstName',
	  'Surname',
	  'LastEditedNice'
  );
	
	static $default_sort = 'Surname, FirstName';
	public $CallerClass = null;	
		
	public function canPublish() {
		return $this->PermissionPublish;
	}
	
 	public function getCreatedNice() {
  		return date('d.m.Y H:i', strtotime($this->Created));
 	}
  
	public function getLastEditedNice() {
		return date('d.m.Y H:i', strtotime($this->LastEdited));
	}
	
	public function getEmailVerifiedNice() {
		return $this->EmailVerified?date('d.m.Y H:i', strtotime($this->EmailVerified)):'-';
	}
  
	public function getPermissionPublishIcon() {		
		$ico_url = ($this->PermissionPublish?'ecalendar/images/user-comment-green.gif':'ecalendar/images/user-comment-red.gif');	
		$img_alt = ($this->PermissionPublish?_t('AssociationOrganizer.ACCEPTED_YES', 'Yes'):_t('AssociationOrganizer.ACCEPTED_NO', 'No'));
		
		$html = "<img src=\"$ico_url\" border=\"0\" alt=\"$img_alt\"> ".$img_alt;
		return $html;
	}
	
	public function getDOMTitle() {
  		return mb_strtolower(_t('AssociationOrganizer.SINGULARNAME', 'Organizer')).' `'.$this->Name.'´';
	}
	
	static public function toDropdownList() {
		$associationorg_objs = DataObject::get('AssociationOrganizer');
		$associationorgs = array();
		if ($associationorg_objs)
			foreach ($associationorg_objs as $associationorg) {
				$associationorgs[$associationorg->ID] = $associationorg->getNameWithAssociation();
			}
		return $associationorgs;
  	}
  	
	public function forTemplate() {
		return $this->getNameWithAssociation();
	}
  	
	public function getUserRoles() {
		$permissions = $this->AssociationPermissions(null, "if(Type = 'Moderator', 0, 1), Created");
		$municipalpermissions = $this->MunicipalPermissions();
		$li = array();
		foreach ($municipalpermissions as $municipal) {
			$li[] = '<li class="mmoderator"> + '.sprintf(_t('AssociationOrganizer.MUNICIPALMODERATORFOR', 'Moderator for "%s"'),$municipal->Name).'</li>';
		}
		
		foreach ($permissions as $permission) {
			if ( $permission->Type == 'Moderator' ) {
				$li[] = '<li class="amoderator"> + '.sprintf(_t('AssociationOrganizer.MODERATINGFOR', 'Moderating for "%s"'),$permission->Association()->Name).'</li>';
			} elseif ( $permission->Type == 'Organizer' ) {
				$li[] = '<li class="aorganizer"> + '.sprintf(_t('AssociationOrganizer.ORGANIZINGFOR', 'Organizing for "%s"'),$permission->Association()->Name).'</li>';
			}
		}
		
		if (eCalendarExtension::isAdmin($this)) {
			$li[] = '<li class="aadministrator">'._t('AssociationOrganizer.ADMINISTRATOR', 'Administrator').'</li>';
		}
		
		if (count($li) == 0){
			$li[] = '<li>'._t('AssociationOrganizer.NOROLES', 'None').'</li>';
		}
		
		return '<ul class="userroles">'.implode("\n", $li).'</ul>';		
	}
	
	public function getAssociation() {
		$permissions = $this->AssociationPermissions(null, "if(Type = 'Moderator', 0, 1), Created");
		if ($permissions) {
			$permission = $permissions->First();
			if ($permission) {
				return $permission->Association();
			}		
		}			
		return singleton('Association');
	}
	
	public function getNameWithAssociation() {
		if ($this->getAssociation()->ID) {
			return $this->getFullName(). ' - '.$this->getAssociation()->getNameHierachy(false);
		} else {
			return $this->getFullName().'';
		}
	}
	
	
	public function getRequirementsForPopup() {
		Requirements::css('ecalendar/css/eCalendarAdmin.css');	
		$this->extend('getRequirementsForPopup');
		
		Requirements::customScript('jQuery(function() { 
			jQuery(\'label[for="Password-_Password"]\').append(\' <em>*</em>\');
			jQuery(\'label[for="Password-_ConfirmPassword"]\').append(\' <em>*</em>\');
		});');
		
		Requirements::customCSS('
			#TabSet_AssociationPermissionsTab .only-related-control { display: none }
			#TabSet_AssociationPermissionsTab .data .actions.col input[type=checkbox] { display: none; }
			#TabSet_AssociationPermissionsTab .permission-not-editable { background-color: #FF8080; }
		');
		
		$member = Member::currentMember();
		if (eCalendarExtension::IsMunicipalModerator() || eCalendarExtension::isAdmin() || $this->ID == 0 || (!$this->NumVisit && $this->CreatorID == $member->ID)) {
			Requirements::javascript('dialog_dataobject_manager/javascript/jquery.valid8.min.js');
			Requirements::javascript('dialog_dataobject_manager/javascript/jquery.forceredraw-1.0.3.js');
			
			Requirements::add_i18n_javascript('ecalendar/javascript/Pages_lang');
			Requirements::javascript('ecalendar/javascript/AssociationOrganizer.js');
			Requirements::customCSS('/* Validation */
				.error { }
				.error input { background-color: #FFF0F0 !important; border: 1px dashed #FF8080 !important; }
				.error textarea { background-color: #FFF0F0 !important; border: 1px dashed #FF8080 !important; }
				.error span.validationMessage { display: true; }

				.valid { }
				.valid input { }
				.valid span.validationMessage { display: true; }');
		}
	}
	
	
	public function getCMSFields() {			
		// För tillfället fungerar inte DialogDataObjectManagern i den vy som finns i bl.a. Security
		if (Director::CurrentPage() == 'SecurityAdmin') {
			$parentfields = parent::getCMSFields();
				
			$parentfields->removeByName( "Events" );
			$parentfields->removeByName( "AssociationPermissions" );
			$parentfields->removeByName( "MunicipalPermissions" );
			$parentfields->removeByName( "Hash" );
			$parentfields->removeByName( "ModeratorVerifiedID" );
			$parentfields->removeByName( "Creator" );
			$parentfields->removeByName( "EmailVerified" );
			$parentfields->removeByName( "PermissionPublish" );
			$parentfields->removeByName( "PermissionRequests" );
			$parentfields->removeByName( "UserInviteRequests" );
			$parentfields->removeByName( "Notifications" );
			
			return $parentfields;
		}
		else if (substr($_GET['url'], -9) == 'myprofile') {
			// only simple cms fields for my profile
			$fields = $this->getStandardFields();
			$fields->removeByName('DateFormat');
			$fields->removeByName('TimeFormat');
			$this->extend('updateCMSFields', $fields);		
			return $fields;
		}
			
		$stfields = $this->getStandardFields();
		
		// Add * to required fields
		$tmpFields = new DataObjectSet();
		$tmpFields->push($stfields->fieldByName('FirstName'));
		$tmpFields->push($stfields->fieldByName('Surname'));
		$tmpFields->push($stfields->fieldByName('Email'));
		foreach ($tmpFields as $tmpField) 
			$tmpField->setTitle($tmpField->Title() . ' <em>*</em>');
		
		// remove date and time format fields and locale fields, not needed here
		$stfields->removeByName('DateFormat');
		$stfields->removeByName('TimeFormat');

		$member = Member::currentMember();
		
		$fields = new FieldSet($DTSet = new DialogTabSet('TabSet'));				

		$generalTab = new Tab('GeneralTab',	_t('AssociationOrganizer.GENERALTAB', 'General'));
		$generalTab->setChildren( $stfields );
			
		if ($this->ID == $member->ID) { // Break here, cant set own permissions
			$DTSet->push($generalTab);
			$this->extend('updateCMSFields', $fields);		
			return $fields;
		}
		else if (eCalendarExtension::IsMunicipalModerator() || eCalendarExtension::isAdmin() || $this->ID == 0 || (!$this->NumVisit && $this->CreatorID == $member->ID)) {
			$DTSet->push($generalTab);
		}
				
		/* Permissions in Associations ------------------------------------------------->>> */		
		$where_only_aso = "AssociationPermission.AssociationOrganizerID = '".$this->ID."' AND (AssociationPermission.AssociationOrganizerID != 0 OR AssociationPermission.TemporaryDataObjectOwnerID = " . Member::currentUserID() . ")";
		
		$dbmgr = new DialogHasManyDataObjectManager(
			$this, 
			'AssociationPermissions', 
			'AssociationPermission', 
			array(
				'NiceType' => _t('Association.TYPE', 'Type'),						
				'Association.Name' => _t('Association.NAME', 'Name'),			
			),
			null,
			$where_only_aso,
			"Type ASC",
			'LEFT JOIN Association ON AssociationPermission.AssociationID = Association.ID'
		);	
		
		if (!$this->canEdit()) {
			$dbmgr->removePermission('add');	
		}
		$dbmgr->setMarkingPermission(false);
		$dbmgr->setOnlyRelated(true);
		
		$dbmgr->setHighlightConditions(array(
			array(
				"rule" => '$IsEditableByUser() == true',
				"class" => 'permission-editable',
			),
			array(
				"rule" => '$IsEditableByUser() == false',
				"class" => 'permission-not-editable',
			),
		));			
		
		$DTSet->push(				
			$associationPermissionsTab = new Tab(
				'AssociationPermissionsTab', 
				_t('AssociationOrganizer.ASSOCIATIONPERMISSIONSTAB', 'Permissions in following organizations')
			)
		);		
		
		if ($this->ID != $member->ID && (eCalendarExtension::isAdmin($member) || $this->PermissionPublish == false)) {
			// Check Permission publish if we are adding a new user
			if ($this->ID == 0 && eCalendarExtension::isAdminPage())
				$this->PermissionPublish = true;
			
			$associationPermissionsTab->push(
				new CheckboxField(
						'PermissionPublish', 
						'<b>'._t('AssociationOrganizer.CANPUBLISHDIRECTLY', 'Can publish directly').'</b><br /><br />'
				)
			);
		}		
		
		$associationPermissionsTab->push( $dbmgr );	
		/* <<<--------------------------------------------------------------------------- */
		
		/* Permissions in Municipals ------------------------------------------------->>> */		
		$where_only_mun = null;		
		$showMunicipals = false;	
		if (eCalendarExtension::isAdmin()) {
			$showMunicipals = true;		
		} else {
		// Vilka kommuner är denna kommunadmin i?
			if (count($this->getMyMunicipals())) {
				$where_only_mun = "Municipal.ID IN ('".implode("','", $this->getMyMunicipals())."')";
				$showMunicipals = true;	
			} else {
				$where_only_mun = 'Municipal.ID = 0'; // RETURN NO MUNICIPALS IMPORTANT!! 
			}
		}
		
		if ( $showMunicipals == true ) {
			$dbmgr = new DialogManyManyDataObjectManager(
				$this, 
				'MunicipalPermissions', 
				'Municipal',
				null,
				null,
				$where_only_mun
			);		

					
			$DTSet->push(					
				$municipalPermissionsTab = new Tab(
					'MunicipalPermissionsTab', 
					_t('AssociationOrganizer.MUNICIPALPERMISSIONSTAB', 'Admin in following municipals')
				)
			);
			
			$municipalPermissionsTab->push( $dbmgr );	
		}
		
		/* <<<--------------------------------------------------------------------------- */
		
		
		
		$this->extend('updateCMSFields', $fields);
		
		return $fields;
	}
	
	/*
	 * Ment to be used only in CMS but lets see..
	 */
	function validate() {
		$data = Convert::raw2sql($_POST);
		
		$requiredFields = array(			
			'FirstName' => _t('Member.FIRSTNAME', 'FirstName'),
			'Surname' => _t('Member.SURNAME', 'Surname'),
			'Email' => _t('Member.EMAIL', 'Email')
		);
		
		foreach ($requiredFields as $key => $value) {
			if (isset($data[$key]) && strlen($data[$key]) < 1) {
				return new ValidationResult(false, sprintf(_t('DialogDataObjectManager.FILLOUT', 'Please fill out %s'), _t($value, $value)));
			}
		}		

		if ( !empty($data['PermissionPublish']) && !eCalendarExtension::isAdmin() && $this->AssociationPermissions()->exists()) {
			$myusers = PermissionExtension::getMyUsers(null, 'moderators', true);
			if (!in_array($this->ID, $myusers['All']) || $this->canPublish() == false) {
				return new ValidationResult(false, 'Not allowed to set the permission for selected user.');
			}			
		}
		
		if ( !empty($data['MunicipalPermissions']) && !eCalendarExtension::isAdmin() ) {
			$selected = isset($data['MunicipalPermissions']['selected']) ? $data['MunicipalPermissions']['selected'] : '';
			$exploded = explode(',', $selected);
			$mymunicipals = $this->getMyMunicipals();
			
			foreach ($exploded as $municipalId) {
				if (is_numeric($municipalId))
					if (!in_array($municipalId, $mymunicipals)) {
						return new ValidationResult(false, 'Not allowed to set the permission for all selected Municipals.');
					}
			}
		}
					
		return parent::validate();
	}	
	
	public function onBeforeWrite() {		
		try {
			parent::onBeforeWrite();
		}
		catch(ValidationException $e) {
			$identifierField = self::$unique_identifier_field;
			// if the unique identifier does not exist on the original, and if the original only contains 3 keys (ID, ClassName and RecordClassName),
			// it means this object was just saved temporarily when creating a new object (for making relation-saving work).
			// In this case, just delete this empty object
			if (!array_key_exists($identifierField, $this->original) && count($this->original) == 3) {
				$this->delete();
			}
			throw new ValidationException(new ValidationResult(false, _t('RegistrationPage.EMAILALREADYEXISTS', 'This email address has already been registered')));
		}
		
		$member = Member::CurrentUser();	
		if (!$this->Hash && $this->ID > 0) {
			$this->Hash = md5($this->ID.strtolower($this->Email).'--'.strtotime($this->Created));
		}
		
		if (eCalendarExtension::isBackWebRequest() && $this->EmailVerified == '' && $this->PermissionPublish) {
			$this->EmailVerified = date('Y-m-d H:i:s');	
		}
		
		if ($this->ID == 0 && $member) {
			$this->CreatorID = $member->ID;
		}
		
		if ( $this->isChanged('PermissionPublish', 2) ) {
			$changedFields = $this->getChangedFields(true, 2);
			
			if ($changedFields['PermissionPublish']['before'] == true && $changedFields['PermissionPublish']['after'] == false) {

			}
			else if ($changedFields['PermissionPublish']['before'] == false && $changedFields['PermissionPublish']['after'] == true) {		
				
			}
			
			/*
			if ( trim($changedFields['Email']['before']) != trim($changedFields['Email']['after']) ) {
				$this->sendVerificationEmail($member);
			}
			*/
			if ( $member ) { // when accepting member from LINK without logging in it is not set :/
				$this->ModeratorVerifiedID = $member->ID;
			}
		}		
	}
		
	public function getEmailVerifySubject() {
		return 
		_t('RegistrationPage.VERIFYEMAILSUBJECT', 'Ostrobothnia Evencalendar - VERIFY EMAIL');	
	}	
	
	public function sendVerificationEmail($member = null) {
		if (!$member) {
			$member = &$this;
		}
		
		// Send email with verification mail to the user
		$to = $member->Email;
		$subject = $this->getEmailVerifySubject();
		$message = _t('RegistrationPage.VERIFYEMAILMESSAGE', 
			"Click following link to verify your email adress and start using our admin"				
		).
		"<br /><br />\n\r".
		'<a href="'.$this->Link().'VerifyEmail?email='.urlencode($member->Email).'&hash='.urlencode($member->Hash).'">'._t('RegistrationPage.VERIFYMYEMAIL', 'Verify my e-mailaddress').'</a>';
		
		// We dont want website errors cause of a stupid address, but not on all servers the same problem (fixing not with @)
		/*
		 * [Warning] mail() [function.mail]: SMTP server response: 550 5.1.1 <asf@creamarketing.com>: Recipient address rejected: User unknown in local recipient table
		 */
		try {			
			@eCalendarAdmin::SendEmail($subject, $message, $to);
		} catch (Exception $e) {

		}	
		
	}
	
	public function write() {
		if ($this->CallerClass != 'NotificationTask') {
			$this->WillBeDeleted = null;
		}
		parent::write();
	}
	
	public function onAfterWrite() {
		parent::onAfterWrite();
		
		$member = Member::CurrentUser();
		
		// Om e-post verifierad
		$condition1 = !empty($this->EmailVerified);
		// eller om man är en administrator / moderator som lägger till användare
		$condition2 = eCalendarExtension::IsBackWebRequest();
		
		if ( (!$this->inGroup('eventusers') && !$this->inGroup('eventadmins') && !$this->SystemAdmin) && ($condition1 || $condition2) ) {
			$memberGroup = DataObject::get_one('Group', "Code = 'eventusers'");
			
			/* Skapar gruppen om den inte finns! */
			if (!$memberGroup) {
				$memberGroup = new Group();
				$memberGroup->Code = 'eventusers';
				$memberGroup->Title = 'Event calendar users';
				$memberGroup->write();
				Permission::grant($memberGroup->ID, 'CMS_ACCESS_eCalendarAdmin');
			}
			 
			// Lägger personen till gruppen
			$memberGroup->Members()->add($this);
		}
		
		if ($this->isChanged('Email', 2) && eCalendarExtension::IsBackWebRequest()) {
			$changedFields = $this->getChangedFields(true, 2);
			if (!strlen($changedFields['Email']['before']) && strlen($changedFields['Email']['after'] != '')) {
				eCalendarExtension::SendMemberWelcomeMessageAfterAdd($this);
			}
		}
		
		if ( $this->isChanged('PermissionPublish', 2) ) {
			$changedFields = $this->getChangedFields(true, 2);
			
			if ($changedFields['PermissionPublish']['before'] == true && $changedFields['PermissionPublish']['after'] == false) {
				$originalLocale = i18n::get_locale();
				$currentLocale = $this->Locale; 
				i18n::set_locale($currentLocale);			
				$subject = _t('eCalendarAdmin.EMAIL_PUBLISHING_RIGHTS_SUBJECT', 'Publishing rights');
				$body = _t('eCalendarAdmin.EMAIL_CANNOTPUBLISH_BODY', 'You are no longer able to publish events directly');

				$msg = new IM_Message();
				$msg->ToID = $this->ID;
				$msg->FromID = 0;
				$msg->Subject = $subject;
				$msg->Body = $body;
				$msg->send(false);		
				i18n::set_locale($originalLocale);
			}
			else if ($changedFields['PermissionPublish']['before'] == false && $changedFields['PermissionPublish']['after'] == true) {
				$originalLocale = i18n::get_locale();
				$currentLocale = $this->Locale; 
				i18n::set_locale($currentLocale);			
				$subject = _t('eCalendarAdmin.EMAIL_PUBLISHING_RIGHTS_SUBJECT', 'Publishing rights');
				$body = _t('eCalendarAdmin.EMAIL_CANPUBLISH_BODY', 'You have been verified and can publish events directly');

				$msg = new IM_Message();
				$msg->ToID = $this->ID;
				$msg->FromID = 0;
				$msg->Subject = $subject;
				$msg->Body = $body;
				$msg->send(false);		
				i18n::set_locale($originalLocale);
				
				// Accept also the Associations
				$permissions = $this->AssociationPermissions("Type = 'Moderator'");
				if ($permissions) {
					foreach ($permissions as $permission) {
						$association = $permission->Association();
						if ($association && $association->Status == 'New') {
							$association->setField('Status', 'Active');
							$association->write();
						}
					}
				}				
				
				// Publish all our events
				$unpublishedEvents = $this->Events("Status = 'Preliminary'");
				if ($unpublishedEvents) {
					foreach ($unpublishedEvents as $event) {
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

	public function getFullName() {
		return $this->FirstName . ' ' . $this->Surname;
	}
			
	function getValidator() {
		return null;
	}
	
	public static function OrganizerLoginForm($controller) {
		return new CalendarLoginForm($controller, 'OrganizerLoginForm');
	}
	
	protected function getStandardFields() {
		$member = new Member();
		$fields = $member->getMemberFormFields();
		
		$fields->insertBefore(new TextField('Phone', _t('AssociationOrganizer.PHONE', 'Phone number')), 'Email');
		
		$fields->replaceField('Email', $email = new EmailField('Email', _t('Member.EMAIL', 'Email')));
		
		$fields->replaceField('Password', $password = new ConfirmedPasswordField (
			'Password',
			_t('Member.PASSWORD', 'Password'),
			null,
			null,
			isset($this->ID) ? true : false
		));
		$password->setCanBeEmpty(false);
		$password->requireStrongPassword = true;
		$password->minLength = 6;
		$password->maxLength = 40;
		if(isset($this->ID) && !$this->ID) $password->showOnClick = false;
		
		$locales = CalendarLocale::toLocaleDropdownList();
		
		$fields->insertBefore(
			new DropdownField('Locale', _t('AssociationOrganizer.NATIVELANGUAGE', 'Native language'), $locales, i18n::get_locale()),
			'IM_Inbox_EmailNotification'
		);
		
		return $fields;
	}
	
	public function OrganizerRegistrationForm($controller) {		
		$fields = self::getStandardFields();
		
		// remove date and time format fields and locale filled, not needed here
		$fields->removeByName('DateFormat');
		$fields->removeByName('TimeFormat');
		//$fields->removeByName('Locale');
				
		$associationArray = Association::toDropdownList(false);
						
		$fields->insertAfter(
				$associationDropdownField = new AdvancedDropdownField(
						'AssociationID',
						_t('RegistrationPage.ASSOCIATION', 'Association for'),
						$associationArray
				),
				'Password' 
		);
		
		$associationDropdownField->setDropdownPosition(array('my' => 'left bottom', 'at' => 'left top'));
		
		$fields->insertAfter( 
				new CheckboxField(
					'RegisterNewAssociation', 
					_t('Association.REGISTERNEWASSOCIATION', 'Register new association')
				),
				'AssociationID' 
		);		
		
		$ocontainerField = new FieldGroup();
		$ocontainerField->setID('NameGroup');
		$ocontainerField->addExtraClass('translationGroup');
		foreach (Translatable::get_allowed_locales() as $locale) {
			$ocontainerField->push( new TextField(	
						'Organization[Name_'.$locale.']', 
						i18n::$common_locales[$locale][1])
			);
		}
		Requirements::customCSS('.translationGroup .fieldgroupField label {display: block;}
						  .translationGroup .fieldgroupField input {width: 99% !important;}'
		);
		
		$fields->insertAfter(
				$associationDetailsGrp = new FieldGroup(
					new LabelField('AssociationName', _t('Association.NAME', 'Name')),
					$ocontainerField,
					new OptionsetField('Organization[Type]', _t('Association.TYPE', 'Type'), 
						array(
							'Association' => _t('Association.TYPE_ASSOCIATION', 'Association'), 
							'Company' => _t('Association.TYPE_COMPANY', 'Company'),
							'Other' => _t('Association.TYPE_OTHER', 'Other'),
						), 'Association'
					),
					new TextField(
						'Organization[PostalAddress]', 
						_t('Association.POSTALADDRESS', 'Address')
					),
					new NumericField(
						'Organization[PostalCode]', 
						_t('Association.POSTALCODE', 'Postal code')
					),						
					new TextField(
						'Organization[PostalOffice]', 
						_t('Association.POSTALOFFICE', 'Postal office')
					),	
					new AdvancedDropdownField(
						'Organization[MunicipalID]',
						_t('Municipal.SINGULARNAME', 'Municipal'),
						Municipal::toDropdownList()
					),						
					new TextField(
						'Organization[Phone]', 
						_t('Association.PHONE', 'Phone')
					),							
					new EmailField(
						'Organization[Email]',
						_t('Association.EMAIL', 'Email')
					),
					new TextField(
						'Organization[Homepage]', 
						_t('Association.HOMEPAGE', 'Homepage')
					),
					$imgUpload = new ImageUploadField('OrganizationLogo', _t('Association.LOGO', 'Logo'))
				),
				'RegisterNewAssociation' 
		);		
		
		$associationDetailsGrp->addExtraClass('association-details');		
		$associationDetailsGrp->setID('OrganizationGroup');
					
		// Terms
		if($tacPage = DataObject::get_one('OrganizerTermsPage')) {
			$readConditionsField = new CheckboxField('ReadConditions', sprintf(_t("AssociationOrganizer.ACCEPTTERMS", "I agree to the terms and conditions stated on the <a href=\"%s\" title=\"Read the terms and conditions for this site\" target=\"_blank\">\"%s\"</a> page"), $tacPage->URLSegment, $tacPage->Title));
		} else {
			$readConditionsField = new HiddenField('ReadConditions', '', '1');
		}
		
		$fields->push(
			$readConditionsField
		);
		
		// Remove fields used by internal messaging
		$fields->removeByName('IM_Inbox');
		$fields->removeByName('IM_Sentbox');
		$fields->removeByName('IM_Trashbox');
		$fields->removeByName('IM_Inbox_EmailNotification');
		
		// Info boxes
		$fields->insertBefore(new LiteralField('', '<div class="registration-info-box">' . _t('RegistrationPage.REGISTERINFO1', 'Your account information will only not be visible on the public page.') . '</div>'), 'FirstName');
		$fields->insertAfter(new LiteralField('', '<div class="registration-info-box">' . _t('RegistrationPage.REGISTERINFO2', 'Choose the organizer for whom you will be publishing events from the dropdown list. If you cannot find the desired organizer in the list then click the checkbox below to register a new organizer.') . '</div>'), 'Password');
		
		$actions = new FieldSet(
			new FormAction('RegisterCalendarOrganizer', _t('RegistrationPage.REGISTERBUTTON', 'Register'))
		);
		
		$requirements = new AssociationOrganizer_Validator();
			
		$form = new Form($controller, "OrganizerRegistrationForm", $fields, $actions, $requirements);
		$form->setRedirectToFormOnValidationError(true);
		return $form;
	}	

	protected function onBeforeDelete() {
		parent::onBeforeDelete();

		// REMOVE MunicipalPermission associated with this AssociationOrganizer
		$this->MunicipalPermissions()->removeAll();			
		
		// delete Associations connected to the member if they are empty
		$permissions = $this->AssociationPermissions();
		if ($permissions) {
			foreach ($permissions as $permission) {	
				$association = $permission->Association();
				// If dublicate permissions prevented it should only be one permission per user 
				// and if no other users than this one count should return 1
				if ( $association->AssociationPermissions()->Count() < 2 ) {
					$association->delete();
				}
			}
		}
		
		// delete AssociationPermission associated with this AssociationOrganizer
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
		
		// delete Events associated with this AssociationOrganizer
		if ($this->Events()) {
			foreach ($this->Events() as $event) {
				$event->delete();
			}
		}	
	}	
	
	public function onLogCreate($logItem) {
		$logItem->AddChangedField('FirstName', '', $this->FirstName, 'Member.FIRSTNAME');
		$logItem->AddChangedField('Surname', '', $this->Surname, 'Member.SURNAME');
		$logItem->AddChangedField('Phone', '', $this->Phone, 'Member.PHONE');
		$logItem->AddChangedField('Email', '', $this->Email, 'Member.EMAIL');
		$logItem->AddChangedField('PermissionPublish', '', ($this->PermissionPublish ? '1' : '0'), 'AssociationOrganizer.PERMISSIONPUBLISH');
	}
	
	public function onLogEdit($logItem) {
		$hasRealChanges = false;
		
		$changedFields = $this->getChangedFields(true, 2);
		if (isset($changedFields['FirstName'])) {
			$logItem->AddChangedField('FirstName', $changedFields['FirstName']['before'], $changedFields['FirstName']['after'], 'Member.FIRSTNAME');
			$hasRealChanges = true;
		}
		if (isset($changedFields['Surname'])) {
			$logItem->AddChangedField('Surname', $changedFields['Surname']['before'], $changedFields['Surname']['after'], 'Member.SURNAME');		
			$hasRealChanges = true;
		}
		if (isset($changedFields['Phone'])) {
			$logItem->AddChangedField('Phone', $changedFields['Phone']['before'], $changedFields['Phone']['after'], 'AssociationOrganizer.PHONE');
			$hasRealChanges = true;
		}
		if (isset($changedFields['Email'])) {
			$logItem->AddChangedField('Email', $changedFields['Email']['before'], $changedFields['Email']['after'], 'Member.EMAIL');
			$hasRealChanges = true;
		}
		if (isset($changedFields['Password'])) {
			$logItem->AddChangedField('Password', '********', '********', 'Member.PASSWORD');
			$hasRealChanges = true;
		}
		if (isset($changedFields['PermissionPublish'])) {
			$logItem->Comment = 'eCalendarAdmin.EMAIL_PUBLISHING_RIGHTS_SUBJECT';
			$logItem->write();			
			$logItem->AddChangedField('PermissionPublish', $changedFields['PermissionPublish']['before'], $changedFields['PermissionPublish']['after'], 'AssociationOrganizer.PERMISSIONPUBLISH');
			$hasRealChanges = true;
		}
		
		return $hasRealChanges;
	}
}

class AssociationOrganizer_Validator extends RequiredFields {
	protected $customRequired = array(
		'FirstName',		
		'Surname', 
	 	'Email',
		'Password'
	);

	function php($data) {
		$valid = parent::php($data);
		
		$identifierField = Member::get_unique_identifier_field();
		
		$SQL_identifierField = Convert::raw2sql($data[$identifierField]);
		$member = DataObject::get_one('Member', "\"$identifierField\" = '{$SQL_identifierField}'");

		// if we are in a complex table field popup, use ctf[childID], else use ID
		if(isset($_REQUEST['ctf']['childID'])) {
			$id = $_REQUEST['ctf']['childID'];
		} elseif(isset($_REQUEST['ID'])) {
			$id = $_REQUEST['ID'];
		} else {
			$id = null;
		}

		// Removed if ($id && !
		if( is_object($member) && $member->ID != $id) {
			$uniqueField = $this->form->dataFieldByName($identifierField);
			$this->validationError(
				$uniqueField->id(),
				sprintf(
					_t(
						'Member.VALIDATIONMEMBEREXISTS',
						'A member already exists with the same %s'
					),
					strtolower($identifierField)
				),
				'required'
			);
			$valid = false;
		}
		
		$check_locales = array_flip(Translatable::get_allowed_locales());
		$atleastOneName = false;
		
		foreach ($check_locales as $locale => $dummy) {
			if (empty($data['AssociationID']) && strlen(trim($data['Organization[Name_'.$locale.']'])) >= 2) {
				$atleastOneName = true;
			}
		}
		
		if (empty($data['AssociationID']) && !$atleastOneName) {
			$this->validationError(
				'Organization[Name_'.$locale.']', 
				sprintf(
					_t('DialogDataObjectManager.FILLOUT', 'Please fill out %s'), 
					_t('Association.NAME', 'Name')
				),
				'required'
			);				
		}
		
		if (empty($data['AssociationID']) && empty($data['Organization[MunicipalID]'])) {
			$this->validationError(
				'Organization[MunicipalID]', 
				sprintf(
					_t('DialogDataObjectManager.FILLOUT', 'Please fill out %s'), 
					_t('Municipal.SINGULARNAME', 'Municipality')
				),
				'required'
			);					
		}
		
		if (empty($data['AssociationID']) && empty($data['RegisterNewAssociation'])) {
			$this->validationError(
				'RegisterNewAssociation', 
				sprintf(
					_t('DialogDataObjectManager.FILLOUT', 'Please fill out %s'), 
					_t('Association.SINGULARNAME', 'Organizer')
				),
				'required'
			);				
		}
		
		if (empty($data['ReadConditions']) || $data['ReadConditions'] != 1) {
			$this->validationError(
				'ReadConditions',
				_t('RegistrationPage.TERMSREQUIRED', 'You have to accept the terms and conditions!'),				
				'required'
			);
			$valid = false;
		}
		
		// Execute the validators on the extensions
		if($this->extension_instances) {
			foreach($this->extension_instances as $extension) {
				if(method_exists($extension, 'hasMethod') && $extension->hasMethod('updatePHP')) {
					$valid &= $extension->updatePHP($data, $this->form);
				}
			}
		}

		return $valid;
	}
	/**
	 * Constructor
	 */
	public function __construct() {
		$required = func_get_args();
		if(isset($required[0]) && is_array($required[0])) {
			$required = $required[0];
		}
		$required = array_merge($required, $this->customRequired);

		parent::__construct($required);
	}
}

?>
