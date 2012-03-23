<?php
class EventFile extends DataObject {
	
	static $extensions = array(			
		'PermissionExtension',
		'TemporaryDataObjectOwner',
		'TranslatableDataObject'
	);
		
	static $db = array(
		'Title' => 'Varchar(255)',
		'OnlySelectedLocales' => 'Boolean'
	);
	
	static $many_many = array(
		'Locales'	=> 'CalendarLocale',
	);
	
	static $has_one = array(
		'Event' => 'Event',
		'File'	=> 'File',
	);
	
	static $translatableFields = array(
		'Title'
	);
	
	static $defaults = array(
		'OnlySelectedLocales' => false
	);
 	
	function duplicate($doWrite = true) {
		$parentDuplicate = parent::duplicate($doWrite);
		if ($doWrite && $this->File() && $this->File()->exists()) {
			$duplicateFile = $this->File()->DuplicateFile();
			$parentDuplicate->FileID = $duplicateFile->ID;
		}
		return $parentDuplicate;
	}
	
	public function onBeforeDelete() {
		parent::onBeforeDelete();
		
		if ($this->File() && $this->File()->exists())
			$this->File()->delete();
	}
	
	public function getNiceLocale() {
		$locales = $this->Locales();
		if ($locales) {
			$localeNames = array();
			foreach ($locales as $locale) {
				$localeNames[] = $locale->NiceName;
			}
			return implode('<br/>', $localeNames);
		}
		return '';
	}
	
	public function getTitle() {
		$name = '';
		$locales = array();
		$selectedLanguages = $this->Locales();
		foreach ($selectedLanguages as $lang)
			$locales[] = $lang->Locale;
		
		if (!count($locales)) 
			return $this->getField('Title');
		
		// File has a Title in our current locale?
		if (in_array(i18n::get_locale(), $locales)) {
			return $this->getField('Title_' . i18n::get_locale());
		}
				
		// Otherwise return the first language
		$firstLanguage = $selectedLanguages->First();
		return $this->getField('Title_' . $firstLanguage->Locale);
	}	
	
	public function getNiceTitle() {
		$locales = $this->Locales();
		if ($locales) {
			$localeNames = array();		
			foreach ($locales as $locale) {
				$localeNames[] = $this->getField('Title_' . $locale->Locale);
			}
			return implode('<br/>', $localeNames);
		}
		return '';
	}
	
	public function getVisibilityNice() {
		if (!$this->OnlySelectedLocales)
			return _t('Boolean.YES', 'Yes');	
		return _t('Boolean.NO', 'No');
	}
	
	public function getFileIcon() {
		return '<img width="12" src="'.$this->File()->Icon().'" alt="" border="0">';
	}
	
	public function getLink() {
		return $this->File()->Link();
	}
	
	public function getDownloadLink() {
		//return $this->CMSThumbnail();
		if ($this->File() && $this->File()->exists())
			return $this->getFileIcon().'&nbsp;<a class="noClickPropagation" target="_blank" href="' . $this->File()->Link(). '">' . _t('EventFile.DOWNLOAD', 'Download') . '</a>';
		return _t('EventFile.FILE_MISSING', 'File missing');
	}	
	
