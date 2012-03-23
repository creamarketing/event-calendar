<?php

class NumericFieldEx extends NumericField {
	protected $defaultZeroValue = 0;
	
	function validate($validator){
		
		$this->setValue(str_replace(',', '.', $this->value));
		
		if($this->value && !is_numeric(trim($this->value))){
 			$validator->validationError(
 				$this->name,
				sprintf(
					_t('NumericField.VALIDATION', "'%s' is not a number, only numbers can be accepted for this field"),
					$this->value
				),
				"validation"
			);
			return false;
		} else{
			return true;
		}
	}	
	
	public function setDefaultZeroValue($value) {
		$this->defaultZeroValue = $value;
	}
	
	function dataValue() {
		return (is_numeric($this->value)) ? $this->value : $this->defaultZeroValue;
	}	
}
?>
