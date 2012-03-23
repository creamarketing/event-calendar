<?php

class ImageExtension extends Extension {
    function extraStatics() {        
		return array(            
			'db' => array(
			),            
			'has_one' => array(
				), 
			);    
	}
	
				
	function AbsoluteLink() {
		return Director::absoluteURL($this->owner->Link());
	}	
}

?>
