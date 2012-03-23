<?php

class DOSPaginationExtension extends Extension {
   function extraStatics() {        
		return array(            
			'db' => array(
			),            
			'has_one' => array(
				), 
			);    
	}	
	
	public function Pagination() {
		$pageLimits = $this->owner->getPageLimits();
		$items = $this->owner->toArray();
		$items = array_slice($items, $pageLimits["pageStart"], $pageLimits["pageLength"]);
		return new DataObjectSet($items);     
	}
}

?>
