<?php

class AdvancedDropdownField extends FormField {
	
	protected $showAddButton = false;
	protected $showEditButton = false;
	protected $sourceClass = "";
	protected $sourceClassTitle = "";
	protected $source = array();
	protected $onSelect = "''";
	protected $getSource = "''";
	protected $initValue = '';
	protected $forceNameAsID = false;
	protected $generateUniqueID = false;
	protected $dropdownPosition = null;
	protected $uniqueID = '';
		
	function __construct($name, $title = null, $source = "", $value = "", $showAddButton = false, $showEditButton = false, $onSelect = null, $getSource = null, $form = null) {
		$this->uniqueID = sha1(uniqid('autocomplete_dropdown_', true));
		
		parent::__construct($name, ($title===null) ? $name : $title, $value, $form);
		
		if (strstr($value, '[initial]'))
			$this->initValue = str_replace('[initial]', '', $value);

		$this->showAddButton = $showAddButton;
		$this->showEditButton = $showEditButton;
		if ($source) {
			if (is_array($source)) {
				$this->source = $source;
			}
			else {
				$this->sourceClass = $source;
				$this->sourceClassTitle = _t($source . '.SINGULARNAME', $source);
				$sourceObjects = DataObject::get("$source");
				if ($sourceObjects) {
					$this->source = $sourceObjects->map('ID', 'Name', _t('AdvancedDropdownField.NONESELECTED', '(None selected)'));
				}
			}
		}
		
		if ($onSelect) {
			$this->onSelect = $onSelect;
		}
		
		if ($getSource) {
			$this->getSource = $getSource;
		}	
	}
	
	function setSource($source) {
		$this->source = $source;
	}
	
	function setSourceClass($sourceClass) {
		$this->sourceClass = $sourceClass;
	}
	
	function setForceNameAsID($boolean) {
		$this->forceNameAsID = $boolean;
	}
	
	function setGenerateUniqueID($boolean) {
		$this->generateUniqueID = $boolean;
	}
	
	/*
	 * Alternative position for dropdown, $position should be an array like this:
	 * array('my' => 'left top', 'at' => 'left bottom');
	 */
	function setDropdownPosition($position) {
		$this->dropdownPosition = $position;
	}
	
	function id() {
		$id = '';
		
		if ($this->forceNameAsID) {
			$id = $this->name;
		}
		else {
			$id = parent::id();
		}
		
		if ($this->generateUniqueID) {
			$id .= '_' . $this->uniqueID;
		}
		
		return $id;
	}
	
	function FieldHolder() {
		$Title = $this->XML_val('Title');
		$Message = $this->XML_val('Message');
		$MessageType = $this->XML_val('MessageType');
		$RightTitle = $this->XML_val('RightTitle');
		$Type = $this->XML_val('Type');
		$extraClass = $this->XML_val('extraClass');
		$Name = $this->XML_val('Name');
		$Field = $this->XML_val('Field');
	
		if ($this->id() == $Name) {
			$Name .= 'Holder';
		}
		
		// Only of the the following titles should apply
		$titleBlock = (!empty($Title)) ? "<label class=\"left\" for=\"{$this->id()}\">$Title</label>" : "";
		$rightTitleBlock = (!empty($RightTitle)) ? "<label class=\"right\" for=\"{$this->id()}\">$RightTitle</label>" : "";
	
		// $MessageType is also used in {@link extraClass()} with a "holder-" prefix
		$messageBlock = (!empty($Message)) ? "<span class=\"message $MessageType\">$Message</span>" : "";
	
		return <<<HTML
	<div id="$Name" class="field $Type $extraClass">$titleBlock<div class="middleColumn">$Field</div>$rightTitleBlock$messageBlock</div>
HTML;
	}
	
	static function isMultiArray($a){
		foreach($a as $v) if(is_array($v)) return true;
		return false;
	}	
	
