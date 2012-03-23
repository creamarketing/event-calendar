<?php
class EventLink extends DataObject {
	
	static $extensions = array(
			'TranslatableDataObject',	
			'PermissionExtension',
			'TemporaryDataObjectOwner'
	);
	
	static $db = array(
			'Name'	=> 'Varchar(255)',
			'Link'	=> 'Varchar(455)',
			'OnlySelectedLocales' => 'Boolean'
	);
	
	static $many_many = array(
		'Locales'	=> 'CalendarLocale',
	);	
	
	static $has_one = array(
			'Event' => 'Event',			
	);
 
	static $translatableFields = array(
			'Name',
			'Link',
	);	
	
	static $defaults = array(
			'Link' => 'http://',
			'OnlySelectedLocales' => false
	);
	
	static $default_sort = 'Name';
	
	public function getRequirementsForPopup() {
		$chosenLocales = '';
		$nonChosen = _t('AdvancedDropdownField.NONESELECTED', '(None selected)');
		if ($this->Locales()) {
			$chosenLocales .= '"' . implode('","', $this->Locales()->column('Locale')) .  '"';
		}		
		
		$customJS = <<<CUSTOM_JS
			var dialog = top.GetPreviousDialog();
			var iframeContents = dialog.find('.iframe_wrap iframe').contents();
			var languages = iframeContents.find('body').find('input[name=Languages]').val();
			var chosenLocales = new Array($chosenLocales);
				
			jQuery('input[name="Locales"]  + input + select > option').each(function() {
				jQuery(this).addClass('hidden');
			});
			
			jQuery('input[name^="Link"]').each(function() {
				if (!jQuery(this).val().length)
					jQuery(this).val('http://');
			});

			jQuery('input[name^="Link"]').attr('disabled', 'disabled').parent().hide();
			jQuery('input[name^="Name"]').attr('disabled', 'disabled').parent().hide();

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
							
							jQuery('input[name=Link_' + locale + ']').removeAttr('disabled').parent().show();
							jQuery('input[name=Name_' + locale + ']').removeAttr('disabled').parent().show();
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
			
		Requirements::customScript('var languageIDToLocaleMapping = { ' . implode(',', $languageMapping) . " };\n" . 'jQuery(document).bind("dialogLoaded", function() { ' . $customJS .  '});');
		
		Requirements::javascript('thirdparty/tipsy-0.1.7/src/javascripts/jquery.tipsy.js');
		Requirements::css('thirdparty/tipsy-0.1.7/src/stylesheets/tipsy.css');
		Requirements::customScript('jQuery(function() { jQuery(".tipsy-hint").tipsy({fade: true, gravity: "w", html: true }); });');				
		
		Requirements::javascript('ecalendar/javascript/EventLink.js');
		Requirements::css('ecalendar/css/EventLink.css');
		
		$this->extend('getRequirementsForPopup');
	}
	
	public function getName() {
		if (!$this->Event())
			return $this->getField('Name');
		
		$name = '';
		$locales = array();
		$selectedLanguages = $this->Event()->Languages();
		foreach ($selectedLanguages as $lang)
			$locales[] = $lang->Locale;
		
		if (!count($locales))
			return $this->getField('Name');
		
		// Link has a Name in our current locale?
		if (in_array(i18n::get_locale(), $locales)) {
			return $this->getField('Name_' . i18n::get_locale());
		}
				
		// Otherwise return the first language
		$firstLanguage = $selectedLanguages->First();
		return $this->getField('Name_' . $firstLanguage->Locale);
	}	
	
	public function getLink() {
		if (!$this->Event())
			return $this->getField('Link');
		
		$name = '';
		$locales = array();
		$selectedLanguages = $this->Event()->Languages();
		foreach ($selectedLanguages as $lang)
			$locales[] = $lang->Locale;
		
		if (!count($locales))
			return $this->getField('Link');
		
		// Link has a Link in our current locale?
		if (in_array(i18n::get_locale(), $locales)) {
			return $this->getField('Link_' . i18n::get_locale());
		}
				
		// Otherwise return the first language
		$firstLanguage = $selectedLanguages->First();
		return $this->getField('Link_' . $firstLanguage->Locale);
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
	
	public function getNiceName() {
		$locales = $this->Locales();
		if ($locales) {
			$localeNames = array();		
			foreach ($locales as $locale) {
				$localeNames[] = $this->getField('Name_' . $locale->Locale);
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
	
	public function getCMSFields() {		
		$fields = new FieldSet(				
			$DTSet = new DialogTabSet('TabSet',		
					$generalTab = new Tab(
						'GeneralTab', 
						_t('EventLink.GENERALTAB', 'General'),						
						new FieldGroup(
							new AdvancedDropdownField('Locales', _t('EventLink.LOCALE', 'Locale'), CalendarLocale::toLocaleDropdownList(), '', false, false, 'selectLocaleDropdown', 'showLocaleDropdown'),
							new LiteralField('', '<span class="tipsy-hint" title="' . _t('EventLink.HINT_LOCALES', 'Choose which languages to use for this file') . '"></span>')
						),
						new CheckboxField('OnlySelectedLocales', _t('EventLink.ONLYSELECTEDLOCALES', 'Only visible in selected languages') . '<span class="tipsy-hint" title="' . _t('EventLink.HINT_ONLYSELECTEDLOCALES', 'If this is checked, this file will only be visible in the selected languages. If not checked, this file will be visible on all languages.') . '"></span><br/><br/>'),							
						new TextField('Name', _t('EventLink.NAME', 'Name')),
						new TextField('Link', _t('EventLink.LINK', 'Link'))
					)	
			)
		);
	
		$this->extend('updateCMSFields', $fields);
		
		return $fields;
	}
		
	public function validate() {
		$data = Convert::raw2sql($_POST);
		
		if (isset($data['Locales']) && empty($data['Locales'])) {
			return new ValidationResult(false, _t('EventLink.ERROR_LANGUAGES', 'You must select atleast one language'));
		}		
		
		$locales = Translatable::get_allowed_locales();
		$hasone = false;
		foreach ($locales as $locale) {
			if (isset($data['Link_'.$locale])) {
				if (empty($data['Link_'.$locale]))
					$hasone = true;
				else if (!preg_match("#^(https|http|ftp)?://[a-z0-9-_.]+\.[a-z]{2,4}#i", $data['Link_'.$locale])) {
					return new ValidationResult(false, _t('EventLink.ERROR_LINKFORMAT', 'Invalid URL'));
				}
			}
		}
		
		if ($hasone == true) {
				return new ValidationResult(false, sprintf(_t('DialogDataObjectManager.FILLOUT', 'Please fill out %s'), _t('EventLink.LINK', 'Link')));
		}
		return parent::validate();
	}
	
	public function getClickableLink() {
		return '<a class="noClickPropagation" target="_blank" href="' . $this->Link . '">' . $this->Link . '</a>';
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
	}
}

?>
