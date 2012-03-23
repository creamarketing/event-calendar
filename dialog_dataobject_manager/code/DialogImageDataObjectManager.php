<?php

class DialogImageDataObjectManager extends ImageDataObjectManager {
	public $template = "DialogImageDataObjectManager";
	public $itemClass = "DialogImageDataObjectManager_Item";
	public $popupClass = "DialogImageDataObjectManager_Popup";	
	public $templatePopup = "DialogImageDataObjectManager_popup";
	
	public function __construct($controller, $name = null, $sourceClass = null, $fileFieldName = null, $fieldList = null, $detailFormFields = null, $sourceFilter = "", $sourceSort = "", $sourceJoin = "") {
		parent::__construct($controller, $name, $sourceClass, $fileFieldName, $fieldList, $detailFormFields, $sourceFilter, $sourceSort, $sourceJoin);
		
		Requirements::block('dataobject_manager/javascript/imagedataobject_manager.js');
	}
	
	function handleItem($request) {
		return new DialogImageDataObjectManager_ItemRequest($this, $request->param('ID'));
	}	
	
	protected function closePopup()
	{
		Requirements::clear();
		if($this->isNested)
			Requirements::customScript("top.CloseLastDataObjectManager(true);");

		return $this->customise(array(
			'String' => true,
			'DetailForm' => 'Closing...'
		))->renderWith($this->templatePopup);
	}	
	
	/**
	 * Overridden so that we do not have to save the object before adding/editing neseted DOMs
	 */
	/*function FieldHolder() {
		// set caption if required
		if($this->popupCaption) {
			$id = $this->id();
			if(Director::is_ajax()) {
			$js = <<<JS
$('$id').GB_Caption = '$this->popupCaption';
JS;
				FormResponse::add($js);
			} else {
			$js = <<<JS
Event.observe(window, 'load', function() { \$('$id').GB_Caption = '$this->popupCaption'; });
JS;
				Requirements::customScript($js);
			}
		}

		// compute sourceItems here instead of Items() to ensure that
		// pagination and filters are respected on template accessors
		$this->sourceItems();

		return $this->renderWith($this->template);
	}	*/
}

class DialogImageDataObjectManager_Item extends ImageDataObjectManager_Item {
	function __construct(DataObject $item, ComplexTableField $parent) {
		parent::__construct($item, $parent);
	}
}

class DialogImageDataObjectManager_Popup extends ImageDataObjectManager_Popup {
	function __construct($controller, $name, $fields, $validator, $readonly, $dataObject) {
		parent::__construct($controller, $name, $fields, $validator, $readonly, $dataObject);
		
		Requirements::css('dialog_dataobject_manager/css/DialogDataObjectManager.css');
	}
}

class DialogImageDataObjectManager_ItemRequest extends ImageDataObjectManager_ItemRequest {
	function __construct($ctf, $itemID) {
		parent::__construct($ctf, $itemID);
	}
	
	function saveComplexTableField($data, $form, $params) {
		// use addComplexTableField if the url contains AddForm, to be able to add items in nested DOM's
		if ($this->itemID == 0 || strstr($this->getRequest()->getURL(), 'AddForm')) {
			$this->addComplexTableField($data, $form, $params);
		}
		else {
			$this->updateComplexTableField($data, $form, $params);
		}
	}	

	function addComplexTableField($data, $form, $params) {
		try {		
			$className = $this->ctf->sourceClass();
			$childData = new $className();
			$childData->write();
			$form->saveInto($childData);
			$childData->write();
					
			$response['code'] = 'good';
			if (isset($data['closeAfterAdd'])) 
				$response['closePopup'] = true;	
			$response['message'] = sprintf(_t('DataObjectManager.ADDEDNEW','Added new %s successfully'),$this->ctf->SingleTitle());
			echo json_encode($response);			
		}
		catch(ValidationException $e) {
			$response['code'] = 'bad';
			$response['message'] = $e->getResult()->message();
			echo json_encode($response);
		}
	}	
	
	function updateComplexTableField($data, $form, $request) {
		try {
			$dataObject = $this->dataObj();
			$form->saveInto($dataObject);
			$dataObject->write();
		
			$response['code'] = 'good';
			if (isset($data['closeAfterSave'])) 
				$response['closePopup'] = true;
			$response['message'] = sprintf(_t('DataObjectManager.SAVED','Saved %s successfully'),$this->ctf->SingleTitle());
			echo json_encode($response);			
		}
		catch(ValidationException $e) {
			$response['code'] = 'bad';
			$response['message'] = $e->getResult()->message();
			echo json_encode($response);
		}
	}	
}

?>
