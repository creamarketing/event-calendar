<?php
class Municipal extends DataObject {

	static $extensions = array(
		'TranslatableDataObject',
		'PermissionExtension',	
	);

	static $db = array(
		'Name'	=> 'Varchar(255)', 
		'AlwaysLast' => 'Boolean'
	);

	static $translatableFields = array(
		'Name',
	);
	
	static $summary_fields = array(
		'Name',
		'CreatedNice',
		'LastEditedNice',
	);

	static $has_many = array(
		'Events' => 'Event'
	);
	
	static $belongs_many_many = array(
		'AssociationOrganizers' => 'AssociationOrganizer',
		'EventPages' => 'EventPageCustomizable'
	);

	static $default_sort = 'Name';
	
	public function forTemplate() {
		return $this->Name;	
	}
	
	public function getCreatedNice() {
		return date('d.m.Y H:i', strtotime($this->Created));
	}

	public function getLastEditedNice() {
		return date('d.m.Y H:i', strtotime($this->LastEdited));
	}

	static public function toDropdownList() {
		$muncipal_objs = DataObject::get('Municipal', '', 'AlwaysLast ASC, Name ASC');
		$municipals = array('' => _t('AdvancedDropdownField.NONESELECTED', '(None selected)'));
		if ($muncipal_objs)
			foreach ($muncipal_objs as $municipal) {
				$municipals[$municipal->ID] = $municipal->Name;
			}
		return $municipals;
	}
	
	static public function getIDFromAlwaysLast() {
		$municipality = DataObject::get_one('Municipal', "AlwaysLast = 1");
		if ($municipality)
			return $municipality->ID;
		return 0;
	}	

	static public function getIDFromName($name, $fallbackToAlwaysLast = false) {
		$locales = DataObject::get('CalendarLocale');
		$originalLocale = i18n::get_locale();
		
		if ($locales) {
			foreach ($locales as $locale) {
				i18n::set_locale($locale->Locale);
				$municipality = DataObject::get_one('Municipal', "`Name_{$locale->Locale}` LIKE '%" . $name . "%'");
				if ($municipality) {
					i18n::set_locale($originalLocale);
					return $municipality->ID;
				}
			}
		}
		i18n::set_locale($originalLocale);
		if ($fallbackToAlwaysLast)
			return self::getIDFromAlwaysLast();
		return 0;
	}	
	
	public function getCMSFields() {	
	
		$dbmgr = new DialogManyManyDataObjectManager(
			$this, 
			'AssociationOrganizers', 
			'AssociationOrganizer', 
			array(
				'Fullname' => _t('AssociationOrganizer.NAME', 'Name'),						
				'Association' => _t('Association.SINGULARNAME', 'Association'),		
			)
		);	
		$dbmgr->setPluralTitle( _t('eCalendarAdmin.MUNICIPALADMINS', 'Municipal moderators') );
		
		$fields = new FieldSet(	
			$DTSet = new DialogTabSet('TabSet',		
				$generalTab = new Tab('GeneralTab', _t('Municipal.GENERALTAB', 'General'),
					new TextField('Name', _t('Municipal.NAME', 'Name')),
					new CheckboxField('AlwaysLast', _t('EventCategory.ALWAYSLAST', 'Always last')),
					$dbmgr							
			 	)			
			)
		);
		
		$this->extend('updateCMSFields', $fields);
		return $fields;
	}

}
?>