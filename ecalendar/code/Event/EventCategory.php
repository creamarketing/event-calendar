<?php
class EventCategory extends DataObject {
	
	static $extensions = array(
		'TranslatableDataObject',
		'PermissionExtension',		
	);
	
	static $db = array(
		'Name'	=> 'Varchar(255)',
		'Inactive' => 'Boolean',
		'AlwaysLast' => 'Boolean',
	);
	
	static $belongs_many_many = array(
		'Events' => 'Event',	
	);	
 
	static $translatableFields = array(
		'Name',
	);	
	
	static $default_sort = 'Name';
	
	static public function toDropdownList() {
		$eventcategory_objs = DataObject::get('EventCategory', 'Inactive = 0', 'AlwaysLast ASC, Name ASC');
		$eventcategories = array('' => _t('AdvancedDropdownField.NONESELECTED', '(None selected)'));
		if ($eventcategory_objs) {				
			foreach ($eventcategory_objs as $eventcategory) {
				$eventcategories[$eventcategory->ID] = $eventcategory->Name;
			}   
		}
		return $eventcategories;
	}
		
	public function getCMSFields() {		
		
		$fields = new FieldSet(				
		$DTSet = new DialogTabSet('TabSet',		
				$generalTab = new Tab('GeneralTab', _t('EventCategory.GENERALTAB', 'General'),
					new TextField('Name', _t('EventCategory.NAME', 'Name')),
					new CheckboxField('AlwaysLast', _t('EventCategory.ALWAYSLAST', 'Always last'))
			 	)			
			)
		);
	
		$this->extend('updateCMSFields', $fields);
		return $fields;
	}
	
	public function delete() {
		$this->Inactive = true;
		$this->write();
		return false;
	}
	
	static public function getIDFromAlwaysLast() {
		$category = DataObject::get_one('EventCategory', "AlwaysLast = 1");
		if ($category)
			return $category->ID;
		return 0;
	}
	
	static public function getIDFromName($name, $fallbackToAlwaysLast = false) {
		$locales = DataObject::get('CalendarLocale');
		$originalLocale = i18n::get_locale();

		if ($locales) {
			foreach ($locales as $locale) {
				i18n::set_locale($locale->Locale);
				$category = DataObject::get_one('EventCategory', "`Name_{$locale->Locale}` LIKE '%" . $name . "%'");
				if ($category) {
					i18n::set_locale($originalLocale);
					return $category->ID;
				}
			}
		}
		i18n::set_locale($originalLocale);
		if ($fallbackToAlwaysLast)
			return self::getIDFromAlwaysLast();
		return 0;
	}	
}

?>