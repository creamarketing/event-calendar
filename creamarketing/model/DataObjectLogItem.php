<?php

class DataObjectLogItem extends DataObject {
	
	static $db = array(
		'ObjectClass' => 'Varchar(128)',
		'Time' => 'SS_Datetime',
		'Type' => 'Enum("Created,Edited,Deleted","Created")'
	);
	
	static $has_one = array(
		'Object' => 'DataObject',
		'Member' => 'Member'
	);
	
	static $has_many = array(
		'FieldChanges' => 'DataObjectLogItem_FieldChange'
	);
	
	public function NiceTime() {
		return date('d.m.Y H:i', strtotime($this->Time));
	}
	
	public function LogText() {
		$text = '';
		if ($this->getObject()->hasMethod('getLogText')) {
			$text = $this->getObject()->getLogText($this->Type);
		}
		if ($this->Type == 'Created') {
			if (!$text) {
				$text = _t($this->ObjectClass.'.SINGULARNAME', $this->ObjectClass) . ' ' . _t('DataObjectLogItem.CREATED', 'Created');
			}
		}
		else if ($this->Type == 'Edited') {
			if (!$text) {
				$text = _t($this->ObjectClass.'.SINGULARNAME', $this->ObjectClass) . ' ' . _t('DataObjectLogItem.EDITED', 'Edited, changed fields: ');
			}
			$fieldCount = 0;
			if ($this->FieldChanges() && $this->FieldChanges()->Count() > 0) {
				foreach ($this->FieldChanges() as $fieldChange) {
					if ($fieldCount > 0) {
						$text .= ', ';
					}
					$text .= $fieldChange->NiceFieldName();
					$fieldCount++;
				}
			}
		}
		else if ($this->Type == 'Deleted') {
			$text = _t($this->ObjectClass.'.SINGULARNAME', $this->ObjectClass) . ' ' . _t('DataObjectLogItem.DELETED', 'Deleted');
		}
		return $text;
	}
	
	public function getObject() {
		$object = DataObject::get_by_id($this->ObjectClass, (int)$this->ObjectID);
		return $object;
	}
	
}

class DataObjectLogItem_FieldChange extends DataObject {
	
	static $db = array(
		'FieldName' => 'Varchar(64)',
		'Before' => 'Text',
		'After' => 'Text'
	);
	
	static $has_one = array(
		'LogItem' => 'DataObjectLogItem'
	);
	
	function NiceFieldName() {
		$fieldName = _t($this->LogItem()->ObjectClass.'.' . strtoupper($this->FieldName), $this->FieldName);
		if ($this->LogItem()->getObject()->hasMethod('getLogItemFieldName')) {
			$explicitFieldName = $this->LogItem()->getObject()->getLogItemFieldName($this->FieldName);
			if ($explicitFieldName) {
				$fieldName = $explicitFieldName;
			}
		}
		
		return $fieldName;
	}
	
	function NiceBefore() {
		$value = null;
		if ($this->LogItem()->getObject()->hasMethod('getLogItemValue')) {
			$value = $this->LogItem()->getObject()->getLogItemValue($this->FieldName, $this->Before, true, $this->LogItem()->Type);
		}
		if (!$value) {
			$value = $this->Before;
		}
		return $value;
	}
	
	function NiceAfter() {
		$value = null;
		if ($this->LogItem()->getObject()->hasMethod('getLogItemValue')) {
			$value = $this->LogItem()->getObject()->getLogItemValue($this->FieldName, $this->After, false, $this->LogItem()->Type);
		}
		if (!$value) {
			$value = $this->After;
		}
		return $value;
	}
	
}

?>