	function Field() {
		Requirements::javascript(THIRDPARTY_DIR . '/jquery/jquery.js');
		Requirements::javascript(THIRDPARTY_DIR . '/jquery-ui/jquery-ui-1.8rc3.custom.js');
		//Requirements::javascript(THIRDPARTY_DIR . '/jquery-ui/jquery.ui.autocomplete.js');
		Requirements::javascript(THIRDPARTY_DIR . '/jquery-ui/jquery.ui.autocomplete-1.8rc3-mod.js');
		Requirements::javascript(THIRDPARTY_DIR . '/jquery-ui/jquery.ui.dialog.js');
		Requirements::javascript(THIRDPARTY_DIR . '/jquery-ui/jquery.ui.position.js');		
		Requirements::css(THIRDPARTY_DIR . '/jquery-ui-themes/smoothness/jquery-ui-1.8rc3.custom.css');
		Requirements::javascript(THIRDPARTY_DIR . '/jquery-form/jquery.form.js');
		Requirements::javascript('creamarketing/javascript/AdvancedDropdownField.js');
		Requirements::javascript('creamarketing/javascript/AdvancedDropdownFieldHelpers.js');
		Requirements::css('creamarketing/css/AdvancedDropdownField.css');
		// javascript localization
		Requirements::javascript('sapphire/javascript/i18n.js');
		Requirements::add_i18n_javascript('dialog_dataobject_manager/javascript/lang');

		if (strlen($this->initValue)) {
			$this->setValue($this->initValue);
			$this->initValue = '';
		}
		
		$extraDropdownClasses = 'AdvancedDropdownField-autocomplete';
		if (!empty($this->name))
			$extraDropdownClasses .= ' ' . $this->name . '-autocomplete';
		
		$customJS = "jQuery(document).ready(function () {	
						AdvancedDropdownField('{$this->id()}', {$this->onSelect}, {$this->getSource}, '{$extraDropdownClasses}');
					 ";
		if (is_array($this->dropdownPosition))
			$customJS .= "AdvancedDropdownFieldPosition('{$this->id()}', '{$this->dropdownPosition['my']}', '{$this->dropdownPosition['at']}');";
		
		if ($this->showAddButton) {
			$customJS .= "
							jQuery('#{$this->id()}_addButton').click(function() {
								top.ShowAddOrEditDialog('{$this->id()}', '{$this->Link()}/AddFormHTML', '" . _t('AdvancedDropdownField.ADD', 'Add') . " {$this->sourceClassTitle}', true, window);
							 });
						 ";
		}
		
		if ($this->showEditButton) {
			$customJS .= "
							jQuery('#{$this->id()}_editButton').click(function() {
								var currentID = jQuery('#{$this->id()}').val();
								if (currentID && currentID != 0) {
									top.ShowAddOrEditDialog('{$this->id()}', '{$this->Link()}/EditFormHTML?id='+currentID, '" . _t('AdvancedDropdownField.EDIT', 'Edit') . " {$this->sourceClassTitle}', false, window);
								}
							 });
						 ";
		}
		
		$customJS .= '});';
		
		Requirements::customScript($customJS);
			
		$attributes = array(
			'class' => ($this->extraClass() ? $this->extraClass() : ''),
			'id' => $this->id(),
			'name' => $this->name,
			'type' => 'hidden',
			'value' => $this->value
		);
		
		$html = $this->createTag('input', $attributes);
				
		$sourceMultiArray = self::isMultiArray($this->source);
		$value = '';
		if ($this->source && is_array($this->source)) {
			if ($this->value && strpos($this->value, ',') > 0) { // Multiple select
				$tmp_values = array();
				$all_ids = explode(',', $this->value);
				
				foreach($all_ids as $id) {
					if (!$sourceMultiArray) {
						if (isset($this->source[$id]))
							$tmp_values[] = $this->source[$id];
					}
					else {
						foreach ($this->source as $sourceKey => $sourceValue) {
							if (is_array($sourceValue)) {
								if ($sourceValue['id'] == $id)
									$tmp_values[] = $sourceValue['text'];								
							}
						}
					}
				}
				$value = implode(', ', $tmp_values);
			}			
			else if ($this->value) {
				if (!$sourceMultiArray) {
					if (isset($this->source[$this->value]))					
						$value = $this->source[$this->value];
				}				
				else {
					foreach ($this->source as $sourceKey => $sourceValue) {
						if (is_array($sourceValue)) {
							if ($sourceValue['id'] == $this->value) {
								$value = $sourceValue['text'];	
								break;
							}
						}
					}					
				}
			}			
			else if (isset($this->source[''])) {
				$value = $this->source[''];
			}
			else if (isset($this->source[0])) {
				$value = $this->source[0];
			}
		}

		$attributes = array(
			'class' => 'AdvancedDropdown',
			'id' => $this->id() . 'Text',
			'type' => 'text',
			'value' => $value
		);
		
		if ($this->isDisabled())
			$attributes['disabled'] = 'disabled';
		if ($this->isReadonly())
			$attributes['readonly'] = 'readonly';
		
		$html .= $this->createTag('input', $attributes);
		
		$attributes = array(
			'class' => 'AdvancedDropdownSource',
			'id' => $this->id() . 'Select'
		);
		
		$content = '';
		$allValues = array($this->value);
		if (strpos($this->value, ',') > 0 && is_array($this->source)) // Support multiple selected
			$allValues = explode(',', $this->value);
		
		foreach ($this->source as $key => $value) {
			$selected = '';
			$selected_class = '';
			$id = $key;
			$text = $value;
			$class = '';
			$disabled = '';
			if (is_array($value)) {
				if (isset($value['id'])) {
					$id = $value['id'];
				}
				if (isset($value['class'])) {
					$class = $value['class'];
				}
				if (isset($value['text'])) {
					$text = $value['text'];
				}
				if (isset($value['disabled']) && $value['disabled']) {
					$disabled = ' disabled="disabled"';
				}
			}
			if (in_array($id, $allValues)) { 
				$selected = ' selected="selected"';
				$class .= ' selected';
			} 
			$content .= '<option class="'. $class . '" value="' . $id . '"' . $selected . $disabled . '>' . $text . '</option>';
		}
		$html .= $this->createTag('select', $attributes, $content);
		
		if ($this->showAddButton) {
			$attributes = array(
				'class' => 'autocomplete-addButton',
				'id' => $this->id() . '_addButton',
				'type' => 'button',
				'value' => _t('AdvancedDropdownField.ADD', 'Add')
			);
			if ($this->isDisabled())
				$attributes['disabled'] = 'disabled';
			if ($this->isReadonly())
				$attributes['readonly'] = 'readonly';
			$html .= $this->createTag('input', $attributes);
		}
		
		if ($this->showEditButton) {
			$attributes = array(
				'class' => 'autocomplete-editButton',
				'id' => $this->id() . '_editButton',
				'type' => 'button',
				'value' => _t('AdvancedDropdownField.EDIT', 'Edit')
			);
			if ($this->isDisabled())
				$attributes['disabled'] = 'disabled';
			if ($this->isReadonly())
				$attributes['readonly'] = 'readonly';
			$html .= $this->createTag('input', $attributes);
		}
		
		if (Director::is_ajax()) {
			$html .= "
						<script type=\"text/javascript\">
						//<![CDATA[
							$customJS
						//]]>
						</script>
					";
		}
		
		return $html;
	}
	
