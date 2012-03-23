<?php

class CalendarSecurity extends Security {
	protected $registrationPageExists = false;
	
	public function login() {	// Force redirect to calendar login form
		if ($this->registrationPageExists)
			$this->redirect(singleton('RegistrationPage_Controller')->ControllerLink());
		else
			return parent::login();
	}
	
	function init() {
		parent::init();
		
		$this->findRegistrationPage();
	}
	
	function findRegistrationPage() {
		$this->registrationPageExists = strlen(singleton('RegistrationPage_Controller')->ControllerLink()) ? true : false;
		
		if ($this->registrationPageExists) {
			if (strlen(Session::get('RegistrationPage_Controller_Locale'))) {
				i18n::set_locale(Session::get('RegistrationPage_Controller_Locale'));
				Translatable::set_current_locale(Session::get('RegistrationPage_Controller_Locale'));
			}		
		}
	}
	
	/**
	 * Show the "lost password" page
	 *
	 * @return string Returns the "lost password" page as HTML code.
	 */
	public function lostpassword() {
		if (!$this->registrationPageExists)
			return parent::lostpassword();
			
		$this->redirect(Controller::join_links(singleton('RegistrationPage_Controller')->ControllerLink(), 'lostpassword'));
	}	
	
	/**
	 * Factory method for the lost password form
	 *
	 * @return Form Returns the lost password form
	 */
	public function LostPasswordForm() {
		if (!$this->registrationPageExists)
			return parent::LostPasswordForm();
		
		Session::set('LostPasswordForm_Locale', Translatable::get_current_locale());
		return new CalendarLoginForm(
			$this,
			'LostPasswordForm',
			new FieldSet(
				new EmailField('Email', _t('Member.EMAIL', 'Email'))
			),
			new FieldSet(
				new FormAction(
					'forgotPassword',
					_t('Security.BUTTONSEND', 'Send me the password reset link')
				)
			),
			false
		);
	}
	
	/**
	 * Show the "password sent" page, after a user has requested
	 * to reset their password.
	 *
	 * @param SS_HTTPRequest $request The SS_HTTPRequest for this action. 
	 * @return string Returns the "password sent" page as HTML code.
	 */
	public function passwordsent($request) {
		if (!$this->registrationPageExists)
			return parent::passwordsent($request);
		
		Session::set('LostPassword_passwordSentToEmail', Convert::raw2xml($request->param('ID') . '.' . $request->getExtension()));
		
		$this->redirect(Controller::join_links(singleton('RegistrationPage_Controller')->ControllerLink(), 'passwordsent'));
	}	
	
	/**
	 * Show the "change password" page.
	 * This page can either be called directly by logged-in users
	 * (in which case they need to provide their old password),
	 * or through a link emailed through {@link lostpassword()}.
	 * In this case no old password is required, authentication is ensured
	 * through the Member.AutoLoginHash property.
	 * 
	 * @see ChangePasswordForm
	 *
	 * @return string Returns the "change password" page as HTML code.
	 */	
	public function changepassword() {
		if (!$this->registrationPageExists)
			return parent::changepassword();
		
		// First load with hash: Redirect to same URL without hash to avoid referer leakage
		if(isset($_REQUEST['h']) && Member::member_from_autologinhash($_REQUEST['h'])) {
			// The auto login hash is valid, store it for the change password form.
			// Temporary value, unset in ChangePasswordForm
			Session::set('AutoLoginHash', $_REQUEST['h']);
		}
		else
			Session::set('AutoLoginHash', false);
		return $this->redirect(Controller::join_links(singleton('RegistrationPage_Controller')->ControllerLink(), 'changepassword'));
	}
	
	public function ChangePasswordForm() {
		if (!$this->registrationPageExists)
			return parent::ChangePasswordForm();
		
		return new CalendarPasswordForm($this, 'ChangePasswordForm');
	}	
}

?>