	public function getRequirementsForPopup() {
		$chosenLocales = '';
		$nonChosen = _t('AdvancedDropdownField.NONESELECTED', '(None selected)');
		if ($this->Locales()) {
			$chosenLocales .= '"' . implode('","', $this->Locales()->column('Locale')) .  '"';
		}
		
		Requirements::customCSS('.horizontal_tabs { margin-top: 0px; }');
		
		$customJS = <<<CUSTOM_JS
			var dialog = top.GetPreviousDialog();
			var iframeContents = dialog.find('.iframe_wrap iframe').contents();
			var languages = iframeContents.find('body').find('input[name=Languages]').val();
			var chosenLocales = new Array($chosenLocales);
			
			jQuery('input[name="Locales"]  + input + select > option').each(function() {
				jQuery(this).addClass('hidden');
			});
			
			jQuery('input[name^="Title"]').attr('disabled', 'disabled').parent().hide();

			var languageText = new Array();
			var languageID = new Array();

			if (languages != '') {
				var langArray = languages.split(',');
				
			
				for (var i=0; i<langArray.length;i++) {
					var locale = languageIDToLocaleMapping[langArray[i]];
					jQuery('input[name="Locales"] + input + select > option[value=' + locale + ']').val(langArray[i]).removeClass('hidden');

					for (var j=0;j<chosenLocales.length;j++) {
						if (chosenLocales[j] == locale) {
							jQuery('input[name="Locales"] + input + select > option[value=' + langArray[i] + ']').addClass('selected')
							languageText.push(jQuery('input[name="Locales"] + input + select > option[value=' + langArray[i] + ']').text());
							languageID.push(langArray[i]);
							
							jQuery('input[name=Title_' + locale + ']').removeAttr('disabled').parent().show();
						}
					}
				}
			}
			
			jQuery('input[name="Locales"]').val(languageID.join(','));
			jQuery('input[name="Locales"] + input').val(languageText.join(', '));
			jQuery('input[name="Locales"] + input + select > option.hidden').remove();
			
			if (jQuery('input[name="Locales"] + input').val() == '')
				jQuery('input[name="Locales"] + input').val('$nonChosen');
CUSTOM_JS;
			
		$eventLanguageLocales = CalendarLocale::toLanguageLocaleList();
			
		$languageMapping = array();
		foreach ($eventLanguageLocales as $key => $value) 
			$languageMapping[] = "'$key': '$value'";
			
		Requirements::customScript('var languageIDToLocaleMapping = { ' . implode(',', $languageMapping) . " };\n" . 'jQuery(document).bind("dialogLoaded", function() { ' . $customJS .  '}); ');
		
		Requirements::javascript('thirdparty/tipsy-0.1.7/src/javascripts/jquery.tipsy.js');
		Requirements::css('thirdparty/tipsy-0.1.7/src/stylesheets/tipsy.css');
		Requirements::customScript('jQuery(function() { jQuery(".tipsy-hint").tipsy({fade: true, gravity: "w", html: true }); });');		
		
		Requirements::css('ecalendar/css/EventFile.css');
		Requirements::javascript('ecalendar/javascript/EventFile.js');
		
		$this->extend('getRequirementsForPopup');
	}	
	
	public function getCMSFields() {		
		$fields = new FieldSet(				
			$DTSet = new DialogTabSet('TabSet',		
					$generalTab = new Tab(
						'GeneralTab', 
						_t('EventFile.GENERALTAB', 'General'),
						new FieldGroup(
							new AdvancedDropdownField('Locales', _t('EventFile.LOCALE', 'Locale'), CalendarLocale::toLocaleDropdownList(), '', false, false, 'selectLocaleDropdown', 'showLocaleDropdown'),
							new LiteralField('', '<span class="tipsy-hint" title="' . _t('EventFile.HINT_LOCALES', 'Choose which languages to use for this file') . '"></span>')
						),
						new CheckboxField('OnlySelectedLocales', _t('EventFile.ONLYSELECTEDLOCALES', 'Only visible in selected languages') . '<span class="tipsy-hint" title="' . _t('EventFile.HINT_ONLYSELECTEDLOCALES', 'If this is checked, this file will only be visible in the selected languages. If not checked, this file will be visible on all languages.') . '"></span><br/><br/>'),
						new TextField('Title', _t('EventFile.TITLE', 'Title')),
						$filefield = new FileUploadField('File', _t('EventFile.SINGULARNAME', 'File')),
						new LabelField('MaxFilesize', sprintf(_t('EventFile.MAXFILESIZE', 'Max filesize: %s'), eCalendarExtension::formatBytes($filefield->getSetting('sizeLimit'))), null, true)
					)	
			)
		);
	
		$filefield->removeFolderSelection();
		$filefield->removeImporting();
		$filefield->setBackend(false);
		$filefield->setUploadFolder('events/attachments');
		
		$this->extend('updateCMSFields', $fields);	
		
		return $fields;
	}
		
	public function getValidator() {
		//return new EventFile_Validator($this);
		return null;
	}
	
