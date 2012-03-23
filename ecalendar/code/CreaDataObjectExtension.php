<?php 
class CreaDataObjectExtension extends Extension {
	public function extraStatics() {
		
	}	
	
	public function getRequirementsForPopup() {
		Requirements::css('ecalendar/css/DialogCommon.css');
	}	
	
	public function getArrayForObject($object, $sort, $where = null, $field = 'Name') {
		if (!class_exists($object)) {
			return 'NO SUCH OBJECT';
		} 
				
		$dbset = DataObject::get($object, $where, $sort);
		if ($dbset) {
			$map = $dbset->map('ID', $field);	
		} else {
			$map = array();
		}
		$map = array('' => '') + $map;		
		return $map;
	}

	/*
	 * Fixing translation for fields automaticly
	 */
	function fieldLabels(&$labels) {		
    // add a translation to the $labels array for each db field
    // if $includerelations == true (and it normally is) you need to add 
    // the db_ prefix, cause that's what SS will look for
    foreach($this->owner->stat('db') as $key => $value) {    	
      $labels[$key] = _t($this->owner->class.".".strtoupper($key), $key);
    }

    return $labels;
  }
  
  function isDOMAddForm($prefix="") {
		$url = Controller::curr()->getRequest()->getURL();
		if (preg_match('/' . $prefix . '\/add$/', $url))
			return true;
		return false;
  }
  
  function isDOMEditForm() {
		$url = Controller::curr()->getRequest()->getURL();
		if (preg_match('/edit$/', $url))
			return true;
		return false;	  
  }

  function isDOMDuplicateForm() {
		$url = Controller::curr()->getRequest()->getURL();
		if (preg_match('/duplicate$/', $url))
			return true;
		return false;	  
  }  
}