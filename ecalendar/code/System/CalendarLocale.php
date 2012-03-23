<?php
class CalendarLocale extends DataObject {
  
  static $extensions = array(  
    'PermissionExtension',
  );
  
  static $db = array(
  	'Locale' => 'Varchar(5)',
  	'Name'	=> 'Varchar(255)',   
  	'IsDefault'	=> 'Boolean',   
  );
  
  static $belongs_many_many = array(
    'Events' => 'Event',  
	'EventFiles' => 'EventFile',
	'EventLinks' => 'EventLink'
  );  
 	   	 
  static $summary_fields = array(
  	'Name',
  	'IsDefault',
  );
  
  public function getNiceName() {
		return _t('CalendarLocale.LANGUAGE_' . strtoupper($this->Name), $this->Name);
  }
  
  public function getCMSFields() {  	
  	$locales = Translatable::get_allowed_locales();
		$languages = array();
		foreach ($locales as $locale) {
			$languages[$locale] = _t('CalendarLocale.LANGUAGE_' . strtoupper(i18n::get_language_name(i18n::get_lang_from_locale($locale))), i18n::get_language_name(i18n::get_lang_from_locale($locale)));
		}
		
	  	$fields = new FieldSet(      	
			$DTSet = new DialogTabSet('TabSet',		
		      	$generalTab = new Tab('GeneralTab', _t('CalendarLocale.GENERALTAB', 'General'),
					new ListboxField('Locale', _t('CalendarLocale.LOCALE', 'Language'), $languages),						
					new CheckBoxField('IsDefault', _t('CalendarLocale.ISDEFAULT', 'Is default shown'))							
		     	)			
			)
		);
	  	
		return $fields;
  }
    
  function getValidator() {  
		return new Locale_Validator(); 
  }
	
  static public function toDropdownList() {
		$locales = DataObject::get('CalendarLocale', null, 'IsDefault DESC, SortOrder');
		$languages = array('' => _t('AdvancedDropdownField.NONESELECTED', '(None selected)'));
		if ($locales)
			foreach ($locales as $calLocale) {
				$languages[$calLocale->ID] = _t('CalendarLocale.LANGUAGE_' . strtoupper($calLocale->Name), $calLocale->Name);
			}
		return $languages;
  }
  
  static public function toLocaleDropdownList() {
		$locales = DataObject::get('CalendarLocale', null, 'IsDefault DESC, SortOrder');
		$languages = array();
		if ($locales)
			foreach ($locales as $calLocale) {
				$languages[$calLocale->Locale] = _t('CalendarLocale.LANGUAGE_' . strtoupper($calLocale->Name), $calLocale->Name);
			}
		return $languages;
  }
  
  static public function getIDFromLocale($locale = 'en_US') {
	  $locales = DataObject::get('CalendarLocale');
      if ($locales) {
		  foreach ($locales as $localeObject)
			  if ($localeObject->Locale == $locale)
				  return $localeObject->ID;
	  }
	  return 0;
  }
  
  static public function getIDFromLanguage($lang = 'en') {
	  return self::getIDFromLocale(i18n::get_locale_from_lang($lang));
  }
  
  
	static function toLanguageLocaleList() {
		$objects = DataObject::get('CalendarLocale', null, 'IsDefault DESC, SortOrder');
		$list = array();

		if (count($objects))
			foreach($objects as $obj) {
				$list[$obj->ID] = $obj->Locale;
			}	

		return $list;
	}  
  
  protected function onBeforeWrite() {
		parent::onBeforeWrite();
	 	$this->Name = i18n::get_language_name(i18n::get_lang_from_locale($this->Locale)); 	
  }	
		
}

class Locale_Validator extends RequiredFields {

   protected $customRequired = array('Locale');

   /**
    * Constructor
    */
   public function __construct() {
      $required = func_get_args();
      if(isset($required[0]) && is_array($required[0])) {
         $required = $required[0];
      }
      $required = array_merge($required, $this->customRequired);

      parent::__construct($required);
   }
   
   function php($data) {
      $valid = parent::php($data);
      $locale_SQL = Convert::raw2sql($data['Locale']);
    	if(isset($_REQUEST['ctf']['childID'])) {
         $id = $_REQUEST['ctf']['childID'];
      } elseif(isset($_REQUEST['ID'])) {
         $id = $_REQUEST['ID'];
      } else {
         $id = null;
      } 
      
      $ex_locale = DataObject::get_one("CalendarLocale", "`Locale` = '{$locale_SQL}'");
      
      if($ex_locale) {
         // Existing locale         
         if ($ex_locale->ID != $id || $id == '') {
	     	$this->validationError("Locale", "Sorry, already added");
	     	$valid = false;        
         }
      }      
      return $valid;
   }
}

?>