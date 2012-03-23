<?php
class CalendarLoginForm extends MemberLoginForm {

    public function dologin($data) {
		$responseStatus = 200;
		
		$member = $this->performLogin($data);
		
		if ($member) {
			$this->controller->redirect('admin/ecalendar');
		}
		else {
			$messageArray = explode(';', $this->Message());
			if (count($messageArray)) {
				$this->sessionMessage($messageArray[count($messageArray)-1], 'bad');
			}
			$this->controller->redirectBack();
		}
    }	
	
	public function forgotPassword($data) {
		i18n::set_locale(Session::get('LostPasswordForm_Locale'));
		return parent::forgotPassword($data);
	}
}
?>