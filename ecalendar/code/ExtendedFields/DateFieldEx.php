<?php

class DateFieldEx extends DateField {
	public function __construct($name, $title = null, $value = null, $form = null, $rightTitle = null) {
		parent::__construct($name, $title, $value, $form, $rightTitle);
		
		$this->setConfig('showcalendar', true);
		$this->setConfig('dateformat', 'dd.MM.YYYY');
		$this->setConfig('minDate', null);
		
		$this->addExtraClass('date');
		
		$this->setMaxLength(10);
	}
	
	function Field() {
		Requirements::javascript('ecalendar/javascript/jquery.maskedinput-1.3.min.js');
		
		Requirements::block(SAPPHIRE_DIR . "/javascript/DateField.js");
		Requirements::javascript('ecalendar/javascript/ExtendedFields/DateFieldEx.js');
				
		$html = parent::Field();
		
		if ($this->getConfig('showcalendar') === false) {
		$customJS = <<<JS
		
		jQuery(document).ready(function() {
			jQuery.mask.definitions['d']='[0-3]';
			jQuery.mask.definitions['m']='[0-1]';
			jQuery.mask.definitions['M']='[0-2]';
			jQuery('#{$this->id()}').mask('d9.mM.9999', {
				completed: function() {
					var regex=/^([0-2][0-9]|3[01]).(0[0-9]|1[0-2]).[0-9]{4}$/;
					if (!regex.test(this.val())) {
						this.addClass('invalid-input-date');
					}
					else {
						this.removeClass('invalid-input-date');
					}
				}
			});
		});
		
JS;
			
		Requirements::customScript($customJS);
		}
		
		return $html;
	}
	
	function FieldHolder() {
		// TODO Replace with properly extensible view helper system 
		$d = Object::create('DateField_View_JQueryEx', $this); 
		$d->onBeforeRender(); 
		$html = TextField::FieldHolder(); 
		$html = $d->onAfterRender($html); 
		
		return $html;
	}	
}

class DateField_View_JQueryEx extends DateField_View_JQuery {
	
	function onBeforeRender() {
		if($this->getField()->getConfig('showcalendar')) {
			// Inject configuration into existing HTML
			$format = self::convert_iso_to_jquery_format($this->getField()->getConfig('dateformat'));
			$minDate = $this->getField()->getConfig('minDate');
			$conf = array(
				'showcalendar' => true,
				'dateFormat' => $format
			);
			if ($minDate != null)
				$conf['minDate'] = $minDate;			
			$this->getField()->addExtraClass(str_replace('"', '\'', Convert::raw2json($conf)));
		}
	}	
}

?>
