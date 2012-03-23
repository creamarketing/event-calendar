<?php

class DatetimeFieldEx extends DatetimeField {
	
	public function __construct($name, $title = null, $value = "") {
		parent::__construct($name, $title, $value);
		
		$this->dateField = new DateFieldEx($name . '[date]', false);
		$this->timeField = new TimeFieldEx($name . '[time]', false);		
		
		$this->addExtraClass('datetime');
	}

	function Field() {
		
		Requirements::block(SAPPHIRE_DIR . '/css/DatetimeField.css');
		Requirements::css('ecalendar/css/ExtendedFields/DatetimeFieldEx.css');
		
		return parent::Field();
		
	}
	
}

?>
