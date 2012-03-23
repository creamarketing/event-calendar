<?php

class EventPageCustomizable extends EventPage {
	static $db = array(
		'IFrameWidth' => 'Varchar',
		'ExtraFrameStyles' => 'Text',
		'ShowCategories' => 'Boolean',
		'ShowEventsToday' => 'Boolean',
		'AllowMunicipalitiesSelection' => 'Boolean'
	);
	
	static $defaults = array(
		'IFrameWidth' => '580px',
		'ShowCategories' => true,
		'ShowEventsToday' => true,
		'AllowMunicipalitiesSelection' => false
	);
	
	static $has_one = array(
		'HeaderLeftImage' => 'EventPageImage',
		'HeaderRightImage' => 'EventPageImage',
		'BackgroundImage' => 'EventPageImage',
		'BackgroundLeftImage' => 'EventPageImage',
		'BackgroundRightImage' => 'EventPageImage',
	);
	
	static $many_many = array(
		'Municipalities' => 'Municipal'
	);
	
	public function IFrameStyles() {
		$iframeWidth = $this->IFrameWidth;
		
		Requirements::customCSS("
			html { background: #fff; overflow-y: auto; height: auto; } 
			body { } 
			#BgContainer { width: {$iframeWidth}; } 
			#Container { width: {$iframeWidth}; } 
			#Layout { width: {$iframeWidth}; margin-top: 0px; } 
		");
	}
	
	public function getShowCategories() {	
		return $this->getField('ShowCategories');
	}
	
	public function getShowEventsToday() {
		return $this->getField('ShowEventsToday');
	}
	
	public function CustomTitle() {
		return $this->Title;
	}
		
	public function UseCustomHeader() {
		return true;
	}
	
	public function getThemeData() {
		return array(
			'CustomTitle' => $this->CustomTitle(),
			'UseCustomHeader' => $this->UseCustomHeader(),
			'ExtraFrameStyles' => $this->ExtraFrameStyles(),
			'HeaderLeftImage' => $this->HeaderLeftImage(),
			'HeaderRightImage' => $this->HeaderRightImage(),
			'BackgroundImage' => $this->BackgroundImage(),
			'BackgroundLeftImage' => $this->BackgroundLeftImage(),
			'BackgroundRightImage' => $this->BackgroundRightImage()
		);
	}	
		
	public function ExtraFrameStyles() {
		$extraStyles = $this->ExtraFrameStyles;
		if ($this->BackgroundImage() && $this->BackgroundImage()->exists())
			$extraStyles = str_replace('BackgroundImageURL', $this->BackgroundImage()->getURL(), $extraStyles);
		if ($this->BackgroundLeftImage() && $this->BackgroundLeftImage()->exists())
			$extraStyles = str_replace('BackgroundLeftImageURL', $this->BackgroundLeftImage()->getURL(), $extraStyles);		
		if ($this->BackgroundRightImage() && $this->BackgroundRightImage()->exists())
			$extraStyles = str_replace('BackgroundRightImageURL', $this->BackgroundRightImage()->getURL(), $extraStyles);				
		
		Requirements::customCSS($extraStyles);
	}
	
	function getCMSFields() {
		$fields = parent::getCMSFields();
		
        $translation = $this->getTranslation(Translatable::default_locale());		
		
		$fields->addFieldToTab("Root.Content.Main", $additionalField = new TextField("IFrameWidth", _t('EventPageFrame.IFRAMEWIDTH', 'Iframe width')), 'Content');
		$fields->addFieldToTab("Root.Content.Main", $additionalField = new TextareaField("ExtraFrameStyles", _t('EventPageFrame.EXTRAFRAMESTYLES', 'Extra CSS')), 'Content');
		$fields->addFieldToTab("Root.Content.Main", $additionalField = new CheckboxField("ShowCategories", _t('EventPageFrame.SHOWCATEGORIES', 'Show categories')), 'Content');
		$fields->addFieldToTab("Root.Content.Main", $additionalField = new CheckboxField("ShowEventsToday", _t('EventPageFrame.SHOWEVENTSTODAY', 'Show events today')), 'Content');
		$fields->addFieldToTab("Root.Content.Main", $additionalField = new CheckboxField("AllowMunicipalitiesSelection", _t('EventPageFrame.ALLOWMUNICIPALITIESSELECTION', 'Allow municipalities selection')), 'Content');
		
		$fields->addFieldToTab('Root.Content', new Tab('ImagesTab', _t('EventPage.IMAGESTAB', 'Images'), 
				$headerLeftImage = new ImageUploadField('HeaderLeftImage', _t('EventPage.HEADERLEFTIMAGE', 'Header left image')),
				$headerRightImage = new ImageUploadField('HeaderRightImage', _t('EventPage.HEADERRIGHTIMAGE', 'Header right image')),
				$backgroundUploader = new ImageUploadField('BackgroundImage', _t('EventPage.BACKGROUNDIMAGE', 'Background image')),
				$backgroundLeftUploader = new ImageUploadField('BackgroundLeftImage', _t('EventPage.BACKGROUNDLEFTIMAGE', 'Background left image')),
				$backgroundRightUploader = new ImageUploadField('BackgroundRightImage', _t('EventPage.BACKGROUNDRIGHTIMAGE', 'Background right image'))
		));
		
		$headerLeftImage->setUploadFolder('eventpage_logos');
		$headerLeftImage->setVar('image_class', 'EventPageImage');
		$headerLeftImage->removeImporting();
		$headerLeftImage->removeFolderSelection();
		$headerLeftImage->setBackend(false);
		
		$headerRightImage->setUploadFolder('eventpage_logos');
		$headerRightImage->setVar('image_class', 'EventPageImage');
		$headerRightImage->removeImporting();
		$headerRightImage->removeFolderSelection();
		$headerRightImage->setBackend(false);		
		
		$backgroundUploader->setUploadFolder('eventpage_backgrounds');
		$backgroundUploader->setVar('image_class', 'EventPageImage');
		$backgroundUploader->removeImporting();
		$backgroundUploader->removeFolderSelection();
		$backgroundUploader->setBackend(false);		
		
		$backgroundLeftUploader->setUploadFolder('eventpage_backgrounds');
		$backgroundLeftUploader->setVar('image_class', 'EventPageImage');
		$backgroundLeftUploader->removeImporting();
		$backgroundLeftUploader->removeFolderSelection();
		$backgroundLeftUploader->setBackend(false);			
		
		$backgroundRightUploader->setUploadFolder('eventpage_backgrounds');
		$backgroundRightUploader->setVar('image_class', 'EventPageImage');
		$backgroundRightUploader->removeImporting();
		$backgroundRightUploader->removeFolderSelection();
		$backgroundRightUploader->setBackend(false);					
		
		$fields->addFieldToTab('Root.Content', new Tab('MunicipalitiesTab', _t('EventPage.MUNICIAPLITITESTAB', 'Municipalitites'),
				$municipalititesDOM = new ManyManyDataObjectManager($this, 'Municipalities', 'Municipal')
		));
		
		$municipalititesDOM->removePermission('add');
		$municipalititesDOM->removePermission('edit');
		$municipalititesDOM->removePermission('delete');
		
		//if ($translation && $this->Locale != Translatable::default_locale())
		//	$fields->addFieldToTab("Root.Content.Main", $additionalField = new CheckboxField("ShowAsTab", _t('Page.SHOWASTAB', 'Show as tab?')), 'Content');
		//else 
		//	$fields->addFieldToTab("Root.Content.Main", $additionalField = new CheckboxField("ShowAsTab", _t('Page.SHOWASTAB', 'Show as tab?')), 'Content');			
		
        /*if($translation && $this->Locale != Translatable::default_locale()) {
            $transformation = new Translatable_Transformation($translation);
            $fields->replaceField(
                'ShowAsTab',
                $transformation->transformFormField($additionalField)
            );
        }*/		
		
		return $fields;
	}	
}

class EventPageCustomizable_Controller extends EventPage_Controller {
	protected $iframeMode = false;
	
	public function init() {
		Page_Controller::init();
		
		Validator::set_javascript_validation_handler('none');
					
		if (isset($_REQUEST['iframe_mode']) && $_REQUEST['iframe_mode'] == '1') {
			$this->iframeMode = true;
			$this->pageTemplate = 'PageIFrame';			
			$this->showResultsRedirect = 'showResults?iframe_mode=1';
		}
		
		Session::set('ThemeFromEventPageID', $this->CurrentPage()->ID);
		$this->useDefaultLayout = false;
	}
	
	public function getIsIFrameMode() {
		return $this->iframeMode;
	}
	
	public function AppendIFrameParam($prepend = '&') {
		if ($this->iframeMode)
			return $prepend . 'iframe_mode=1';
		return;
	}
	
	protected function updateSearchFormFields(&$fields) {
		//parent::updateSearchFormFields($fields);
		
		$fields->push(new HiddenField('iframe_mode', '', $this->IsIframeMode ? '1' : '0'));
		
		if (!$this->AllowMunicipalitiesSelection) {
			$fields->removeByName('Municipalities');
		}
		else {
			$municipalities = $fields->fieldByName('Municipalities');
			if (count($this->Municipalities())) {
				$municipalities->setSource(array('' => _t('AdvancedDropdownField.NONESELECTED', '(None selected)')) + $this->Municipalities()->map('ID', 'Name'));
			}
			else {
				$municipalities->setSource(array('' => _t('AdvancedDropdownField.NONESELECTED', '(None selected)')));
			}
		}
	}
	
	protected function updateSearchParameters(&$searchParameters) {
		//parent::updateSearchParameters($searchParameters);
		
		if (empty($searchParameters['Municipalities'])) {
			$searchParameters['Municipalities'] = implode(',', $this->Municipalities()->column('ID'));
		}
	}
	
	public function EventCategories() {
		$categories = new DataObjectSet();
		
		$eventService = new RemoteDataService();
			
		try {			
			$includeMunicipalities = '';
			if (count($this->Municipalities()))
				$includeMunicipalities = '&Municipalities=' . implode(',', $this->Municipalities()->column('ID'));			
			
			$xml = $eventService->request('categories?Locale=' . i18n::get_locale() . $includeMunicipalities);
			return $eventService->getValues($xml->getBody(), 'Categories', 'Category');
		} 
		catch (Exception $e) {
			
		}
				
		return $categories;
	}
	
	public function CurrentEvents() {
		$events = new DataObjectSet();
		
		$eventService = new RemoteDataService();
			
		try {
			$includeMunicipalities = '';
			if (count($this->Municipalities()))
				$includeMunicipalities = '&Municipalities=' . implode(',', $this->Municipalities()->column('ID'));
			
			$xml = $eventService->request('search?Locale=' . i18n::get_locale() . '&Limit=5' . $includeMunicipalities);
			$events = $eventService->getValues($xml->getBody(), 'Events', 'Event');
		} 
		catch (Exception $e) {
			
		}
	
		return $events->getRange(0, 5);
	}
}

?>
