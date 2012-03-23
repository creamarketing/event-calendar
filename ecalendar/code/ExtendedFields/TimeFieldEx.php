<?php

class TimeFieldEx extends TimeField {
	
	protected $useInputMask = true;
	
	public function __construct($name, $title = null, $value = "") {
		parent::__construct($name, $title, $value);
		
		$this->setConfig('showdropdown', false);
		$this->setConfig('timeformat', 'HH:mm');		
		
		$this->addExtraClass('time');
		
		$this->setMaxLength(5);
	}
	
	function Field() {
		Requirements::javascript('coursebooking/javascript/jquery.maskedinput-1.3.min.js');
		
		$html = parent::Field();
		
		$customJS = <<<JS
		
		jQuery(document).ready(function() {
			jQuery.mask.definitions['h']='[0-2]';
			jQuery.mask.definitions['m']='[0-5]';
			jQuery('#{$this->id()}').mask('h9:m9', {
				completed: function() {
					var regex=/^(2[0-3])|[01][0-9]:[0-5][0-9]$/;
					if (!regex.test(this.val())) {
						this.addClass('invalid-input-time');
					}
					else {
						this.removeClass('invalid-input-time');
					}
				}
			});
		});
		
JS;
		
		Requirements::customScript($customJS);
		
		return $html;
	}	
}

?>
