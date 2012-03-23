<?php

class DialogHasManyDataObjectManager extends DialogDataObjectManager
{
	public $joinField;
	public $addTitle;
	public $RelationType = "HasMany";	
	protected $htmlListEndName = 'CheckedList';
	protected $htmlListField = 'selected';
	public $template = 'DialogRelationDataObjectManager';
	public $itemClass = 'DialogHasManyDataObjectManager_Item';
	protected $relationAutoSetting = false;
  	protected $markingPermission;
	protected $unpagedSourceItems = null;
  	public $allowRelationOverride = false;
  	
  	protected static $only_related;
  	protected $OnlyRelated = false;
  	
  	public static $rowClickSelectsRelation = true;
  	
  	public function RowClickSelectsRelation() {
  		return self::$rowClickSelectsRelation;
  	}	
	
  	public static function set_only_related($bool)
  	{
  		self::$only_related = $bool;
  	}
  	
  	public function setOnlyRelated($bool)
  	{
  		if(!isset($_REQUEST['ctf'][$this->Name()]['only_related']))
  		$this->OnlyRelated = $bool;
  	}
  	
  	public function OnlyRelated()
  	{
  		return self::$only_related !== null ? self::$only_related : $this->OnlyRelated;
  	}
  	
  	public function OnlyRelatedLink()
  	{
  		return $this->RelativeLink(array('only_related' => '1'));
  	}
  	
  	public function AllRecordsLink()
  	{
  		return $this->RelativeLink(array('only_related' => '0'));
  	}	
	
	/**
	 * Most of the code below was copied from HasManyComplexTableField.
	 * Painful, but necessary, until PHP supports multiple inheritance.
	 */
	
	function __construct($controller, $name, $sourceClass, $fieldList = null, $detailFormFields = null, $sourceFilter = "", $sourceSort = "", $sourceJoin = "")
	{
		parent::__construct($controller, $name, $sourceClass, $fieldList, $detailFormFields, $sourceFilter, $sourceSort, $sourceJoin);
		
		$this->Markable = true;
		$this->relationAutoSetting = true;

		if($controllerClass = $this->controllerClass()) {
			$this->joinField = $this->getParentIdName($controllerClass, $this->sourceClass);
		} else {
			user_error("Can't figure out the data class of $controller", E_USER_WARNING);
		}
		
		if(isset($_REQUEST['ctf'][$this->Name()]['only_related']))
			$this->OnlyRelated = $_REQUEST['ctf'][$this->Name()]['only_related'];
		
		$this->addPermission('only_related');		
	}

	
	/**
	 * Try to determine the DataObject that this field is built on top of
	 */
	function controllerClass() {
		if($this->controller instanceof DataObject) return $this->controller->class;
		elseif($this->controller instanceof ContentController) return $this->controller->data()->class;
	}
	
	public function setMarkingPermission($perm)
	{
	   $this->markingPermission = $perm;
	}
	
	public function hasMarkingPermission()
	{
	   if(is_bool($this->markingPermission))
	     return $this->markingPermission;
	   elseif($this->markingPermission)
	     return Permission::check($this->markingPermission);
     return true;
	}
	
	public function setParentClass($class)
	{
		parent::setParentClass($class);
		$this->joinField = $this->getParentIdName($class, $this->sourceClass);
	}
	
	public function getQueryString($params = array())
	{
		$only_related = isset($params['only_related'])? $params['only_related'] : $this->OnlyRelated();
		return parent::getQueryString($params)."&ctf[{$this->Name()}][only_related]={$only_related}";
	}	
	
	function getQuery($limitClause = null) {
		if($this->customQuery) {
			$query = $this->customQuery;
			$query->select[] = "{$this->sourceClass}.ID AS ID";
			$query->select[] = "{$this->sourceClass}.ClassName AS ClassName";
			$query->select[] = "{$this->sourceClass}.ClassName AS RecordClassName";
		}
		else {
			$query = singleton($this->sourceClass)->extendedSQL($this->sourceFilter, $this->sourceSort, $limitClause, $this->sourceJoin);
			
			// Add more selected fields if they are from joined table.

			$SNG = singleton($this->sourceClass);
			foreach($this->FieldList() as $k => $title) {
				// check for nested field names (with dot notation)
				$fieldNameParts = explode('.', $k);
				if (count($fieldNameParts) > 0) {
					$k = $fieldNameParts[0];
				}
				// only add field if we do not have such a field, or an ID field with this name
				if(!$SNG->hasField($k) && !$SNG->hasDatabaseField($k.'ID') && !$SNG->hasMethod('get' . $k))
					$query->select[] = $k;
			}
			
			if ($this->class == 'DialogHasManyDataObjectManager' && $this->controller->ID) {
				$query->select[] = "IF({$this->sourceClass}.{$this->joinField} = {$this->controller->ID}, 1, 0) AS Related";
				$query->orderBy("Related DESC, $this->sourceSort");
			}
			else if ($this->class == 'DialogHasOneDataObjectManager' && $this->getControllerJoinID()) {
				$query->select[] = "IF({$this->sourceClass}.ID = {$this->getControllerJoinID()}, 1, 0) AS Selected";
				$query->orderBy("Selected DESC, $this->sourceSort");
			}			
			
			if($this->OnlyRelated())
				$query->where[] = "{$this->sourceClass}.{$this->joinField} = {$this->controller->ID}";			
		}
		return clone $query;
	}
	
