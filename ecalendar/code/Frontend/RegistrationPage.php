<?php 
class RegistrationPage extends Page {	
	function MenuTitle() {
		$title = parent::getMenuTitle();
		
		if (Member::currentUserID()) 
			return _t('RegistrationPage.MENUTITLE_LOGGEDIN', 'My events');

		return $title;
	}
}

class RegistrationPage_Controller extends Page_Controller {
	static $extensions = array(
		'CreaDataObjectExtension',
		'eCalendarExtension'
	);
	
	static $allowed_actions = array(
		'VerifyEmail',
		'isEmailValid',
		'lostpassword',
		'passwordsent',
		'changepassword',
		'passwordchanged'
	);
	
	public function ControllerLink() {
		$page = Translatable::get_one_by_lang('SiteTree', i18n::get_locale(), "ClassName = '" . str_replace('_Controller', '', $this->class) . "'");
 		if (!$page) {
			$page = DataObject::get_one('' . str_replace('_Controller', '', $this->class) . '');
		}
		
		if (!$page) {
			return '';
		}
		
		if ($page->Locale != Translatable::get_current_locale()) {
			$page = $page->getTranslation(Translatable::get_current_locale());
	  }
		
	  return $page->Link();
	}	
				
	public function OrganizerLoggedIn() {
		$this->redirect('admin/ecalendar');
	}
	
	public function RegisterCalendarOrganizer($data, $form) {
	
		// Important: escape all data before doing any queries!
		$sqlData = Convert::raw2sql($data);
		
		// Important: don't allow setting the ID!
		if(isset($sqlData['ID'])) {
			$form->sessionMessage(_t('RegistrationPage.IDNOTALLOWED', 'Registration failed! (ID not allowed)'), 'bad');
			return $this->redirectBack();
		}
			
		// Important: safety-check that there is no existing member with this email adress!
		if($member = DataObject::get_one("Member", "`Email` = '". $sqlData['Email'] . "'")) {
			$form->sessionMessage(sprintf(_t('Member.VALIDATIONMEMBEREXISTS', 'A member already exists with the same %s'), _t('Member.EMAIL', 'e-mail address')), 'bad');
			return $this->redirectBack();		
		}
		else {
			$member = new AssociationOrganizer();
			$member->PermissionPublish = false;
			$member->EmailVerified = null; // Just in case someone tries some form manipulation			
			$form->saveInto($member);
			$member->Locale = Translatable::get_current_locale();
	  		$member->write();						
	  		$member->write(); // write twice so we get the Hash written						
			AssociationOrganizer::sendVerificationEmail($member);
			
			// --------------------------------------------- // 
			
			// Register a new association?
			if (isset($sqlData['RegisterNewAssociation']) && isset($sqlData['Organization'])) {				
				/*if (isset($_POST['AssociationID'])) // Hack to prevent validation error on permission write
					unset($_POST['AssociationID']);
				$_POST['Type'] = 'Moderator';*/
				
				foreach (Translatable::get_allowed_locales() as $locale) {
					$assocName[$locale] = $data['Organization']['Name_'.$locale];
					if (strlen($assocName[$locale]) && !isset($assocName['default_name']))
						$assocName['default_name'] = $assocName[$locale];
				}
				$assocType = $data['Organization']['Type'];
				$assocPostalAddress = $data['Organization']['PostalAddress'];
				$assocPostalCode = $data['Organization']['PostalCode'];
				$assocPostalOffice = $data['Organization']['PostalOffice'];
				$assocPhone = $data['Organization']['Phone'];
				$assocEmail = $data['Organization']['Email'];
				$assocHomepage = $data['Organization']['Homepage'];
				$assocMunicipalID = $data['Organization']['MunicipalID'];
								
				/*$logo = new Image();
				$logo->loadUploadedImage($data['OrganizationLogoID']);
				$logo->write();*/
				
				$association = new Association();
				
				if(isset($data['OrganizationLogoID'])) {
					if($file = DataObject::get_by_id("File", (int) $data['OrganizationLogoID'])) {
						$file->ClassName = 'AssociationLogo';
						$file->write();
						
						$association->LogoID = $file->ID;
					}
				}
				
				foreach (Translatable::get_allowed_locales() as $locale) {
					if (strlen($assocName[$locale]))
						$association->setField('Name_' . $locale, $assocName[$locale]);
					else
						$association->setField('Name_' . $locale, $assocName['default_name']);
				}
				$association->CreatorID = $member->ID; // OBS
				$association->Type = $assocType;
				$association->PostalAddress = $assocPostalAddress;
				$association->PostalCode = $assocPostalCode;
				$association->PostalOffice = $assocPostalOffice;
				$association->Phone = $assocPhone;
				$association->Email = $assocEmail;
				$association->Homepage = $assocHomepage;
				$association->MunicipalID = $assocMunicipalID;
				$association->Status = 'New';
				$association->write(); 
				$association->write(); // Must write twice to generate accept/reject links
				
				// This is done in association instead
				/*$perm = new AssociationPermission();
				$perm->AssociationOrganizerID = $member->ID;
				$perm->AssociationID = $association->ID;
				$perm->Type = 'Moderator';
				$perm->write();*
				
				$association->AssociationPermissions()->add($perm);
				$association->write();
				
				$member->AssociationPermissions()->add($perm);
				$member->write();*/
			}
			else if (!empty($sqlData['AssociationID'])) {
				$perm = new AssociationPermission();
				$perm->AssociationOrganizerID = $member->ID;
				$perm->AssociationID = (int)$data['AssociationID'];
				$perm->Type = 'Organizer';
				$perm->write();
				
				$member->AssociationID = (int)$data['AssociationID'];				
				$member->AssociationPermissions()->add($perm);
				$member->write();
			}
						
			$form->sessionMessage(_t('RegistrationPage.REGISTRATIONSUCCESSFUL', 'Registration successful'), 'good');
			$this->redirectBack();
		}
	}
		
