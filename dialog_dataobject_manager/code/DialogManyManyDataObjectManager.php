<?php

class DialogManyManyDataObjectManager extends DialogHasManyDataObjectManager
{
	private $manyManyParentClass;
	protected $manyManyTable;
	public $RelationType = "ManyMany";
	public $itemClass = 'DialogManyManyDataObjectManager_Item';
	protected $sortableOwner;
	protected $itemRelation = '';
	protected $belongsManyMany = false;

	/**
	 * Most of the code below was copied from ManyManyComplexTableField.
	 * Painful, but necessary, until PHP supports multiple inheritance.
	 */
	function __construct($controller, $name, $sourceClass, $fieldList = null, $detailFormFields = null, $sourceFilter = "", $sourceSort = "", $sourceJoin = "") {

		parent::__construct($controller, $name, $sourceClass, $fieldList, $detailFormFields, $sourceFilter, $sourceSort, $sourceJoin);
		$manyManyTable = false;
		$classes = array_reverse(ClassInfo::ancestry($this->controllerClass()));
		foreach($classes as $class) {
			if($class != "Object") {
				$singleton = singleton($class);
				$manyManyRelations = $singleton->uninherited('many_many', true);
				if(isset($manyManyRelations) && array_key_exists($this->name, $manyManyRelations)) {
					$this->manyManyParentClass = $class;
					$manyManyTable = $class . '_' . $this->name;
					$this->itemRelation = $this->name;
					break;
				}

				$belongsManyManyRelations = $singleton->uninherited( 'belongs_many_many', true );
				 if( isset( $belongsManyManyRelations ) && array_key_exists( $this->name, $belongsManyManyRelations ) ) {
					$this->manyManyParentClass = $class;
					
					// @modification http://open.silverstripe.org/ticket/5194
					$manyManyClass = $belongsManyManyRelations[$this->name];
					$manyManyRelations = singleton($manyManyClass)->uninherited('many_many', true);
					foreach($manyManyRelations as $manyManyRelationship => $manyManyChildClass)
						if ($manyManyChildClass == $class)
							break;
					
					$manyManyTable = $manyManyClass . '_' . $manyManyRelationship;
					$this->itemRelation = $manyManyRelationship;
					$this->belongsManyMany = true;
					break;
				}
			}
		}
		if(!$manyManyTable) user_error("I could not find the relation $this->name in " . $this->controllerClass() . " or any of its ancestors.",E_USER_WARNING);
		$this->manyManyTable = $manyManyTable;
		$tableClasses = ClassInfo::dataClassesFor($this->sourceClass);
		$source = array_shift($tableClasses);
		$sourceField = $this->sourceClass;
		if($this->manyManyParentClass == $sourceField)
			$sourceField = 'Child';
		$parentID = $this->controller->ID;
		
		$this->sourceJoin .= " LEFT JOIN `$manyManyTable` ON (`$source`.`ID` = `{$sourceField}ID` AND `$manyManyTable`.`{$this->manyManyParentClass}ID` = '$parentID')";
		
		$this->joinField = 'Checked';

		if($this->ShowAll() && SortableDataObject::is_sortable_many_many($this->sourceClass()))
		  $this->OnlyRelated = '1';

	}
	
	public function setParentClass($class)
	{
		parent::setParentClass($class);
		$this->joinField = "Checked";
	}
	