	function sourceItems() {
		if ($this->sourceItems)
			return $this->sourceItems;
		if ($this->customSourceItems) 
			return $this->customSourceItems;	
			
		$limitClause = '';
		if(isset($_REQUEST[ 'ctf' ][ $this->Name() ][ 'start' ]) && is_numeric($_REQUEST[ 'ctf' ][ $this->Name() ][ 'start' ]))
			$limitClause = $_REQUEST[ 'ctf' ][ $this->Name() ][ 'start' ] . ", $this->pageSize";
		else
			$limitClause = "0, $this->pageSize";
		
		$dataQuery = $this->getQuery($limitClause);
		$records = $dataQuery->execute();
		$items = new DataObjectSet();
		foreach($records as $record) {
			if(! is_object($record)) {
				$class = $this->sourceClass;
				$record = new $class($record);
			}
			$items->push($record);
		}
		
		$dataQuery = $this->getQuery();
		$records = $dataQuery->execute();
		$unpagedItems = new DataObjectSet();
		foreach($records as $record) {
			if(! is_object($record)) {
				$class = $this->sourceClass;
				$record = new $class($record);
			}
			$unpagedItems->push($record);
		}
		$this->unpagedSourceItems = $unpagedItems;
		
		$this->totalCount = ($this->unpagedSourceItems) ? $this->unpagedSourceItems->TotalItems() : null;
		
		return $items;
	}
		
	function getControllerID() {
		return $this->controller->ID;
	}
	
	public function SortableClass()
	{
	   return $this->sourceClass();
	}
	
	function saveInto(DataObject $record) {
		$fieldName = $this->name;
		$saveDest = $record->$fieldName();
		
		if(! $saveDest)
			user_error("HasManyDataObjectManager::saveInto() Field '$fieldName' not found on $record->class.$record->ID", E_USER_ERROR);
		
		$items = array();
		
		// Check that $this->value is array, otherwise might generate notice warning if not and still accessed anyway
		if(is_array($this->value) && $list = $this->value[ $this->htmlListField ]) {
			if($list != 'undefined')
				$items = explode(',', trim($list,","));
		}
		
		$record->extend('onBeforeRelationWrite', $fieldName, $items);
		$saveDest->setByIDList($items);
	}
	
	function ExtraData() {
		$items = array();
		if ($this->unpagedSourceItems) {
			foreach($this->unpagedSourceItems as $item) {
				//if(($item->{$this->joinField} == $this->controller->ID && $this->controller->ID > 0) || ($this->controller->ID == 0 && $this->getRelationAutoSetting()))
				if($item->{$this->joinField} == $this->controller->ID && $this->controller->ID > 0)
					$items[] = $item->ID;
				else if ($item->hasExtension('TemporaryDataObjectOwner')) {
					if ($item->{$this->joinField} == 0 && $this->controller->ID == 0 && $this->getRelationAutoSetting() && $item->TemporaryDataObjectOwnerID == Member::currentUserID())
						$items[] = $item->ID;
				}
			}
		} 
		else if ($this->allCustomSearchItems) {
			foreach($this->allCustomSearchItems as $item) {
				//if(($item->{$this->joinField} == $this->controller->ID && $this->controller->ID > 0) || ($this->controller->ID == 0 && $this->getRelationAutoSetting()))
				if($item->{$this->joinField} == $this->controller->ID && $this->controller->ID > 0)
					$items[] = $item->ID;
				else if ($item->hasExtension('TemporaryDataObjectOwner')) {
					if ($item->{$this->joinField} == 0 && $this->controller->ID == 0 && $this->getRelationAutoSetting() && $item->TemporaryDataObjectOwnerID == Member::currentUserID())
						$items[] = $item->ID;
				}
			}			
		}
		$list = implode(',', $items);
		$value = ",";
		$value .= !empty($list) ? $list."," : "";
		$inputId = $this->id() . '_' . $this->htmlListEndName;
		return <<<HTML
		<input id="$inputId" name="{$this->name}[{$this->htmlListField}]" type="hidden" value="{$value}"/>
HTML;
	}

}

class DialogHasManyDataObjectManager_Item extends DialogDataObjectManager_Item {
	
	function MarkingCheckbox() {
		$name = $this->parent->Name() . '[]';
		$joinVal = $this->item->{$this->parent->joinField};
		$parentID = $this->parent->getControllerID();
		$disabled = $this->parent->hasMarkingPermission() ? "" : "disabled='disabled'";
		$autoRelation =  $this->parent->getRelationAutoSetting();
		
		
		if($this->parent->IsReadOnly || ($joinVal > 0 && $joinVal != $parentID)) {
			$extraAttributes = '';
			if ($this->parent->allowRelationOverride) {
				$joinObj = DataObject::get_by_id($this->parent->getParentClass(), $joinVal);
				if ($joinObj) {
					$joinName = $joinObj->getTitle();
					$extraAttributes = 'onclick="if (this.checked) {var ok = confirm(\'' . sprintf(_t('DialogHasManyDataObjectManager.RELATIONOVERRIDE', 'Relation already set to: %s\nReally change?'), $joinName) . '\'); if (!ok) this.checked = false; return ok;} else {return true;}"';
				}
			}
			else {
				$extraAttributes = 'disabled="disabled"';
			}
			return "<input class=\"checkbox\" type=\"checkbox\" name=\"$name\" value=\"{$this->item->ID}\" $extraAttributes />";
		}
		//else if(($joinVal == $parentID && $joinVal > 0) || ($parentID == 0 && $autoRelation))
		else if($joinVal == $parentID && $joinVal > 0)
			return "<input class=\"checkbox\" type=\"checkbox\" name=\"$name\" value=\"{$this->item->ID}\" checked=\"checked\" $disabled />";
		else
			return "<input class=\"checkbox\" type=\"checkbox\" name=\"$name\" value=\"{$this->item->ID}\" $disabled />";
	}
}

?>