	public function TryVerifyMessage() {
		if (!isset($_GET['Verify'])) {
			return '';
		} else {
			switch ($_GET['Verify']) {
				case 'ok':					
					return _t('RegistrationPage.VERIFYSUCCESS', 'Verification of emailaddress done, you can now start working.');
				break;
				
				case 'ok2':
					return _t('RegistrationPage.VERIFYSUCCESS2', 'Verification of emailaddress has already been done.');
				break;
				
				case 'fail':
					return _t('RegistrationPage.VERIFYFAIL', 'Verification of emailaddress failed, the link seems to be incorrect.');
				break;
			}
		}
	}
	
	public function isEmailValid() {
		$json["valid"] = true;
		
		if (!isset($_POST['value'])) {
			$json["valid"] = false;
			$json["message"] = 'Email is missing';
		}
		else {
			$existing = DataObject::get_one('Member', "Email = '" . Convert::raw2sql(strtolower($_POST['value'])) .  "'");
			if ($existing) {
				$json["valid"] = false;
				$json["message"] = _t('RegistrationPage.EMAILALREADYEXISTS' , 'This email address has already been registered');
			}
		}
		
		$response = new SS_HTTPResponse(json_encode($json));
		$response->addHeader("Content-type", "application/json");
		return $response;				
	}		
	
	public function LostPasswordForm() {
		$security = new CalendarSecurity();
		$security->findRegistrationPage();
		return $security->LostPasswordForm();
	}
	
	public function ChangePasswordForm() {
		$security = new CalendarSecurity();
		$security->findRegistrationPage();
		return $security->ChangePasswordForm();
	}
	
	public function login() {
		return $this->renderWithThemePage('login');
	}
	
	public function lostpassword() {
		Requirements::javascript('ecalendar/javascript/RegistrationPage_lostpassword.js');
		return $this->renderWithThemePage('lostpassword');
	}
	
	public function passwordsent() {
		$this->PasswordSentToEmail = Session::get('LostPassword_passwordSentToEmail');
		return $this->renderWithThemePage('passwordsent');
	}
	
	public function passwordchanged() {
		return $this->renderWithThemePage('passwordchanged');
	}
	
	public function changepassword() {
		Requirements::javascript('ecalendar/javascript/RegistrationPage_changepassword.js');
		if(Session::get('AutoLoginHash')) {
			$this->AutoLoginHash = true;
		} elseif(Member::currentUser()) {
			$this->MemberChangePassword = true;
		}
		else {
			if(Session::get('AutoLoginHash') == false) {
				$this->InvalidAutoLoginHash = true;
				$this->InvalidAutoLoginHashText = sprintf(
							_t('Security.NOTERESETLINKINVALID',
								'<p>The password reset link is invalid or expired.</p><p>You can request a new one <a href="%s">here</a> or change your password after you <a href="%s">logged in</a>.</p>'
							),
							$this->Link('lostpassword'),
							$this->Link()
				);
			}
		}
		
		return $this->renderWithThemePage('changepassword');
	}
	
	function SwitchToLogin() {
		Requirements::customScript('SwitchToLogin();');
	}
	
	function init() {
		parent::init();    
		Requirements::themedCSS('RegistrationPage');
		Requirements::customScript('var checkValidEmailURL = "' . Controller::curr()->Link() . '";');
		
		Validator::set_javascript_validation_handler('none');
		
		Requirements::javascript(THIRDPARTY_DIR . '/jquery/jquery.js');		
		Requirements::javascript(THIRDPARTY_DIR . '/jquery-cookie/jquery.cookie.js');
		Requirements::javascript('dialog_dataobject_manager/javascript/jquery.valid8.min.js');
		Requirements::javascript('ecalendar/javascript/jquery.numeric.js');

		Requirements::javascript(SAPPHIRE_DIR . '/javascript/i18n.js');
		Requirements::add_i18n_javascript('ecalendar/javascript/Pages_lang');		
		
		Requirements::javascript('ecalendar/javascript/RegistrationPage.js');
			
		Requirements::clear(THIRDPARTY_DIR . '/prototype/prototype.js');
		Requirements::block(THIRDPARTY_DIR . '/prototype/prototype.js');
		
		Requirements::clear(THIRDPARTY_DIR . '/behaviour/behaviour.js');
		Requirements::block(THIRDPARTY_DIR . '/behaviour/behaviour.js');
		
		Requirements::clear(SAPPHIRE_DIR . '/javascript/ConfirmedPasswordField.js');
		Requirements::block(SAPPHIRE_DIR . '/javascript/ConfirmedPasswordField.js');
		
		Requirements::clear(SAPPHIRE_DIR . '/javascript/prototype_improvements.js');
		Requirements::block(SAPPHIRE_DIR . '/javascript/prototype_improvements.js');
		
		// We must use 1.8.16 here, otherwise we can't position autocomplete dropdown
		Requirements::javascript('dialog_dataobject_manager/javascript/jquery-ui-1.8.16.custom.min.js');
		Requirements::block(THIRDPARTY_DIR . '/jquery-ui/jquery-ui-1.8rc3.custom.js');
		Requirements::block(THIRDPARTY_DIR . '/jquery-ui/jquery.ui.autocomplete-1.8rc3-mod.js');
		Requirements::block(THIRDPARTY_DIR . '/jquery-ui/jquery.ui.dialog.js');
		Requirements::block(THIRDPARTY_DIR . '/jquery-ui/jquery.ui.position.js');
		
		Session::set('RegistrationPage_Controller_Locale', Translatable::get_current_locale());
	}
	
}

?>