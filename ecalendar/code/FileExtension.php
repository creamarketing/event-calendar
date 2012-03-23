<?php

class FileExtension extends Extension {
    function extraStatics() {        
		return array(            
			'db' => array(
			),            
			'has_one' => array(
				), 
			);    
	}
	
				
	function DuplicateFile() {
		$originalFile = $this->owner;
		
		$classname = $originalFile->class;
		$duplicateFile = new $classname();
		$duplicateFile->Title = $originalFile->Title;
		$duplicateFile->ParentID = $originalFile->ParentID;
		
		$newName = $originalFile->Name;
		$ext = File::get_file_extension($newName);
		$base = basename(pathinfo($newName, PATHINFO_BASENAME), '.' . $ext);
		$suffix = 1;
		while(DataObject::get_one("File", "\"Name\" = '" . Convert::raw2sql($newName) . "' AND \"ParentID\" = " . (int)$originalFile->ParentID)) {
			$suffix++;
			$newName = "$base-$suffix.$ext";
		}		
		
		$duplicateFile->setName($newName);
		$duplicateFile->write();
		
		if (!file_exists($duplicateFile->getFullPath()))
			copy($originalFile->getFullPath(), $duplicateFile->getFullPath());
			
		return $duplicateFile;
	}	
}

?>
