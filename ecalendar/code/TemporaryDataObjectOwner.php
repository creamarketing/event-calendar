<?php

class TemporaryDataObjectOwner extends DataObjectDecorator {
	function extraStatics() {
		return array(
			'db' => array(
				'TemporaryDataObjectOwnerID' => 'Int'
			)
		);
	}
	
	function populateDefaults() {
		$this->owner->TemporaryDataObjectOwnerID = Member::currentUserID();
	}
}

?>