	protected function loadSort()
	{

		if($this->ShowAll()) 
			$this->setPageSize(999);

	    if(SortableDataObject::is_sortable_many_many($this->sourceClass(), $this->manyManyParentClass)) {
	      list($parentClass, $componentClass, $parentField, $componentField, $table) = singleton($this->controllerClass())->many_many($this->Name());
	      $sort_column = "MMSort";
	      if(!isset($_REQUEST['ctf'][$this->Name()]['sort']) || $_REQUEST['ctf'][$this->Name()]['sort'] == $sort_column) {
	        $this->sort = $sort_column;
			$this->sourceSort = "Checked DESC, " . "$sort_column " . SortableDataObject::$sort_dir;
	      }
	    }
		else if($this->Sortable() && (!isset($_REQUEST['ctf'][$this->Name()]['sort']) || $_REQUEST['ctf'][$this->Name()]['sort'] == "SortOrder")) {
			$this->sort = "SortOrder";
			$this->sourceSort = "Checked DESC, " . "SortOrder " . SortableDataObject::$sort_dir;
		}
		else if(isset($_REQUEST['ctf'][$this->Name()]['sort']) && !empty($_REQUEST['ctf'][$this->Name()]['sort'])) {
			$this->sourceSort = "Checked DESC, " . $_REQUEST['ctf'][$this->Name()]['sort'] . " " . $this->sort_dir;
		}
		else if (singleton($this->sourceClass())->stat('default_sort')) {
			$this->sourceSort = "Checked DESC, " . singleton($this->sourceClass())->stat('default_sort');
		}		
		else {
			$this->sourceSort = "Checked DESC";
		}

		
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
				// only add field if we do not have such a field, or an ID field with this name
				if(!$SNG->hasField($k) && !$SNG->hasDatabaseField($k.'ID') && !$SNG->hasMethod('get' . $k))				
					$query->select[] = $k;
			}
			$parent = $this->controllerClass();
			$mm = $this->manyManyTable;
			$if_clause = "IF(`$mm`.`{$this->manyManyParentClass}ID` IS NULL, '0', '1')";
			$query->select[] = "$if_clause AS Checked";
		    if(SortableDataObject::is_sortable_many_many($this->sourceClass(), $this->manyManyParentClass))
				$query->select[] = "IFNULL(`$mm`.SortOrder,9999999) AS MMSort";
			
			if($this->OnlyRelated())
			 $query->where[] = $if_clause;
		}
		return clone $query;
	}
		
	function getParentIdName($parentClass, $childClass) {
		return $this->getParentIdNameRelation($parentClass, $childClass, 'many_many');
	}
			
	function ExtraData() {
		$items = array();
		if ($this->unpagedSourceItems) {
			foreach($this->unpagedSourceItems as $item) {
				if($item->{$this->joinField})
					$items[] = $item->ID;
			}
		}
		else if ($this->allCustomSearchItems) {
			foreach($this->allCustomSearchItems as $item) {
				if ($this->belongsManyMany) {
					if($item->{$this->itemRelation}()->containsIDs(array($this->controller->ID))) {
						$items[] = $item->ID;
					}
				}
				else {
					if ($this->controller->{$this->itemRelation}()->containsIDs(array($item->ID))) {
						$items[] = $item->ID;
					}
				}
			}			
		}		
		$list = implode(',', $items);
		$value = ",";
		$value .= !empty($list) ? $list."," : "";
		$inputId = $this->id() . '_' . $this->htmlListEndName;
		$controllerID = $this->controller->ID;
		return <<<HTML
		<input name="controllerID" type="hidden" value="$controllerID" />
		<input id="$inputId" name="{$this->name}[{$this->htmlListField}]" type="hidden" value="$value"/>
HTML;
	}
	
	
   public function Sortable() 
   { 
      return ( 
          $this->IsReadOnly !== true && 
          $this->controller->canEdit(Member::currentUser()) && 
          ( 
             SortableDataObject::is_sortable_many_many($this->sourceClass()) || 
             SortableDataObject::is_sortable_class($this->sourceClass()) 
          ) 
       ); 
   }
   	
	public function SortableClass()
	{
	   return $this->manyManyParentClass."-".$this->sourceClass();
	}


}

class DialogManyManyDataObjectManager_Item extends DialogDataObjectManager_Item {
	
	function MarkingCheckbox() {
		$name = $this->parent->Name() . '[]';
		$disabled = $this->parent->hasMarkingPermission() ? "" : "disabled='disabled'";
		
		if($this->parent->IsReadOnly)
			return "<input class=\"checkbox\" type=\"checkbox\" name=\"$name\" value=\"{$this->item->ID}\" disabled=\"disabled\"/>";
		else if($this->item->{$this->parent->joinField})
			return "<input class=\"checkbox\" type=\"checkbox\" name=\"$name\" value=\"{$this->item->ID}\" checked=\"checked\" $disabled />";
		else
			return "<input class=\"checkbox\" type=\"checkbox\" name=\"$name\" value=\"{$this->item->ID}\" $disabled />";
	}
}

?>