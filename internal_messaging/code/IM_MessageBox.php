<?php

class IM_MessageBox extends DataObject {
	static $db = array(
		'Type' => 'Enum("Inbox,Sentbox,Trashbox", "Inbox")'
	);
	
	static $has_one = array(
		'Owner' => 'Member'
	);
	
	static $has_many = array(
		'Messages' => 'IM_Message'
	);
	
	protected function onBeforeDelete() {
		parent::onBeforeDelete();
		
		$messages = $this->Messages();
		if ($messages) {
			foreach ($messages as $message)
				$message->delete();
		}
	}
}

?>