	public function AddForm() {
		$sourceObject = singleton($this->sourceClass);
		$fields = $sourceObject->getCMSFields();
		
		// add a hidden field with the edited objects classname, to mimic the dataobjectmanager popup
		$fields->push(new HiddenField('ctf[ClassName]', '', $this->sourceClass));
		
		// fake form action with a hidden field with name 'action_ACTION'
		$fields->push(new HiddenField('action_AddObject', 'action_AddObject', 'add'));
		
		$actions = new FieldSet();
		
		$form = new Form($this, 'AddForm', $fields, $actions);
		$form->loadDataFrom($sourceObject);
		return $form;
	}
	
	public function AddObject($data, $form) {
		$responseData = array('ID' => 0, 'Name' => '', 'Error' => null);
		try {
			$sourceObject = new $this->sourceClass();
			$sourceObject->write();
			$form->saveInto($sourceObject);
			$sourceObject->write();
			if (Object::has_extension($this->sourceClass, 'TranslatableDataObject')) {
				$value = $sourceObject->getTranslatedValue('Name');
			}
			else {
				$value = $sourceObject->Name;
			}
			$responseData['ID'] = $sourceObject->ID;
			$responseData['Name'] = $value;
		}
		catch(ValidationException $e) {
			$responseData['Error'] = $e->getResult()->message();
		}
		$response = new SS_HTTPResponse(json_encode($responseData));
		$response->addHeader("Content-type", "application/json");
		return $response;
	}
	
	public function AddFormHTML() {
		return $this->AddForm()->forTemplate();
	}
	
	public function EditForm() {
		if (isset($_GET['id'])) {
			$id = intval($_GET['id']);
		}
		else {
			$id = 0;
		}
		
		if ($id) {
			$sourceObject = DataObject::get_by_id($this->sourceClass, $id);
		}
		else {
			$sourceObject = singleton($this->sourceClass);
		}
		$fields = $sourceObject->getCMSFields();
		$fields->push(new HiddenField('EditID', '', $id));
		// add a hidden field with the edited objects classname, to mimic the dataobjectmanager popup
		$fields->push(new HiddenField('ctf[ClassName]', '', $this->sourceClass));
		
		// fake form action with a hidden field with name 'action_ACTION'
		$fields->push(new HiddenField('action_EditObject', 'action_EditObject', 'edit'));
		
		$actions = new FieldSet();
		
		$form = new Form($this, 'EditForm', $fields, $actions);
		$form->loadDataFrom($sourceObject);
		return $form;
	}
	
	public function EditObject($data, $form) {
		if (isset($data['EditID'])) {
			$id = intval($data['EditID']);
		}
		else {
			$id = 0;
		}
		
		$responseData = array('ID' => $id, 'Name' => '', 'Error' => null);
		try {
			if ($id) {
				$sourceObject = DataObject::get_by_id($this->sourceClass, $id);
			}
			else {
				$sourceObject = new $this->sourceClass;
			}
			$form->saveInto($sourceObject);
			$sourceObject->write();
			if (Object::has_extension($this->sourceClass, 'TranslatableDataObject')) {
				$value = $sourceObject->getTranslatedValue('Name');
			}
			else {
				$value = $sourceObject->Name;
			}
			$responseData['Name'] = $value;
		}
		catch(ValidationException $e) {
			$responseData['Error'] = $e->getResult()->message();
		}
		$response = new SS_HTTPResponse(json_encode($responseData));
		$response->addHeader("Content-type", "application/json");
		return $response;
	}
	
	public function EditFormHTML() {
		return $this->EditForm()->forTemplate();
	}
	
	public function Link($action=null) {
		$link = parent::Link($action);
		$link = split('\?', $link);
		return $link[0];
	}
	
}

?>