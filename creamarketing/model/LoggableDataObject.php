<?php

class LoggableDataObject extends DataObjectDecorator {
	
	static $ignoredFields = array(
		'ClassName', 'RecordClassName'
	);
	
	function LogItems() {
		$items = DataObject::get('DataObjectLogItem', "ObjectClass = '{$this->owner->class}' AND ObjectID = {$this->owner->ID}", 'Created DESC');
		return $items;
	}
	
	function LogTableField() {
		$logItems = $this->owner->LogItems();
		$logTable = '';
		if ($logItems && $logItems->Count() > 0) {
			$logTable = "
				<table class='LogTable'>
					<tr>
						<th class='col1'>"._t('DataObjectLogItem.TIME', 'Tid')."</th>
						<th class='col2'>"._t('DataObjectLogItem.MEMBER', 'Användare')."</th>
						<th class='col3'>"._t('DataObjectLogItem.LOGTEXT', 'Text')."</th>
					</tr>
			";
			foreach ($logItems as $logItem) {
				$logTable .= "
					<tr id='{$logItem->ObjectClass}-{$logItem->ObjectID}-{$logItem->ID}' class='LogItem'>
						<td class='col1'>{$logItem->NiceTime()}</td>
						<td class='col2'>{$logItem->Member()->getName()}</td>
						<td class='col3'>{$logItem->LogText()}</td>
					</tr>
				 ";
			}
			$logTable .= "</table>";
		}
		Requirements::javascript(THIRDPARTY_DIR . '/jquery/jquery.js');
		Requirements::javascript(SAPPHIRE_DIR . '/javascript/i18n.js');
		Requirements::add_i18n_javascript('creamarketing/javascript/lang');
		Requirements::javascript('creamarketing/javascript/LoggableDataObject.js');
		Requirements::css('creamarketing/css/LoggableDataObject.css');
		return new LiteralField('LogTable', $logTable);
	}
	
	function LogItemDetailsField($itemID) {
		$itemID = (int)$itemID;
		$logItem = DataObject::get_one('DataObjectLogItem', "ID = {$itemID} AND ObjectClass = '{$this->owner->class}' AND ObjectID = {$this->owner->ID}");
		$logItemTable = '';
		if ($logItem) {
			$logItemTable = "
				<table class='LogTable ItemDetailsTable'>
					<tr>
						<th class='col1'>"._t('DataObjectLogItem_FieldChange.FIELD', 'Field')."</th>
						<th class='col2'>"._t('DataObjectLogItem_FieldChange.BEFORE', 'Before')."</th>
						<th class='col3'>"._t('DataObjectLogItem_FieldChange.AFTER', 'After')."</th>
					</tr>
			";
			if ($logItem->FieldChanges()) {
				foreach ($logItem->FieldChanges() as $fieldChange) {
					$logItemTable .= "
						<tr class='LogItemDetail'>
							<td class='col1'>{$fieldChange->NiceFieldName()}</td>
							<td class='col2'>{$fieldChange->NiceBefore()}</td>
							<td class='col3'>{$fieldChange->NiceAfter()}</td>
						</tr>
					";
				}
			}
			$logItemTable .= "</table>";
		}
		return new LiteralField('LogItemDetailsTable', $logItemTable);
	}
	
	function onBeforeRelationWrite($relationName, $items) {
		$currentItems = $this->owner->$relationName()->map('ID', 'ID');
		sort($currentItems);
		sort($items);
		$before = implode(',', $currentItems);
		$after = implode(',', $items);
		if ($after != $before) {
			$logItem = new DataObjectLogItem();
			$logItem->Time = date('Y-m-d H:i');
			$logItem->ObjectClass = $this->owner->class;
			$logItem->ObjectID = $this->owner->ID;
			$logItem->MemberID = Member::currentUserID();
			$logItem->Type = 'Edited';
			$logItem->write();
			$logSaved = false;
			$fieldChange = new DataObjectLogItem_FieldChange();
			$fieldChange->FieldName = $relationName;
			$fieldChange->Before = $before;
			$fieldChange->After = $after;
			$fieldChange->LogItemID = $logItem->ID;
			$fieldChange->write();
		}
	}
	
	function onAfterWrite() {
		$changedFields = $this->owner->getChangedFields(false, 2);
		if ($changedFields) {
			$logItem = new DataObjectLogItem();
			$logItem->Time = date('Y-m-d H:i');
			$logItem->ObjectClass = $this->owner->class;
			$logItem->ObjectID = $this->owner->ID;
			$logItem->MemberID = Member::currentUserID();
			$logSaved = false;
			if (isset($changedFields['ID'])) {
				$logItem->Type = 'Created';
				$logItem->write();
				$logSaved = true;
			}
			else {
				$logItem->Type = 'Edited';
			}
			
			foreach ($changedFields as $fieldName => $values) {
				$translatedField = false;
				if ($this->owner->hasExtension('TranslatableDataObject')) {
					$translatedFields = Object::get_static($this->owner->class, 'translatableFields');
					if (is_array($translatedFields) && in_array(substr($fieldName, 0, -6), $translatedFields)) {
						$translatedField = true;
					}
				}
				$relationName = $fieldName;
				if (substr($relationName, -2) == 'ID') {
					$relationName = substr($relationName, 0, -2);
				}
				
				$ignoredFields = self::$ignoredFields;
				$objectIgnores = Object::get_static($this->owner->class, 'ignoredLoggableFields');
				if ($objectIgnores) {
					$ignoredFields = array_merge($ignoredFields, $objectIgnores);
				}
				if (!in_array($fieldName, $ignoredFields) && 
					($this->owner->db($fieldName) || $this->owner->has_one($relationName) || $translatedField)) {
					if (!$logSaved) {
						$logItem->write();
					}
					$fieldChange = new DataObjectLogItem_FieldChange();
					$fieldChange->FieldName = $fieldName;
					$fieldChange->Before = $values['before'];
					$fieldChange->After = $values['after'];
					$fieldChange->LogItemID = $logItem->ID;
					$fieldChange->write();
				}
			}
		}
	}
	
	function onAfterDelete() {
		$logItem = new DataObjectLogItem();
		$logItem->Time = date('Y-m-d H:i');
		$logItem->ObjectClass = $this->owner->class;
		$logItem->ObjectID = $this->owner->ID;
		$logItem->MemberID = Member::currentUserID();
		$logItem->Type = 'Deleted';
		$logItem->write();
	}
	
}

class LoggableDataObject_Controller extends Controller {
	
	public function LogItemDetails() {
		if (isset($_GET['ObjectID']) && isset($_GET['ObjectClass']) && isset($_GET['LogItemID'])) {
			$objectID = (int)$_GET['ObjectID'];
			$objectClass = Convert::raw2sql($_GET['ObjectClass']);
			$logItemID = (int)$_GET['LogItemID'];
			if (isset($_GET['Locale'])) {
				$locale = $_GET['Locale'];
				if (in_array($locale, Translatable::get_allowed_locales())) {
					Translatable::set_current_locale($locale);
					i18n::set_locale($locale);
				}
			}
			if ($objectID > 0 && $objectClass && $logItemID > 0) {
				$object = DataObject::get_by_id($objectClass, $objectID);
				if ($object && $object->canEdit()) {
					$logItemDetails = $object->LogItemDetailsField($logItemID);
					return $logItemDetails->forTemplate();
				}
			}
		}
	}
	
}

?>