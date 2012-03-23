<?php
class Notification extends DataObject {
	static $extensions = array(	
		'PermissionExtension',	
	);

	static $db = array(	
		'Type' => 'Enum("Unconfirmed", "")',	
	);
	
	static $has_one = array(
		'Member' => 'Member',		
		'AboutAssociationOrganizer' => 'AssociationOrganizer',
	);

	
}

?>