	public function validate() {
		$data = Convert::raw2sql($_POST);
		
		if (isset($data['Locales']) && empty($data['Locales'])) {
			return new ValidationResult(false, _t('EventFile.ERROR_LANGUAGES', 'You must select atleast one language'));
		}
		
		if (isset($data['FileID']) && empty($data['FileID'])) {
			return new ValidationResult(false, _t('EventFile.ERROR_FILEMISSING', 'No file has been uploaded'));
		}
		
		$locale_objs = DataObject::get('CalendarLocale');
		$locale_list = array('' => '');
		if ($locale_objs)
			$locale_list = $locale_objs->map('ID', 'Locale');		
		
		$requiredFields = array();
		
		foreach ($locale_list as $locale_id => $locale_locale) 
			if (isset($data['Title_'.$locale_locale])) 
				$requiredFields['Title_'.$locale_locale] = 'EventFile.TITLE';
	
		if (count($requiredFields)) {
			foreach ($requiredFields as $key => $value) {
				if (isset($data[$key]) && empty($data[$key])) {
					return new ValidationResult(false, sprintf(_t('DialogDataObjectManager.FILLOUT', 'Please fill out %s'), _t($value, $value)));
				}
			}		
		}
		
		return parent::validate();
	}	
		
	protected function onBeforeWrite() {
		parent::onBeforeWrite();
		
		$safeData = Convert::raw2sql($_POST);
		
		// Add languages
		if ($this->ID) {
			if (isset($safeData['Locales'])) {
				$languagesIDs = explode(',', $safeData['Locales']);
				$existingLanguages = $this->Locales();
				$idList = array();

				if (is_array($languagesIDs) && count($languagesIDs) > 0) {
					foreach($languagesIDs as $languageID) {
						if (is_numeric($languageID) && DataObject::get_by_id('CalendarLocale', $languageID)) {
							$idList[] = $languageID;
						}
					}
				} 

				$existingLanguages->setByIDList($idList);
			}
		}
		
		// Default file title
		if (isset($_POST['FileID'])) {
			$file = DataObject::get_by_id('File', (int)$_POST['FileID']);
			
			if ($file) {
				if (!count($this->Locales())) {
					if (!strlen($this->Title)) {
						$this->setField('Title_' . i18n::get_locale(), $file->Title);
					}
				}
				else {
					foreach ($this->Locales() as $lang) {
						if (!strlen($this->getField('Title_' . $lang->Locale)))
							$this->setField('Title_' .$lang->Locale, $file->Title);
					}
				}
			}
		}
	}
}

class EventFile_Validator extends RequiredFields {
	protected $eventFile = null;
	
	public function __construct($eventFileObject) { 
		$this->eventFile = $eventFileObject;
		
		parent::__construct(); 
	}
   
	function php($data) { 
		//$valid = parent::php($data); 
		$valid = true;
		if(isset($_REQUEST['ctf']['childID'])) { 
			$id = (int)$_REQUEST['ctf']['childID']; 
		} elseif(isset($_REQUEST['ID'])) { 
			$id = (int)$_REQUEST['ID']; 
		} else { 
			$id = null; 
		} 
		
		if (empty($data['Locales'])) {
			$this->validationError('Locales', _t('EventFile.ERROR_LANGUAGES', 'You must select atleast one language'));
			return false;
		}
		
		if (empty($data['FileID'])) {
			$this->validationError('FileID', _t('EventFile.ERROR_FILEMISSING', 'No file has been uploaded'));
			return false;
		}
		
		$locale_objs = DataObject::get('CalendarLocale');
		$locale_list = array('' => '');
		if ($locale_objs)
			$locale_list = $locale_objs->map('ID', 'Locale');		
		
		$requiredFields = array();
		
		foreach ($locale_list as $locale_id => $locale_locale) 
			if (isset($data['Title_'.$locale_locale])) 
				$requiredFields['Title_'.$locale_locale] = 'EventFile.TITLE';
	
		if (count($requiredFields)) {
			foreach ($requiredFields as $key => $value) {
				if (isset($data[$key]) && empty($data[$key])) {
					$this->validationError($key, sprintf(_t('DialogDataObjectManager.FILLOUT', 'Please fill out %s'), _t($value, $value)));
					return false;
				}
			}		
		}

		return $valid;
	}
}

?>
