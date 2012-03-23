<?php
class Event extends DataObject {
	
	static $extensions = array(
		'TranslatableDataObject',
		'CreaDataObjectExtension',
		'PermissionExtension',		
	);
	
	static $db = array(
		'Start' => 'SS_Datetime',
		'End' => 'SS_Datetime',
		'PublishedDate' => 'SS_Datetime',
		'Title'	=> 'Varchar(255)',
		'Place' => 'Varchar(255)',
		'PostalAddress' => 'Varchar', 
		'PostalCode' => 'Int',
		'PostalOffice' => 'Varchar',		
		'GoogleMAP'=>'Varchar(255)',
		'ShowGoogleMap' => 'Boolean',
		'Latitude' => 'Varchar(30)',
		'Longitude' => 'Varchar(30)',
		'EventTextShort' => 'Varchar(120)',
		'EventText' => 'Text',
		'Homepage' => 'Varchar(355)',
		'Status' => 'Enum("Preliminary,Accepted,Rejected,Cancelled,Draft","Preliminary")',		
		'PriceType' => 'Enum("Free,NotFree","Free")',
		'PriceText' => 'Varchar(140)',
		'PublishedOnce' => 'Boolean',
		
		'FeedGUID' => 'Varchar(255)',
		
		'Vasabladet_PublishTo' => 'Boolean',
		'Vasabladet_Municipality' => 'Int',
		'Vasabladet_Category' => 'Int',
		'Vasabladet_SubCategory' => 'Int',
		'Vasabladet_ShortText' => 'Varchar(120)',
		'Vasabladet_AdditionalInfo' => 'Boolean',
		'Vasabladet_Text' => 'Text',
		'Vasabladet_Organizer' => 'Varchar(120)',
		'Vasabladet_URL' => 'Varchar(355)',
		'Vasabladet_Address' => 'Varchar(255)',
		
		'Pohjalainen_PublishTo' => 'Boolean',
		'Pohjalainen_Category' => 'Int',
		'Pohjalainen_MunicipalityZIP' => 'Int',
		'Pohjalainen_PostInIlkka' => 'Boolean',
		'Pohjalainen_Title' => 'Varchar(255)',
		'Pohjalainen_ShortText' => 'Varchar(200)',
		'Pohjalainen_HasText' => 'Boolean',
		'Pohjalainen_Text' => 'Text',
		'Pohjalainen_URL' => 'Varchar(355)',
		'Pohjalainen_Place' => 'Varchar(255)',
		'Pohjalainen_Address' => 'Varchar(255)',
		
		'NetTicket_PublishTo' => 'Boolean',
		'NetTicket_AcceptTerms' => 'Boolean'
	);
	
	static $has_one = array(
		'Association' => 'Association', 
		'Organizer' => 'AssociationOrganizer', 	
		'Creator' => 'Member', 	
		'Municipal' => 'Municipal',
	);
	
	static $defaults = array(
		'GoogleMAP' => 'Finland',
		'Status' => 'Draft',
		'ShowGoogleMap' => true,
		'PublishedOnce' => false,
		'Vasabladet_AdditionalInfo' => false,
		'Pohjalainen_HasText' => false
	);
	
	static $has_many = array(		
		'RepeatDates' => 'EventDate',
		'EventImages' => 'EventImage',	
		'EventLinks'	=> 'EventLink',
		'EventFiles'	=> 'EventFile'
	);
	
	 static $many_many = array(
		'Categories' => 'EventCategory', 
	 	'Languages' => 'CalendarLocale',
	);
	
	 
	static $translatableFields = array(
		'Title',
		'EventText',
		'EventTextShort',
		'Place',
		'PriceText',
	);	
	
	static $default_sort = 'LastEdited DESC';
	
	static $sortfield_override = array(
		'StartNice' => 'Start',
		'EndNice' => 'End',
		'CreatedNice' => 'Created',
		'LastEditedNice' => 'LastEdited',
		'PublishedDateNice' => 'PublishedDate'
	);		
	
	static $api_access = true;
	
	private $justCreated = false;
	
	public static $maxNumberOfImages = 3;
	public static $maxNumberOfFiles = 6;
	public static $NetTicket_EmailAddress = '';
	public static $NetTicket_EmailLocale = 'sv_SE';
	
	public function getSubjectToCharge() {
		return ($this->PriceType == 'Free'?false:true);
	}

	public function getTitle() {
		$name = '';
		$locales = array();
		$selectedLanguages = $this->Languages();
		foreach ($selectedLanguages as $lang)
			$locales[] = $lang->Locale;
		
		if (!count($locales))
			return $this->getField('Title');
		
		// Event has a Title in our current locale?
		if (in_array(i18n::get_locale(), $locales)) {
			return $this->getField('Title_' . i18n::get_locale());
		}
				
		// Otherwise return the first language
		$firstLanguage = $selectedLanguages->First();
		return $this->getField('Title_' . $firstLanguage->Locale);
	}
	
	public function getPriceText() {
		$name = '';
		$locales = array();
		$selectedLanguages = $this->Languages();
		foreach ($selectedLanguages as $lang)
			$locales[] = $lang->Locale;
		
		if (!count($locales))
			return $this->getField('PriceText');
		
		// Event has a PriceText in our current locale?
		if (in_array(i18n::get_locale(), $locales)) {
			return $this->getField('PriceText_' . i18n::get_locale());
		}
				
		// Otherwise return the first language
		$firstLanguage = $selectedLanguages->First();
		return $this->getField('PriceText_' . $firstLanguage->Locale);
	}	
	
	public function getDataExportPriceText() {
		$text = $this->PriceText;
		return str_replace(array("\r\n", "\n\r", "\n", "\r", "\t"), ', ', $text);
	}
	
	public function getDataExportPlaceDetails() {
		$result = array();
		
		$place = $this->Place;
		$municipalityName = $this->Municipal()->Name;
		$postalAddress = $this->PostalAddress;
		
		if (strlen($place))
			$result[] = $place;
		if (strlen($postalAddress))
			$result[] = $postalAddress;				
		if (strlen($municipalityName))
			$result[] = $municipalityName;		
		
		return implode(', ', $result);
	}
	
	public function getEventText() {
		$name = '';
		$locales = array();
		$selectedLanguages = $this->Languages();
		foreach ($selectedLanguages as $lang)
			$locales[] = $lang->Locale;
		
		if (!count($locales))
			return $this->getField('EventText');
		
		// Event has a EventText in our current locale?
		if (in_array(i18n::get_locale(), $locales)) {
			return $this->getField('EventText_' . i18n::get_locale());
		}
				
		// Otherwise return the first language
		$firstLanguage = $selectedLanguages->First();
		return $this->getField('EventText_' . $firstLanguage->Locale);
	}	
	
	public function getEventTextShort() {
		$name = '';
		$locales = array();
		$selectedLanguages = $this->Languages();
		foreach ($selectedLanguages as $lang)
			$locales[] = $lang->Locale;
		
		if (!count($locales))
			return $this->getField('EventTextShort');
		
		// Event has a EventTextShort in our current locale?
		if (in_array(i18n::get_locale(), $locales)) {
			return $this->getField('EventTextShort_' . i18n::get_locale());
		}
				
		// Otherwise return the first language
		$firstLanguage = $selectedLanguages->First();
		return $this->getField('EventTextShort_' . $firstLanguage->Locale);
	}	
	
	public function getPlace() {
		$name = '';
		$locales = array();
		$selectedLanguages = $this->Languages();
		foreach ($selectedLanguages as $lang)
			$locales[] = $lang->Locale;
		
		if (!count($locales))
			return $this->getField('Place');
		
		// Event has a Place in our current locale?
		if (in_array(i18n::get_locale(), $locales)) {
			return $this->getField('Place_' . i18n::get_locale());
		}
				
		// Otherwise return the first language
		$firstLanguage = $selectedLanguages->First();
		return $this->getField('Place_' . $firstLanguage->Locale);
	}	
		
	public function getDatesStart() {
		$dates = $this->RepeatDates();
		
		if ($dates->Count() == 0)
			return $this->Created;
		
		$dates->sort('SortStartTime');
		$first = $dates->First();
		
		return $first->SortStartTime;
	}
	
	public function getDatesEnd() {
		$dates = $this->RepeatDates();
		
		if ($dates->Count() == 0)
			return $this->Created;
		
		$dates->sort('SortStartTime');
		$last = $dates->Last();
		return $last->SortEndTime;
	}	
	
	public function getPeriodNice() {
		if ($this->Start == $this->End)
			return $this->getStartNice();
		else
			return date('d.m.Y H:i', strtotime($this->Start)) . ' - ' . date('d.m.Y H:i', strtotime($this->End));
	}
	
	public function getDatePeriodNice() {
		if ($this->Start == $this->End)
			return date('d.m.Y', strtotime($this->Start));
		else
			return date('d.m.Y', strtotime($this->Start)) . ' - ' . date('d.m.Y', strtotime($this->End));
	}	
	
	public function getPeriodNiceWithBr() {
		return str_replace(' - ', '<br/>', $this->getPeriodNice());
	}
	
	public function getHasUniqueShortText() {
		if ($this->Title == $this->EventTextShort)
			return false;
		return true;
	}
	
	public function getDataExportDate() {
		$result = '';
		
		if ($this->Start == $this->End) {
			$date = date('j.n.Y', strtotime($this->Start)); 
			$time = date('H:i', strtotime($this->Start)); 
			$result .= $date . ($time != '00:00' ? (' ' . _t('DataExport.CLOCK', 'at') . ' ' . $time) : '');
		}
		else if (date('d.m.Y', strtotime($this->Start)) == date('d.m.Y', strtotime($this->End)) &&
				 date('H:i', strtotime($this->Start)) != date('H:i', strtotime($this->End))) {
			// Same date, but different clock?
			$result .= date('j.n.Y', strtotime($this->Start)) . ' ' . _t('DataExport.CLOCK', 'at') . ' ' . date('H:i', strtotime($this->Start)) . ' - ' . date('H:i', strtotime($this->End));
		}
		else {
			// Period
			$result .= date('j.n.Y', strtotime($this->Start)) . ' - ' . date('j.n.Y', strtotime($this->End));
			
			$weekdays = $this->RepeatDates()->groupBy('ShortWeekDayUgly');
			$weekDaysShort = array_keys($weekdays);
			sort($weekDaysShort);
			foreach ($weekDaysShort as &$tmpWeekday) {
				$tmpWeekday = substr($tmpWeekday, 3, strlen($tmpWeekday)-3);
			}
			$result .= ' ' . strtolower(implode(', ', $weekDaysShort));
		}
		
		return $result;
	}
	
	public function getDataExportMoreDates() { 
		$result = new DataObjectSet();
		$allDates = $this->RepeatDates();
		
		if ($allDates->Count() <= 1)
			return false;
		
		foreach ($allDates as $currentDate) {
			if ($currentDate->StartTime == $currentDate->EndTime && $currentDate->StartTime == '00:00:00')
				$result->push(new ArrayData(array(
					'Nice' => date('j.n.Y', strtotime($currentDate->Date)) . ' ' . strtolower($currentDate->ShortWeekDayNice)
				)));
			else if ($currentDate->StartTime == $currentDate->EndTime && $currentDate->StartTime != '00:00:00')
				$result->push(new ArrayData(array(
					'Nice' => date('j.n.Y', strtotime($currentDate->Date)) . ' ' . strtolower($currentDate->ShortWeekDayNice) . ' ' . _t('DataExport.CLOCK', 'at') . ' ' . substr($currentDate->StartTime, 0, 5)
				)));
			else {
				$result->push(new ArrayData(array(
					'Nice' => date('j.n.Y', strtotime($currentDate->Date)) . ' ' . strtolower($currentDate->ShortWeekDayNice) . ' ' . _t('DataExport.CLOCK', 'at') . ' ' . substr($currentDate->StartTime, 0, 5) . ' - ' . substr($currentDate->EndTime, 0, 5)
				)));
			}
		}
		return new ArrayData(array('Dates' => $allDates, 'PrettyDates' => $result));
	}
	
	public function getStartNice() {
		return date('d.m.Y H:i', strtotime($this->Start));
	}
	
 	public function getEndNice() {
		return date('d.m.Y H:i', strtotime($this->End));
	}
	
	public function getCreatedNice() {
		return date('d.m.Y H:i', strtotime($this->Created));
	}
	
	public function getLastEditedNice() {
		return date('d.m.Y H:i', strtotime($this->LastEdited));
	}

	public function getPublishedDateNice() {
		if (strlen($this->PublishedDate))
			return date('d.m.Y H:i', strtotime($this->PublishedDate));
		return '';
	}	
	
	public function getClosestDate() {
		$dates = $this->RepeatDates();
		$closestDate = null;
		
		if ($dates->exists()) {
			foreach ($dates as $date) {
				if ($date->Date < date('Y-m-d'))
					continue;

				$closestDate = $date;
				break;
			}
			if (!$closestDate)
				$closestDate = $dates->Last();		
		}
		
		return $closestDate;
	}
	
	function getWizardValidationRules() {
		Requirements::customScript('
			// Validation
			/*validationRules["input[name=Start[date]], input[name=End[date]]"] = { 
				"regularExpressions": [
					{ expression: /^\d\d?.\d\d?.\d\d\d\d$/, errormessage: "" }
				]
			};	
			validationRules["input[name=Start[time]], input[name=End[time]]"] = { 
				"regularExpressions": [
					{ expression: /^([0-1][0-9]|[2][0-3]):([0-5][0-9])$/, errormessage: "" }
				]
			};*/
			/*validationRules["input[name=Languages] + input.AdvancedDropdown"] = "";
			validationRules["input[name=Categories] + input.AdvancedDropdown"] = "";
			validationRules["input[name=OrganizerID] + input.AdvancedDropdown"] = "";
			validationRules["input[name=Status]"] = {
				"jsFunctions": [
					{ function: function(values) { 
						var oneIsSelected = values.radioButtons.filter(":checked");
						if (oneIsSelected.length) 
							return { valid: true } 
						else 
							return {valid: false, message: "" }
						}, 
						values: function() {
							return { radioButtons: jQuery("input[name=Status]") }
						}
					}
				]
			};
			validationRules["input[name^=\'Title_\']:visible"] = "";
			
			validationRules["input[name^=\'EventTextShort_\']:visible"] = "";
			validationRules["textarea[name^=\'EventText_\']:visible"] = "";
			validationRules["input[name=Municipal] + input.AdvancedDropdown"] = "";
			validationRules["input[name^=\'Place_\']:visible"] = "";
			*/
			validationRules["input[name=EventDates]"] = "' . _t('Event.ERROR_EVENTDATESMISSING', 'Event dates missing') . '";			
		');
	}
	
	function getRequirementsForPopup() {	
		if ($this->isDOMAddForm()) {
			$decorator = '_add';
		} 
		else if ($this->isDOMDuplicateForm()) {
			$decorator = '_duplicate';
		}
		else {
			$decorator = '_edit';
		}
		
		$localeObjs = DataObject::get('CalendarLocale'); // Maps the languages for javascript use
		$languageMapping = '';
		if ($localeObjs)
			foreach ($localeObjs as $calendarLocale) {
				$languageMapping.= "languageMapping[{$calendarLocale->ID}] = '{$calendarLocale->Locale}';\n";	
			}
		
		$translatableFields = '';
			foreach (self::$translatableFields as $dummy => $fieldName) {
			$translatableFields.= "translatableFields[$dummy] = '{$fieldName}';\n\t\t";	
		}
				
		Requirements::customScript("
			var languageMapping = new Array();
			$languageMapping
	
			var translatableFields = new Array();
			$translatableFields
		");
			
		Requirements::javascript("ecalendar/javascript/jquery.numeric.js");		
		Requirements::javascript("ecalendar/javascript/jquery.charlimit.js");		
		
		Requirements::css("ecalendar/css/CustomDatepicking.css"); 			
		Requirements::javascript("ecalendar/javascript/CustomDatepicking.js"); 			
		Requirements::javascript('ecalendar/javascript/datejs/date-' . str_replace('_', '-', i18n::get_locale()) . '.js');			
		Requirements::javascript('sapphire/thirdparty/jquery-livequery/jquery.livequery.js');

		$extendedEdit = $this->extendedCan('canEdit', Member::CurrentUser());
		$normalEdit = $this->canEdit(Member::CurrentUser());
				
		if ($decorator == '_edit' && ($extendedEdit || ($normalEdit === null && $normal))) {
			// Add existing dates
			$dates = $this->RepeatDates();
			$addDates = array();
					
			if ($dates) {		
				foreach ($dates as $date) {
					if ($date->StartTime != $date->EndTime) {
						$addDates[] = "AddDateToList(Date.parseExact('{$date->Date} {$date->StartTime}', 'yyyy-MM-dd HH:mm:ss'), Date.parseExact('{$date->Date} {$date->EndTime}', 'yyyy-MM-dd HH:mm:ss'), {$date->ID});";
					}
					else
						$addDates[] = "AddDateToList(Date.parseExact('{$date->Date} {$date->StartTime}', 'yyyy-MM-dd HH:mm:ss'), null, {$date->ID});";
				}
				$addDates[] = 'SortDatesList();';
				Requirements::customScript('jQuery(document).bind("dialogLoaded", function() { ss.i18n.init(); ' . implode(' ', $addDates) . ' }); ');				
			}
		}
		
		Requirements::javascript("ecalendar/javascript/EventDialog.js");
		Requirements::javascript("ecalendar/javascript/EventDialog$decorator.js");
		
		Requirements::javascript(SAPPHIRE_DIR . "/javascript/i18n.js");
		Requirements::add_i18n_javascript('ecalendar/javascript/lang');
		
		Requirements::css('ecalendar/css/EventDialog.css');		
		
		Requirements::javascript('thirdparty/tipsy-0.1.7/src/javascripts/jquery.tipsy.js');
		Requirements::css('thirdparty/tipsy-0.1.7/src/stylesheets/tipsy.css');
		Requirements::customScript('jQuery(function() { jQuery(".tipsy-hint").tipsy({fade: true, gravity: "w", html: true }); });');
			
		if ($this->editAsDraft()) {
			if ($this->Status == 'Accepted' || ($this->Status == 'Preliminary' && (!$this->canPublish() || !$this->canAssociationPublish())))
				Requirements::customScript('top.setVisibleDraftButtons("[unpublish],[save]"); ');
			else
				Requirements::customScript('top.setVisibleDraftButtons("[publish],[save]"); ');
		}
		
		$this->getRequirementsForVasabladet();
		
		$this->extend('getRequirementsForPopup');
	}	
		
	public function editAsDraft() {
		$extended = $this->ExtendedCan('CanEdit', Member::CurrentUser());
		$normal = $this->CanEdit(Member::CurrentUser());
		
		if ($extended || ($extended === null && $normal))
			return true;
		
		$extended = $this->ExtendedCan('CanCreate', Member::CurrentUser());
		$normal = $this->CanCreate(Member::CurrentUser());		
		
		if ($extended || ($extended === null && $normal))
			return true;	
		
		return false;
	}
	
	public function canAssociationPublish($override_association = null) {
		$association = $this->Association();
		if ($override_association)
			$association = $override_association;
		
		if ($association->Status != 'Active')
			return false;
		
		return true;
	}
	
	public function canPublish() {
		$organizer = $this->Organizer();
		$association = $this->Association();
		$currentMember = Member::currentUser();
		
		if (eCalendarExtension::isAdmin())
			return true;
		
		if ($organizer && $association && $currentMember && $currentMember instanceof AssociationOrganizer) {
			if ($organizer->ID == $currentMember->ID && $currentMember->canPublish()) 
				return true;
			else if ($organizer->ID == $currentMember->ID && !$currentMember->canPublish())
				return false;
						
			$myAssociations = $this->getMyAssociations($currentMember, 'moderators');
			
			$permissions = $currentMember->AssociationPermissions("Type = 'Moderator' AND AssociationID IN ('".implode("','", $myAssociations)."')");
			if ($permissions && $permissions->Count()) 
				return true;
			
			$municipals = $currentMember->MunicipalPermissions('MunicipalID = ' . (int)$this->MunicipalID);
			if ($municipals && $municipals->Count()) 
				return true;
		}
		return false;
	}
	
	private function resetOtherMedias() {
		$this->Vasabladet_PublishTo = false;
		$this->Vasabladet_Municipality = 0;
		$this->Vasabladet_Category = 0;
		$this->Vasabladet_SubCategory = 0;
		$this->Vasabladet_ShortText = '';
		$this->Vasabladet_AdditionalInfo = false;
		$this->Vasabladet_Text = '';
		$this->Vasabladet_Organizer = '';
		$this->Vasabladet_URL = '';
		$this->Vasabladet_Address = '';
		
		$this->Pohjalainen_PublishTo = false;
		$this->Pohjalainen_Category = 0;
		$this->Pohjalainen_MunicipalityZIP = 0;
		$this->Pohjalainen_PostInIlkka = false;
		$this->Pohjalainen_Title = '';
		$this->Pohjalainen_ShortText = '';
		$this->Pohjalainen_HasText = false;
		$this->Pohjalainen_Text = '';
		$this->Pohjalainen_URL = '';
		$this->Pohjalainen_Place = '';
		$this->Pohjalainen_Address = '';
		
		$this->NetTicket_PublishTo = false;
		$this->NetTicket_AcceptTerms = false;
	}
	
	public function onBeforeDuplicate($sourceID) {
		$this->DuplicateFrom = DataObject::get_by_id('Event', $sourceID);
		
		$this->FeedGUID = '';
		$this->resetOtherMedias();
	}	

	public function onAfterDuplicate($sourceID) {
		$this->DuplicateFrom = DataObject::get_by_id('Event', $sourceID);
		if (!$this->DuplicateFrom) return;
		
		$eventLinks = $this->DuplicateFrom->EventLinks();
		if ($eventLinks) {
			foreach ($eventLinks as $eventLink) {
				$duplicateLink = $eventLink->duplicate(false);
				$duplicateLink->EventID = $this->ID;
				$duplicateLink->write();

				$this->EventLinks()->add($duplicateLink);
			}
		}
		
		$eventFiles = $this->DuplicateFrom->EventFiles();
		if ($eventFiles) {
			foreach ($eventFiles as $eventFile) {
				$duplicateFile = $eventFile->duplicate(true);
				$duplicateFile->EventID = $this->ID;
				$duplicateFile->write();

				$this->EventFiles()->add($duplicateFile);
			}
		}	
		
		$eventImages = $this->DuplicateFrom->EventImages();
		if ($eventImages) {
			foreach ($eventImages as $eventImage) {
				$duplicateImage = $eventImage->duplicate(true);
				$duplicateImage->EventID = $this->ID;
				$duplicateImage->write();

				$this->EventImages()->add($duplicateImage);
			}
		}		
	}
	
	public function getReadonlyFields() {
		$fields = new FieldSet();
		// Make some hidden fields to use in preview tab
		$localeList = array();
		foreach ($this->Languages() as $lang)
			$localeList[] = $lang->Locale;
						
		$fields->push(new HiddenField('Languages', '', implode(',', $this->Languages()->getIdList())));
		$fields->push(new HiddenField('Categories', '', implode(',', $this->Categories()->getIdList())));
		$fields->push(new HiddenField('AssociationID', '', $this->AssociationID));
		$fields->push(new HiddenField('OrganizerID', '', $this->OrganizerID));
		$fields->push(new HiddenField('MunicipalID', '', $this->MunicipalID));
		
		foreach ($localeList as $locale) {
			$fields->push(new HiddenField('Title_' . $locale, '', $this->getField('Title_' . $locale)));
			$fields->push(new HiddenField('EventTextShort_' . $locale, '', $this->getField('EventTextShort_' . $locale)));
			$fields->push(new HiddenField('EventText_' . $locale, '', $this->getField('EventText_' . $locale)));
			$fields->push(new HiddenField('Place_' . $locale, '', $this->getField('Place_' . $locale)));
			$fields->push(new HiddenField('PriceText_' . $locale, '', $this->getField('PriceText_' . $locale)));
		}
		
		if ($this->ShowGoogleMap)
			$fields->push(new HiddenField('ShowGoogleMap', '', $this->ShowGoogleMap));
		$fields->push(new HiddenField('GoogleMAP', '', $this->GoogleMAP));
		$fields->push(new HiddenField('Homepage', '', $this->Homepage));
		$fields->push(new HiddenField('PriceType', '', $this->PriceType));
		
		$datesArray = array();
		foreach ($this->RepeatDates() as $date) {
			$datesArray[] = $date->Date . ' ' . $date->Start . '-' . $date->End . ' ' . $date->ID;
		}
		$fields->push(new HiddenField('EventDates', '', implode(',', $datesArray)));
		$fields->push(new HiddenField('EventImages[selected]', '', implode(',', $this->EventImages()->getIdList())));
		$fields->push(new HiddenField('EventFiles[selected]', '', implode(',', $this->EventFiles()->getIdList())));
		$fields->push(new HiddenField('EventLinks[selected]', '', implode(',', $this->EventLinks()->getIdList())));
		
		$fields->push(new DialogTabSet('TabSet', $this->getPreviewTab()));
		return $fields;
	}
	
	public function getCMSFields() {		
		$member = Member::currentUser();
		/* -- For languages many_many relation defaults */
		$id_list = array();		
		// If new event all selected
		if ($this->ID == 0 && !$this->isDOMDuplicateForm()) {
			/*$calendarLocales = DataObject::get('CalendarLocale', "IsDefault=1");
			if ($calendarLocales) {
				$id_list = $calendarLocales->map('ID');
			}*/
		} else {					
			if ($this->isDOMDuplicateForm() && $this->DuplicateFrom->Languages()) {
				$id_list = $this->DuplicateFrom->Languages()->getIdList();
			}
			else if ($this->Languages()) {
				$id_list = $this->Languages()->getIdList();
			}	
		}	 
 	
		$languagesDropdownValue = '[initial]' . implode(',', array_keys($id_list));
 	
	 	/* -- For EventCategories many_many relation defaults */
		$id_list = array();
		if ($this->isDOMDuplicateForm() && $this->DuplicateFrom->Categories()) {
			$id_list = $this->DuplicateFrom->Categories()->getIdList();
		}
		else if ($this->Categories()) {
			$id_list = $this->Categories()->getIdList();
		}	
	 	$eventCategoriesDropdownValue = '[initial]' . implode(',', array_keys($id_list));
	 	
	
	 	/* -- Associations that current user have the permission to choose from */
	 	
	 	if (eCalendarExtension::isAdmin()) {	 			
	 		$where_assoc = null;	
	 		$where_organizer = null;
	 		$mymunicipals = array();	
	 		$myusers = array('All' => array(), 'Moderator' => array(), 'Organizer' => array());	
	 	} else {
			$where_assoc = '(';
	 		$where_assoc .= "Association.ID = '".$this->Association()->ID."'"; // Returnerar förening tilldelad default		
	 		$where_organizer = "AssociationOrganizer.ID = '".$this->Organizer()->ID."'"; // Returnerar förening tilldelad default		
			
	 		$myassociations = $this->getMyAssociations(null, 'organizers', true);			
			$where_assoc.= " OR (
				Association.ID IN ('".implode("','", $myassociations)."')
			)";	
									
			$myusers = $this->getMyUsers(null, 'moderators', true);
			$where_organizer.= " OR (
				AssociationOrganizer.ID IN ('".implode("','", $myusers['All'])."')
			)";
			
			$where_assoc .= ')';	

			$mymunicipals = $this->getMyMunicipals();
			
			if (count($myassociations) == 1) {
				$tmpMyAssociations = $myassociations;
				$this->AssociationID = array_pop($tmpMyAssociations);
			}
	 	}
	 	
	 	$associations = array('' => _t('AdvancedDropdownField.NONESELECTED', '(None selected)'));
		$associations_info = array('email' => array(), 'phone' => array(), 'canPublish' => array(), 'users' => array());
	 	$association_objs = DataObject::get('Association', $where_assoc);
	 	if ($association_objs) {
	 		$associations = $association_objs->map('ID', 'NameHierachyAsTextWithStatus', _t('AdvancedDropdownField.NONESELECTED', '(None selected)'));
			
			foreach ($association_objs as $assocObj) {
				$associations_info['email'][$assocObj->ID] = $assocObj->Email;
				$associations_info['phone'][$assocObj->ID] = $assocObj->Phone;
				$associations_info['canPublish'][$assocObj->ID] = $this->canAssociationPublish($assocObj) ? '1' : '0';
				$associations_info['users'][$assocObj->ID] = $assocObj->getAssociationUsers($member, $assocObj, 'organizers', true);
			}
	 	}
	 
	 	/* ------------------------------------------------------------------- */
	 		 	
		if ($member instanceof AssociationOrganizer) {
	 		$canPublish = $member->canPublish();
	 	} else {
	 		$canPublish = false;
	 	}

		if (eCalendarExtension::isAdmin()) {
			$canPublish = true;
		}		 
	 	
	 	if ($this->ID == 0 || $this->isDOMDuplicateForm()) {
			if ($this->ID == 0 && !eCalendarExtension::isAdmin())
				$this->OrganizerID = $member->ID;		 	

			$this->Status = 'Draft';
	 	}
		
	 	$avb_statuses = array(
			'Draft' => _t('Event.STATUS_DRAFT', 'Draft'),
 			'Preliminary' => _t('Event.STATUS_PRELIMINARY', 'Preliminary'),
 			'Accepted' => _t('Event.STATUS_ACCEPTED', 'Accepted'),
 			'Rejected' => _t('Event.STATUS_REJECTED', 'Rejected'),
 			'Cancelled' => _t('Event.STATUS_CANCELLED', 'Cancelled'),
	 	);
	 	
	 	if (!$canPublish) {
			if ($this->Status != 'Accepted') // If our event has been accepted by moderator but we cant publish directly, we need this option
				unset($avb_statuses['Accepted']);
			unset($avb_statuses['Rejected']);
		}
		
		if ($this->ID == 0 || $this->isDOMDuplicateForm()) {
			unset($avb_statuses['Cancelled']);
			unset($avb_statuses['Rejected']);
		}
		
		$statusField = new OptionsetField( 
			'Status', 
			_t('Event.STATUS', 'Status'), 
		 	$avb_statuses, 
			'Preliminary'
		);
	 	
		$organizerlist = DataObject::get('AssociationOrganizer', $where_organizer);			
	 	if ( count($myusers['All']) > 0 || eCalendarExtension::isAdmin()) {
			$organizerField = new AdvancedDropdownField(
				'OrganizerID', 
				_t('AssociationOrganizer.SINGULARNAME', 'Organizer') . ' <em>*</em>' . '<span class="tipsy-hint" title="' . _t('Event.HINT_ORGANIZER', 'Choose the organizer for the event') . '"></span>',
				is_object($organizerlist)?$organizerlist->map('ID', 'Name', _t('AdvancedDropdownField.NONESELECTED', '(None selected)')):array('' => _t('AdvancedDropdownField.NONESELECTED', '(None selected)'))
			);
		} else {
			if ($this->ID == 0) {
				$organizername = $member->Name;
			} else {
				$organizername = $this->Organizer()->Name;
			}
			$organizerField = new LabelField('Organizer', _t('AssociationOrganizer.SINGULARNAME', 'Organizer').': <b>'.$organizername.'</b><br /><br />', null, true);
		}	
					
		$associationField = new AdvancedDropdownField(
			'AssociationID',
			_t('Association.SINGULARNAME', 'Association') . ' <em>*</em>' . '<span class="tipsy-hint" title="' . _t('Event.HINT_ASSOCIATION', 'Choose the association for the event') . '"></span>',
			$associations
		);
			
				
	 	$googleMap = new GoogleMapSelectableField('GoogleMAP', _t('Event.ADDRESS', 'Address') . '<span class="tipsy-hint" title="' . _t('Event.HINT_ADDRESS', 'Enter the address where the event occures. You can enter it directly or use the pin on the map to select the location. (e.g. Eventaddress 2, 65100 Vasa)') . '"></span>', (int)$this->Latitude, (int)$this->Longitude, '600px', '300px');
		
			$fields = new FieldSet(				
			$DTSet = new DialogTabSet('TabSet',		
				$generalTab = new Tab('GeneralTab', _t('Event.GENERALTAB', 'General'),										
					new AdvancedDropdownField(				
						'Languages', 
						_t('Event.EVENTLANGUAGES', 'Event languages') . ' <em>*</em>' . '<span class="tipsy-hint" title="' . _t('Event.HINT_LANGUAGES', 'Choose the languages for the event') . '"></span>',
						CalendarLocale::toDropdownList(),
						$languagesDropdownValue,
						false,
						false,
						'selectEventLanguage',
						'showEventLanguages'
					),
					new TextField('Title', _t('Event.TITLE', 'Title of the event') . ' <em>*</em>' . '<span class="tipsy-hint" title="' . _t('Event.HINT_TITLE', 'The title for the event') . '"></span>'),
					new AdvancedDropdownField(				
						'Categories', 
						_t('Event.EVENTCATEGORIES', 'Event categories') . ' <em>*</em>' . '<span class="tipsy-hint" title="' . _t('Event.HINT_CATEGORIES', 'Choose the categories for the event') . '"></span>',
						EventCategory::toDropdownList(),
						$eventCategoriesDropdownValue,
						false,
						false,
						'selectEventCategory',
						'showEventCategory'
					),								
					$associationField,								
					$organizerField,
					new LiteralField('', '<div style="height: 140px;">&nbsp;</div>') // For dropdown..
				),
				$infoTab = new Tab('InfoTab', _t('Event.INFOTAB', 'Information'),				
					$shortDesc = new TextField('EventTextShort', _t('Event.DESCRIPTIONSHORT', 'Short description')  . ' <em>*</em>' . '<span class="tipsy-hint" title="' . _t('Event.HINT_SHORTDESCRIPTION', 'Enter a short description for the event') . '"></span>'),
					new TextAreaField('EventText', _t('Event.DESCRIPTION', 'Description') . '<span class="tipsy-hint" title="' . _t('Event.HINT_DESCRIPTION', 'Enter a detailed description for the event') . '"></span>'),
					new TextField('Homepage', _t('Event.HOMEPAGE', 'Homepage') . '<span class="tipsy-hint" title="' . _t('Event.HINT_HOMEPAGE', 'Enter the homepage address for the event (e.g. http://www.myevent.org)') . '"></span>')
				),
			
				$locationTab = new Tab('LocationTab', _t('Event.LOCATIONTAB', 'Location'),				
					new AdvancedDropdownField(				
						'MunicipalID', 
						_t('Municipal.SINGULARNAME', 'Municipal') . ' <em>*</em>' . '<span class="tipsy-hint" title="' . _t('Event.HINT_MUNICIPALITY', 'Choose the municipality in which the event occures') . '"></span>',
						Municipal::toDropdownList()						
					),
					new TextField('Place', _t('Event.PLACE', 'Place') . '<span class="tipsy-hint" title="' . _t('Event.HINT_PLACE', 'Enter the place where the event occures (e.g. City Library') . '"></span>'),
					new CheckboxField('ShowGoogleMap', _t('Event.SHOWGOOGLEMAP', 'Show Google map on event page') . '<span class="tipsy-hint" title="' . _t('Event.HINT_SHOWGOOGLEMAP', 'If checked, Google map will be shown on event page') . '"></span>'),
					$googleMap	
				),
				$commissionTab = new Tab('CommissionTab', _t('Event.COMMISSIONTAB', 'Commission'),
						new OptionsetField('PriceType', _t('Event.COMMISSIONTAB', 'Commission') . ' <em>*</em>' . '<span class="tipsy-hint" title="' . _t('Event.HINT_EVENTCHARGE', 'Select if or if not there is a charge to this event.') . '"></span>', array("Free" => _t('Event.NOTSUBJECTTOCHARGE', 'Free event'), "NotFree" => _t('Event.SUBJECTTOCHARGE', 'Subject to a charge'))),
						new TextAreaField('PriceText', _t('Event.PRICETEXT', 'Price description') . ' <em>*</em>' . '<span class="tipsy-hint" title="' . _t('Event.HINT_PRICETEXT', 'Information about the price, different pricetypes or price, other info like if you need to be a member to visit the event or not.') . '"></span>')
				),			
				$datesTab = new Tab('DatesTab', _t('Event.DATESTAB', 'Dates')),
				$imageLinkFilesTab = new Tab('ImageLinkFilesTab', _t('Event.IMAGELINKFILESSTAB', 'Images, Links & Files'),			
					$imagesDOM = new DialogHasManyDataObjectManager (
						$this, 
						'EventImages', 
						'EventImage',
						array(		
							'ThumbnailImage' => _t('EventImage.THUMBNAIL', 'Thumbnail'),
							'DownloadLink' => _t('EventImage.LINK', 'Link'),
						),
						null,
						"EventImage.EventID = '{$this->ID}' AND (EventImage.EventID != 0 OR EventImage.TemporaryDataObjectOwnerID = " . Member::currentUserID() . ")"
					),
					new LabelField('EventImagesLimit', sprintf(_t('Event.IMAGELIMIT', 'You can have a maximum of %s images per event.'), self::$maxNumberOfImages), null, true),
					$linksDOM = new DialogHasManyDataObjectManager (
						$this, 
						'EventLinks', 
						'EventLink',
						array(
							'NiceLocale' => _t('EventLink.LOCALE', 'Language'),
							'NiceName' => _t('EventLink.NAME', 'Name'),
							'ClickableLink' => _t('EventLink.LINK', 'Link'),
							'VisibilityNice' => _t('EventLink.VISIBILITYNICE', 'Visible on all languages'),
						),
						null,
						"EventLink.EventID = '{$this->ID}' AND (EventLink.EventID != 0 OR EventLink.TemporaryDataObjectOwnerID = " . Member::currentUserID() . ")"
					),
					$filesDOM = new DialogHasManyDataObjectManager (
						$this, 
						'EventFiles', 
						'EventFile',
						array(							
							'NiceLocale' => _t('EventFile.LOCALE', 'Language'),
							'NiceTitle' => _t('EventFile.TITLE', 'Title'),						
							'DownloadLink' => _t('EventFile.LINK', 'Link'),
							'VisibilityNice' => _t('EventFile.VISIBILITYNICE', 'Visible on all languages'),
						),
						null,
						"EventFile.EventID = '{$this->ID}' AND (EventFile.EventID != 0 OR EventFile.TemporaryDataObjectOwnerID = " . Member::currentUserID() . ")"
					),
					new LabelField('EventFilesLimit', sprintf(_t('Event.FILELIMIT', 'You can have a maximum of %s files per event.'), self::$maxNumberOfFiles), null, true)
				)
			)
		);
						
		$generalTab->push($statusField);
		
		$linksDOM->setColumnWidths(array(
			'NiceLocale' => '20',
			'NiceName' => '25',
			'ClickableLink' => '35',
			'VisibilityNice' => '20'
		));
		
		$filesDOM->setColumnWidths(array(
			'NiceLocale' => '20',
			'NiceTitle' => '25',
			'DownloadLink' => '35',
			'VisibilityNice' => '20'
		));		
					
		$imagesDOM->setMarkingPermission(false);
		$imagesDOM->setShowAll(true);
		
		$linksDOM->setMarkingPermission(false);
		$linksDOM->setShowAll(true);
		
		$filesDOM->setMarkingPermission(false);
		$filesDOM->setShowAll(true);
		
		$imagesDOM->removePermission('only_related');
		$linksDOM->removePermission('only_related');
		$filesDOM->removePermission('only_related');
		
		$shortDesc->setMaxLength(120);
		
		if ($this->EventImages()->Count() >= self::$maxNumberOfImages)
			$imagesDOM->removePermission ('add');
		else {
			$tempImages = DataObject::get('EventImage', "EventImage.EventID = {$this->ID} AND (EventImage.EventID != 0 OR EventImage.TemporaryDataObjectOwnerID = " . Member::currentUserID() . ")");
			if ($tempImages && $tempImages->Count() >= self::$maxNumberOfImages)
				$imagesDOM->removePermission ('add');
		}
		
		if ($this->EventFiles()->Count() >= self::$maxNumberOfFiles)
			$filesDOM->removePermission ('add');		
		else {
			$tempFiles = DataObject::get('EventFile', "EventFile.EventID = {$this->ID} AND (EventFile.EventID != 0 OR EventFile.TemporaryDataObjectOwnerID = " . Member::currentUserID() . ")");
			if ($tempFiles && $tempFiles->Count() >= self::$maxNumberOfFiles)
				$filesDOM->removePermission ('add');
		}		
		
		/* Dates tab */
		$datesTab->push(new LiteralField('DateSelector', $this->renderWith('EventDatesTab', array('DefaultDate' => date('d.m.Y')))));
		$datesTab->push(new CheckboxField('Repeat', _t('Event.REPEAT', 'Recurring')));
		$datesTab->push(
			$repeatGroup = new FieldGroup(
				new AdvancedDropdownField(
					'RepeatType', 
					_t('Event.REPEATING', 'Repeat') . ':', 
					array(
						'' => _t('Event.REPEATINGDAILY', 'daily'), 
						'w' => _t('Event.REPEATINGWEEKLY', 'weekly'), 
						'm' => _t('Event.REPEATINGMONTHLY', 'monthly')
					),
					'',
					false,
					false,
					'RepeatTypeChanged'
				),
				new AdvancedDropdownField(
					'RepeatEach', 
					_t('Event.REPEATINGAFTER', 'Repeat after') . ':', 
					array(
						'' => 1, 
						2 => 2, 
						3 => 3, 
						4 => 4
					), 
					'', 
					false, 
					false, 
					'RepeatEachChanged'
				),
				new CheckboxSetField(
					'RepeatDays', 
					_t('Event.REPEATINGEVERY', 'Repeat every') . ':', 
					array(
						1 => _t('Event.DAY_MO', 'Mo'), 
						2 => _t('Event.DAY_TU', 'Tu'), 
						3 => _t('Event.DAY_WE', 'We'), 
						4 => _t('Event.DAY_TH', 'Th'), 
						5 => _t('Event.DAY_FR', 'Fr'), 
						6 => _t('Event.DAY_SA', 'Sa'), 
						7 => _t('Event.DAY_SU', 'Su'),
					)
				),
				new LabelField(
					'RepeatEachLabel', 
					 _t('Event.DAYS', 'days'), 
					'', 
					true
				),
				new OptionsetField(
					'RepeatStop',
					'<div style="height: 0px; margin-top: 5px;">&nbsp;</div>' . _t('Event.REPETITIONSTEND', 'Ends') . ':', 
					array(
						0 => _t('Event.REPETITIONSTEND_AFTER', 'After'), 
						1 => _t('Event.REPETITIONSTEND_DATE', 'Date')
					)
				),
				new NumericField('RepeatTimes', '', 10),
				new LabelField(
					'RepeatTimesLabel', 
					_t('Event.REPETITIONTIMES', 'times'), 
					'', 
					true
				),
				new LiteralField(
					'Summary', 
					'<label>'._t('Event.RECURRINGSUMMARY', 'Summary').':</label> <p id="RepeatSummary"></p><button type="button" name="AddMultipleDates">' . _t('Event.ADDMULTIPLEDATES', 'Add dates') . '</button>'
				)
			)
		);
		$datesTab->push($repeatEndDay = new DateFieldEx('RepeatEndDay', ''));
		$datesTab->push(new LiteralField('DatesList', '
			<div class="selected-dates-list"> 
				<h5>' . _t('Event.DATESANDTIMES', 'Dates and times') . '<span class="clear-dates">(' . _t('Event.REMOVEALLDATES', 'Remove all') . ')</span>'  . '</h5>
				<ul class="date-list">
				</ul>
			</div>'));
		$datesTab->push(new FieldGroup(new HiddenField('EventDates', '')));
		$repeatGroup->setID('RepeatGroup');	
		$repeatEndDay->setConfig('minDate', '__function{ jQuery("#CurrentSelectedDate").html() }');
		
		$DTSet->push(
			$publishingTab = new Tab('PublishingTab', _t('Event.PUBLISHINGTAB', 'Other medias'),
				new DialogTabSet('PublishingSubTabs', 
					new Tab('PublishingSubTabMain', _t('Event.PUBLISHINGSUBTAB', 'Medias'),
						new CheckboxField('Vasabladet_PublishTo', _t('Vasabladet.PUBLISHTO', 'Publish to Vasabladet')),
						new CheckboxField('Pohjalainen_PublishTo', _t('Pohjalainen.PUBLISHTO', 'Publish to Pohjalainen')),
						new CheckboxField('NetTicket_PublishTo', _t('NetTicket.PUBLISHTO', 'Publish to NetTicket')),
						new LiteralField('PublishingSubTabMain_InfoBox', '<div id="PublishingSubTabMain_InfoBox">' . _t('Event.OTHERMEDIASINFO', '') . '</div>')
					),
					new Tab('Vasabladet_Tab', _t('Vasabladet.TABTITLE', 'Vasabladet'), $vasaBladetGroup = $this->getVasabladetFields()),
					new Tab('Pohjalainen_Tab', _t('Pohjalainen.TABTITLE', 'Pohjalainen'),  $pohjalainenGroup = $this->getPohjalainenFields()),
					new Tab('NetTicket_Tab', _t('NetTicket.TABTITLE', 'NetTicket'), $netticketGroup = $this->getNetTicketFields())
				)
			)
		);
		
		$vasaBladetGroup->setID('VasabladetGroup');
		$pohjalainenGroup->setID('PohjalainenGroup');
		$netticketGroup->setID('NetTicketGroup');
		
		$DTSet->push($this->getPreviewTab());
					
		$fields->push( new HiddenField('Latitude') );
		$fields->push( new HiddenField('Longitude') );
		$fields->push($publishDirectly = new HiddenField('UserCanPublishDirectly', '', ($this->canPublish() ? '1' : '0')));
		$fields->push($publishDirectlyText = new HiddenField('UserCanPublishDirectlyText', '', _t('Event.INFO_CANNOTPUBLISHDIRECTLY', 'You cannot yet publish events directly. This event will be published automatically later on when your account has been confirmed.')));
		$fields->push($associationPublishDirectlyText = new HiddenField('AssociationPublishDirectlyText', '', _t('Event.INFO_ASSOCIATIONCANNOTPUBLISH', 'This event will not be published until the organizer for this event has been confirmed.')));
		$fields->push($confirmPublishText = new HiddenField('InfoConfirmPublish', '', _t('Event.INFO_CONFIRMPUBLISH', 'Are you sure you want to publish this event?<br/><br/>A published event will be visible to the general public.')));
		$fields->push($confirmUnpublishText = new HiddenField('InfoConfirmUnpublish', '', _t('Event.INFO_CONFIRMUNPUBLISH', 'Are you sure you want to unpublish this event?<br/><br/>An unpublished event will NOT be visible to the general public.')));
		
		$publishDirectly->performDisabledTransformation();
		$publishDirectlyText->performDisabledTransformation();
		$confirmPublishText->performDisabledTransformation();
		$confirmUnpublishText->performDisabledTransformation();
		$associationPublishDirectlyText->performDisabledTransformation();
		
		// Javascript info for assocations
		$jsExtra = '<script type="text/javascript">var AssociationsEmail = {}; var AssociationsPhone = {}; var AssociationsPublishable = {}; var AssociationsUsers = {};';
		foreach ($associations_info['email'] as $assocID => $assocEmail) {
			$jsExtra .= 'AssociationsEmail["' . $assocID . '"] = "' . $assocEmail . '"; ';
		}
		foreach ($associations_info['phone'] as $assocID => $assocPhone) {
			$jsExtra .= 'AssociationsPhone["' . $assocID . '"] = "' . $assocPhone . '"; ';
		}
		foreach ($associations_info['canPublish'] as $assocID => $assocPublishable) {
			$jsExtra .= 'AssociationsPublishable["' . $assocID . '"] = "' . $assocPublishable . '"; ';
		}
		foreach ($associations_info['users'] as $assocID => $assocUsers) {
			if (count($assocUsers))
				$jsExtra .= 'AssociationsUsers["' . $assocID . '"] = new Array("' . implode('","', $assocUsers['All']) . '"); ';
			else
				$jsExtra .= 'AssociationsUsers["' . $assocID . '"] = new Array(); ';
		}
		$jsExtra .= '</script>';
		$fields->push(new LiteralField('', $jsExtra));
		
		// 
		
		$this->extend('updateCMSFields', $fields);
		return $fields;
	}
	
	protected function getPreviewTab() {
		$previewTab = new Tab('PreviewTab', _t('Event.PREVIEWTAB', 'Preview'));
		$previewTab->push(new DropdownField('PreviewLanguage', 
				_t('Event.PREVIEWLANGUAGE', 'Preview language') . '<span class="tipsy-hint" title="' . _t('Event.HINT_PREVIEWLANGUAGE', 'Select the languages to use when previewing the event.') . '"></span>',
				CalendarLocale::toDropdownList(),
				CalendarLocale::getIDFromLocale(Member::currentUser()->Locale)
		));
		$previewTab->push(new LiteralField('PreviewFrame', '<div id="PreviewLoader"><img src="dataobject_manager/images/ajax-loader-white.gif" alt="Loading in progress..." /></div><iframe id="PreviewIFrame" frameBorder="0"></iframe>'));		
		return $previewTab;
	}
	
	public function getNiceStatus() {
		return _t('Event.STATUS_' . strtoupper($this->Status), $this->Status);
	}
	
	public function getOrganizerName() {
		$organizer = $this->Organizer();
		$association = $this->Association();
		
		if ($organizer && $association) {
			return $association->forTemplate() . '<br />' . $organizer->Name;
		} else if ($organizer && !$association)		
			return $organizer->Name;
		else if (!$organizer && $association)
			return $association->Name;
		
		return '';
	}
	
	public function getNiceCategories() {
		$categories = $this->Categories();
		if ($categories) {
			$categoryNames = $categories->column('Name');
			return implode(', ', $categoryNames);
		}
		return '';
	}
	
	public function getCreatorName() {
		$creator = $this->Creator();
		if ($creator)
			return $creator->Name;
		return '';		
	}
	
	protected function onBeforeDelete() {
		parent::onBeforeDelete();
			
		// delete EventImages associated with this Event
		if ($this->EventImages()) {
			foreach ($this->EventImages() as $eventImage) {
				if ($eventImage->exists())
					$eventImage->delete();
			}
		}

		// delete RepeatDates associated with this Event	
		if ($this->RepeatDates()) {	
			foreach ($this->RepeatDates() as $eventDate) {
				$eventDate->delete();
			}
		}
		
		// delete EventLinks associated with this Event
		if ($this->EventLinks()) {
			foreach ($this->EventLinks() as $eventLink) {
				if ($eventLink->exists())
					$eventLink->delete();
			}
		}		
		
		// delete EventFiles associated with this Event
		if ($this->EventFiles()) {
			foreach ($this->EventFiles() as $eventFile) {
				if ($eventFile->exists())
					$eventFile->delete();
			}
		}			
		
	}	
			 
	public function onBeforeWrite() {
		parent::onBeforeWrite();
		$member = Member::CurrentUser();
		$data = Convert::raw2sql($_POST);
		
		if ($this->ID == 0 && !$this->CreatorID) {
			$this->CreatorID = $member->ID;					
	 		if (empty($data['OrganizerID']) && !$this->OrganizerID) {
	 			$this->OrganizerID = $member->ID;
	 		}
		}
		else {
			if ($this->Status == '') {
				$this->Status = 'Draft';
			}
		}
		
		// Set event status to Preliminary if our association isn't active
		if ($this->Status == 'Accepted' && ($this->Association()->Status != 'Active' || !$member->canPublish())) {
			$this->Status = 'Preliminary';
			$this->PublishedOnce = false;
			$this->PublishedDate = null;
		}
		else {
			// Set published date
			if ($this->Organizer() && $this->Association() && $this->isChanged('Status', 2)) {			
				$changedFields = $this->getChangedFields(true, 2);

				if ($changedFields['Status']['before'] != 'Accepted' && $changedFields['Status']['after'] == 'Accepted') {	
					$this->PublishedDate = date('Y-m-d H:i:s');
					if (!$this->PublishedOnce)
						$this->PublishedOnce = true;
				}
				else if ($changedFields['Status']['after'] != 'Accepted')
					$this->PublishedDate = null;
			}
		
			if (!strlen($this->PublishedDate) && $this->Status == 'Accepted') {
				$this->PublishedDate = date('Y-m-d H:i:s');
				if (!$this->PublishedOnce)
					$this->PublishedOnce = true;			
			}
		}
		
		// Homepage fix
		if (!empty($this->Homepage)) {
			if (!preg_match("/^(http|https)/i", $this->Homepage)) {
				$this->Homepage = 'http://' . $this->Homepage;
			}
		}
		
		// Google maps
		if (!empty($this->GoogleMAP)) {
			$exploded = explode(',', $this->GoogleMAP);
			
			if (count($exploded) == 4) 
				array_shift($exploded);	// First one is Country
			
			if (count($exploded) == 3) {
				$this->PostalAddress = ucfirst($exploded[0]);
				if (!is_numeric($exploded[1])) {
					$tmp = explode(' ', trim($exploded[1]));
					if (count($tmp) == 2) {
						$this->PostalCode = $tmp[0];
						$this->PostalOffice = $tmp[1];
					}
				}
				else {
					$this->PostalCode = $exploded[1];
					$this->PostalOffice = ucfirst($exploded[2]);
				}
			}
			else if (count($exploded) == 2) {			
				if (!is_numeric($exploded[1])) {
					$tmp = explode(' ', trim($exploded[1]));
					if (count($tmp) == 2) {
						$this->PostalAddress = $exploded[0];
						$this->PostalCode = $tmp[0];
						$this->PostalOffice = $tmp[1];
					}
					else {
						$this->PostalAddress = $exploded[0];
						$this->PostalCode = 0;
						$this->PostalOffice = trim($exploded[1]);
					}
				}
				else {
					$this->PostalAddress = '';
					$this->PostalCode = (int)$exploded[1];
					$this->PostalOffice = ucfirst($exploded[0]);
				}
			}
			else {
				$tmp = explode(' ', trim($this->GoogleMAP));
				if (count($tmp) == 2) {
					$this->PostalAddress = '';
					$this->PostalCode = $tmp[0];
					$this->PostalOffice = $tmp[1];
				}
				else {
					$this->PostalAddress = ucfirst($this->GoogleMAP);
					$this->PostalCode = '';
					$this->PostalOffice = '';
				}
			}
		}
		
		// Add/modify dates
		if (!empty($data['EventDates']) && $this->ID) {
			$existingIDs = array();
			$modifiedIDs = array();
			
			$existingDates = $this->RepeatDates();
			if ($existingDates && $existingDates->Count())
				$existingIDs = array_keys($existingDates->getIDList());			
			
			$datesArray = explode(',', $data['EventDates']);
			if (is_array($datesArray)) {
				foreach ($datesArray as $date) {
					$explodedDate = explode(' ', $date);
					$explodedTimespan = explode('-', $explodedDate[1]);
					$dateID = $explodedDate[2];

					$date = $explodedDate[0];
					$start = $explodedTimespan[0];
					$end = $explodedTimespan[1];

					if (in_array($dateID, $existingIDs)) {
						$existingDate = DataObject::get_by_id('EventDate', $dateID);
						$existingDate->Date = $date;
						$existingDate->StartTime = $start;
						$existingDate->EndTime = $end;
						$existingDate->write();

						$modifiedIDs[] = $existingDate->ID;
					}
					else {
						$eventDate = new EventDate();
						$eventDate->Date = $date;
						$eventDate->StartTime = $start;
						$eventDate->EndTime = $end;
						$eventDate->write();

						$this->RepeatDates()->add($eventDate->ID);
					}
				}
			}
			
			// Get the difference of IDs, those are the ones to remove
			$diffIDs = array_diff($existingIDs, $modifiedIDs);
			
			if (count($diffIDs)) {
				foreach ($diffIDs as $diffID) {
					if ($this->RepeatDates()) {
						$this->RepeatDates()->remove($diffID);					
					
						$date = DataObject::get_by_id('EventDate', $diffID);
						if ($date)
							$date->delete();
					}
				}
			}
		}	
		
		$this->Start = $this->DatesStart;
		$this->End = $this->DatesEnd;
		
		if ($this->ID) {
			$safeData = $data;
			// Add languages
			if (isset($safeData['Languages'])) {
				$languagesIDs = explode(',', $safeData['Languages']);
				$existingLanguages = $this->Languages();
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

			// Add eventcategoires
			if (isset($safeData['Categories'])) {
				$categoryIDs = explode(',', $safeData['Categories']);
				$existingCategories = $this->Categories();
				$idList = array();

				if (is_array($categoryIDs) && count($categoryIDs) > 0) {
					foreach($categoryIDs as $categoryID) {
						if (is_numeric($categoryID) && DataObject::get_by_id('EventCategory', $categoryID)) {
							$idList[] = $categoryID;
						}
					}
				} 
				
				$existingCategories->setByIDList($idList);
			}
		}
	}
	
	public function Link() {
		return Controller::join_links(singleton('EventPage_Controller')->ControllerLink(), 'showEvent', $this->ID);
	}
	
	public function AbsoluteLink() {
		return Director::absoluteURL($this->Link());
	}	
	
	public function getPreviewLink() {
		if ($this->Status == 'Accepted' && $this->Association()->Status == 'Active') {
			return '<a class="noClickPropagation" target="_blank" href="' . $this->AbsoluteLink(). '">' . _t('Event.PREVIEW', 'Preview') . '</a>';
		} else {
			return '&nbsp;';
		}
	}	
	
	public function onAfterWrite() {
		parent::onAfterWrite();

		$changedFields = $this->getChangedFields(true, 2);		
		
		// Send notification email		
		if ($this->Organizer() && $this->Association() && $this->isChanged('Status', 2)) {
			$organizer = $this->Organizer();
			
			if ($changedFields['Status']['before'] != 'Accepted' && $changedFields['Status']['after'] == 'Accepted') {

				$originalLocale = i18n::get_locale();
				$currentLocale = $organizer->Locale; 
				i18n::set_locale($currentLocale);

				$subject = sprintf(_t('Event.PUBLISHEDNOTICE_SUBJECT', 'Event "%s"'), $this->Title);
				$body = _t('Event.PUBLISHEDNOTICE_BODY1', 'The following event has been published.') . "\n\n";
				$body .= '[b]' . $this->Title . "[/b]\n\n";
				$body .= '[b]' . $this->EventTextShort . "[/b]\n\n";
				$body .= '[b]' . $this->EventText . "[/b]\n\n";
				$body .= sprintf(_t('Event.PUBLISHEDNOTICE_BODY2', 'Click [url=%s]here[/url] to view it.'), $this->Link());

				$msg = new IM_Message();
				$msg->ToID = $organizer->ID;
				$msg->FromID = 0;
				$msg->Subject = $subject;
				$msg->Body = $body;
				$msg->send(false);

				/*
				// Send message to moderators that an event has been published
				$association = $this->Association();
				if ($association) {
					$moderators = eCalendarExtension::FindClosestMembers($association, 'parent', 'Moderator', array($organizer->ID));
					if ($moderators) {
						foreach ($moderators as $moderator) {
							if ($organizer->ID != $moderator->ID) {
								$currentLocale = $moderator->Locale; 
								i18n::set_locale($currentLocale);								

								$subject = sprintf(_t('Event.PUBLISHEDNOTICE_SUBJECT', 'Event "%s"'), $this->Title);
								$body = '[b]' . _t('AssociationOrganizer.SINGULARNAME', 'User') . ':[/b] ' . $organizer->FullName . "\n";
								$body .= '[b]' . _t('Association.SINGULARNAME', 'Organization') . ':[/b] ' . $association->getNameHierachy(false) . "\n\n";
								$body .= _t('Event.PUBLISHEDNOTICE_BODY1', 'The following event has been published.') . "\n\n";
								$body .= '[b]' . $this->Title . "[/b]\n\n";
								$body .= '[b]' . $this->EventTextShort . "[/b]\n\n";
								$body .= '[b]' . $this->EventText . "[/b]\n\n";
								$body .= sprintf(_t('Event.PUBLISHEDNOTICE_BODY2', 'Click [url=%s]here[/url] to view it.'), $this->Link());

								$msg->Subject = $subject;
								$msg->Body = $body;								
								$msg->ToID = $moderator->ID;
								$msg->send(false);
							}
						}
					}
				}
				*/
				
				i18n::set_locale($originalLocale);
				
				$this->onPublish();
			}
			else if ($changedFields['Status']['before'] != 'Preliminary' && $changedFields['Status']['after'] == 'Preliminary') {			
				// Event needs to be published by a moderator
				$originalLocale = i18n::get_locale();
				$currentLocale = $organizer->Locale; 
				i18n::set_locale($currentLocale);

				$subject = sprintf(_t('Event.PRELIMINARYNOTICE_SUBJECT', 'Event "%s"'), $this->Title);
				$body = _t('Event.PRELIMINARYNOTICE_BODY1', 'The following event will be published as soon as a moderator has reviewed it or when you are given publishing right.') . "\n\n";
				$body .= '[b]' . $this->Title . "[/b]\n\n";
				$body .= '[b]' . $this->EventTextShort . "[/b]\n\n";
				$body .= '[b]' . $this->EventText . "[/b]\n\n";

				$msg = new IM_Message();
				$msg->ToID = $organizer->ID;
				$msg->FromID = 0;
				$msg->Subject = $subject;
				$msg->Body = $body;
				$msg->send(false);
				
				// Send message to moderators that an event needs to be verified
				$association = $this->Association();
				if ($association) {
					$sent = false;
					$moderators = eCalendarExtension::FindClosestMembers($association, 'parent', 'Moderator', array($organizer->ID));
					if ($moderators) {
						foreach ($moderators as $moderator) {
							if ($organizer->ID != $moderator->ID) {
								$currentLocale = $moderator->Locale; 
								i18n::set_locale($currentLocale);								

								$subject = sprintf(_t('Event.VERIFICATIONNOTICE_SUBJECT', 'Event "%s"'), $this->Title);
								$body = '[b]' . _t('AssociationOrganizer.SINGULARNAME', 'User') . ':[/b] ' . $organizer->FullName . "\n";
								$body .= '[b]' . _t('Association.SINGULARNAME', 'Organization') . ':[/b] ' . $association->getNameHierachy(false) . "\n\n";
								$body .= _t('Event.VERIFICATIONNOTICE_BODY1', 'The following event needs to be verified and published.') . "\n\n";
								$body .= '[b]' . $this->Title . "[/b]\n\n";
								$body .= '[b]' . $this->EventTextShort . "[/b]\n\n";
								$body .= '[b]' . $this->EventText . "[/b]\n\n";
								//$body .= sprintf(_t('Event.VERIFICATIONNOTICE_BODY2', 'Click [url=%s]here[/url] to view it.'), $this->Link());
								$body .= _t('Event.VERIFICATIONNOTICE_BODY2', 'You can publish this event after you have logged into the system.');

								$msg->Subject = $subject;
								$msg->Body = $body;								
								$msg->ToID = $moderator->ID;
								$msg->send(false);
								
								$sent = true;
							}
						}
					}
					
					// If no moderators, sending to municipal moderator
					if ($sent == false) { 
						$moderators = $association->Municipal()->AssociationOrganizers();
						if ($moderators->Count()) {
							foreach ($moderators as $moderator) {
								if ($organizer->ID != $moderator->ID) {
									$currentLocale = $moderator->Locale; 
									i18n::set_locale($currentLocale);								

									$subject = sprintf(_t('Event.VERIFICATIONNOTICE_SUBJECT', 'Event "%s"'), $this->Title);
									$body = '[b]' . _t('AssociationOrganizer.SINGULARNAME', 'User') . ':[/b] ' . $organizer->FullName . "\n";
									$body .= '[b]' . _t('Association.SINGULARNAME', 'Organization') . ':[/b] ' . $association->getNameHierachy(false) . "\n\n";
									$body .= _t('Event.VERIFICATIONNOTICE_BODY1', 'The following event needs to be verified and published.') . "\n\n";
									$body .= '[b]' . $this->Title . "[/b]\n\n";
									$body .= '[b]' . $this->EventTextShort . "[/b]\n\n";
									$body .= '[b]' . $this->EventText . "[/b]\n\n";
									//$body .= sprintf(_t('Event.VERIFICATIONNOTICE_BODY2', 'Click [url=%s]here[/url] to view it.'), $this->Link());
									$body .= _t('Event.VERIFICATIONNOTICE_BODY2', 'You can publish this event after you have logged into the system.');

									$msg->Subject = $subject;
									$msg->Body = $body;								
									$msg->ToID = $moderator->ID;
									$msg->send(false);

									$sent = true;
								}
							}
						}									
					}
					
					if ($sent == false) {
						// Send to administrators
						$admins = eCalendarExtension::FindAdministrators();
						if ($admins) {
							foreach ($admins as $admin) {
								if ($organizer->ID != $admin->ID) {
									$currentLocale = $admin->Locale; 
									i18n::set_locale($currentLocale);								

									$subject = sprintf(_t('Event.VERIFICATIONNOTICE_SUBJECT', 'Event "%s"'), $this->Title);
									$body = '[b]' . _t('AssociationOrganizer.SINGULARNAME', 'User') . ':[/b] ' . $organizer->FullName . "\n";
									$body .= '[b]' . _t('Association.SINGULARNAME', 'Organization') . ':[/b] ' . $association->getNameHierachy(false) . "\n\n";
									$body .= _t('Event.VERIFICATIONNOTICE_BODY1', 'The following event needs to be verified and published.') . "\n\n";
									$body .= '[b]' . $this->Title . "[/b]\n\n";
									$body .= '[b]' . $this->EventTextShort . "[/b]\n\n";
									$body .= '[b]' . $this->EventText . "[/b]\n\n";
									//$body .= sprintf(_t('Event.VERIFICATIONNOTICE_BODY2', 'Click [url=%s]here[/url] to view it.'), $this->Link());
									$body .= _t('Event.VERIFICATIONNOTICE_BODY2', 'You can publish this event after you have logged into the system.');

									$msg->Subject = $subject;
									$msg->Body = $body;								
									$msg->ToID = $admin->ID;
									$msg->send(false);

									$sent = true;
								}
							}
						}
					}
				}				
				
				i18n::set_locale($originalLocale);
			}
		}
		else if ($this->isChanged('NetTicket_PublishTo', 2)) {
			// Check if we want to send emails to NetTicket after we have published this event
			if ($changedFields['NetTicket_PublishTo']['before'] != '1' 
				&& $changedFields['NetTicket_PublishTo']['after'] == '1' 
				&& $this->Status == 'Accepted'
				&& $this->NetTicket_AcceptTerms) {
					$this->sendNetTicketEmail();
			}
		}
	}	
	
	protected function getNetTicketFields() {
		$fields = new FieldGroup();
		
		$fields->push(new LiteralField('', '<div id="NetTicket_InfoText">' . _t('NetTicket.PUBLISHINFO', '<strong>OBSERVERA!</strong> Efter att evenemenaget har publicerats kommer du inom kort att kontaktas av NetTickets personal som ger dig vidare instruktioner om hur du kan göra för att sälja biljetter till ditt evenemang via NetTicket.fi.') . '</div>'));
		$fields->push(new CheckboxField('NetTicket_AcceptTerms', _t('NetTicket.ACCEPTTERMS', 'I accept that NetTicket will contact me')));
		return $fields;
	}
	
	protected function getRequirementsForVasabladet() {
		
		$subCategories = array();
		// Dans
		$subCategories[5] = array();
		$subCategories[5][18] = 'Balett, modern dans';
		$subCategories[5][20] = 'Dansuppvisning';
		$subCategories[5][19] = 'Folkdans';
		$subCategories[5][21] = 'Övrigt';
		
		// Evenemang
		$subCategories[4] = array();
		$subCategories[4][13] = 'Fester, högtider';
		$subCategories[4][47] = 'För barn';
		$subCategories[4][16] = 'Kultur och konst';
		$subCategories[4][14] = 'Kyrka, religion';
		$subCategories[4][15] = 'Lopptorg, basarer';
		$subCategories[4][46] = 'Marknader';
		$subCategories[4][17] = 'Övrigt';
		
		// Film
		$subCategories[1] = array();
		$subCategories[1][1] = 'Bio';
		$subCategories[1][2] = 'Festivaler';
		$subCategories[1][3] = 'Övrigt';
		
		// Friluftsliv
		$subCategories[9] = array();
		$subCategories[9][43] = 'Läger';
		$subCategories[9][42] = 'Utflykter';
		$subCategories[9][44] = 'Övrigt';		
		
		// Idrott
		$subCategories[6] = array();
		$subCategories[6][28] = 'Boboll';		
		$subCategories[6][23] = 'Fotboll';
		$subCategories[6][24] = 'Friidrott';
		$subCategories[6][29] = 'Hästsport';
		$subCategories[6][27] = 'Innebandy';
		$subCategories[6][22] = 'Ishockey';
		$subCategories[6][25] = 'Orientering';
		$subCategories[6][31] = 'Simning';
		$subCategories[6][30] = 'Skytte';
		$subCategories[6][26] = 'Volleyboll';
		$subCategories[6][32] = 'Övrigt';
		
		// Musik
		$subCategories[2] = array();
		$subCategories[2][7] = 'Festivaler';
		$subCategories[2][4] = 'Jazz';
		$subCategories[2][6] = 'Konserter';
		$subCategories[2][5] = 'Körmusik';
		$subCategories[2][8] = 'Opera';
		$subCategories[2][9] = 'Övrigt';
		
		// Mässor
		$subCategories[7] = array();
		$subCategories[7][34] = 'Auktioner';
		$subCategories[7][36] = 'Lantbruksutställningar';
		$subCategories[7][33] = 'Stormässor';
		$subCategories[7][35] = 'Yrkesmässor';
		$subCategories[7][37] = 'Övrigt';
		
		// Teater
		$subCategories[3] = array();
		$subCategories[3][10] = 'Drama';
		$subCategories[3][12] = 'Musikteater';
		$subCategories[3][11] = 'sommarteater';
		
		// Utställningar
		$subCategories[8] = array();
		$subCategories[8][38] = 'Djur';
		$subCategories[8][45] = 'Foto';
		$subCategories[8][39] = 'Konst';
		$subCategories[8][40] = 'Museer';
		$subCategories[8][41] = 'Övrigt';
		$subCategories[8]['infoText'] = '<strong>OBSERVERA!</strong> Foto- och konstutställningar publiceras inte under \'Händer idag\' i tidningen. De publiceras i torsdagens tidning under \'Utställningar\'. På webben publiceras de dock som alla andra evenemang.';
		
		$customJS = "var vasabladetSubCategories = {};";
		foreach ($subCategories as $subCategoryKey => $subCategoryValue) {
			$customJS .= 'vasabladetSubCategories[' . $subCategoryKey . '] = {};';
			foreach ($subCategoryValue as $key => $value) {
				$customJS .= 'vasabladetSubCategories[\'' . $subCategoryKey . '\'][\'' . $key . '\'] = "' . $value . '";';
			}
		}
					
		Requirements::customScript($customJS, 'Vasabladet_Subcategories');
	}
	
	protected function getVasabladetFields() {
		$fields = new FieldGroup();
		
		$vasabladetMunicipalities = array();
		$vasabladetMunicipalities[0] = _t('AdvancedDropdownField.NONESELECTED', '(None selected)');
		$vasabladetMunicipalities[1] = _t('Vasabladet.MUNICIAPLITY_JAKOBSTAD', 'Jakobstad');
		$vasabladetMunicipalities[2] = _t('Vasabladet.MUNICIAPLITY_KARLEBY', 'Karleby');
		$vasabladetMunicipalities[4] = _t('Vasabladet.MUNICIAPLITY_KASKO', 'Kaskö');
		$vasabladetMunicipalities[3] = _t('Vasabladet.MUNICIAPLITY_KORSHOLM', 'Korsholm');
		$vasabladetMunicipalities[5] = _t('Vasabladet.MUNICIAPLITY_KORSNAS', 'Korsnäs');
		$vasabladetMunicipalities[6] = _t('Vasabladet.MUNICIAPLITY_KRISTINESTAD', 'Kristinestad');
		$vasabladetMunicipalities[7] = _t('Vasabladet.MUNICIAPLITY_KRONOBY', 'Kronoby');
		$vasabladetMunicipalities[8] = _t('Vasabladet.MUNICIAPLITY_LARSMO', 'Larsmo');
		$vasabladetMunicipalities[9] = _t('Vasabladet.MUNICIAPLITY_MALAX', 'Malax');
		$vasabladetMunicipalities[10] = _t('Vasabladet.MUNICIAPLITY_NYKARLEBY', 'Nykarleby');
		$vasabladetMunicipalities[11] = _t('Vasabladet.MUNICIAPLITY_NARPES', 'Närpes');
		$vasabladetMunicipalities[13] = _t('Vasabladet.MUNICIAPLITY_PEDERSÖRE', 'Pedersöre');
		$vasabladetMunicipalities[14] = _t('Vasabladet.MUNICIAPLITY_VASA', 'Vasa');
		$vasabladetMunicipalities[15] = _t('Vasabladet.MUNICIAPLITY_VORA', 'Vörå');
		
		$vasabladetCategories = array();
		$vasabladetCategories[0] = _t('AdvancedDropdownField.NONESELECTED', '(None selected)');
		$vasabladetCategories[5] = _t('Vasabladet.CATEGORY_DANS', 'Dans');
		$vasabladetCategories[4] = _t('Vasabladet.CATEGORY_EVENEMANG', 'Evenemang');
		$vasabladetCategories[1] = _t('Vasabladet.CATEGORY_FILM', 'Film');
		$vasabladetCategories[9] = _t('Vasabladet.CATEGORY_FRILUFTSLIV', 'Friluftsliv');
		$vasabladetCategories[6] = _t('Vasabladet.CATEGORY_IDROTT', 'Idrott');
		$vasabladetCategories[2] = _t('Vasabladet.CATEGORY_MUSIK', 'Musik');
		$vasabladetCategories[7] = _t('Vasabladet.CATEGORY_MASSOR', 'Mässor');
		$vasabladetCategories[3] = _t('Vasabladet.CATEGORY_TEATER', 'Teater');
		$vasabladetCategories[8] = _t('Vasabladet.CATEGORY_UTSTALLNINGAR', 'Utställningar');
		
		$vasabladetSubCategories = array('0' => _t('AdvancedDropdownField.NONESELECTED', '(None selected)'));
		
		$fields->push(new LiteralField('', '<div id="Vasabladet_ChangeInfo">' . _t('Vasabladet.PUBLISHINFO', '<strong>OBSERVERA!</strong> Du kan göra ändringar i ditt evenemang vardagar före kl. 14 (om publiceringsdagen är i morgon). Om du gör ändringar vardagar efter kl. 14, kommer de inte med till följande dags tidning. Ändringar i ett evenemang som görs på fredag efter kl. 14 kommer inte med till helgens eller måndagens tidning.') . '</div>'));
		$fields->push(new DropdownField('Vasabladet_Municipality', _t('Vasabladet.MUNICIPALITY', 'Municipality') . ' <em>*</em>', $vasabladetMunicipalities));
		$fields->push(new FieldGroup(
				new DropdownField('Vasabladet_Category', _t('Vasabladet.CATEGORY', 'Category') . ' <em>*</em>', $vasabladetCategories),
				new DropdownField('Vasabladet_SubCategory', _t('Vasabladet.SUBCATEGORY', 'Subcategory'), $vasabladetSubCategories),
				new LiteralField('', '<div id="Vasabladet_InfoText"></div>')
		));
		$fields->push(new FieldGroup(new TextField('Vasabladet_ShortText', _t('Vasabladet.SHORTTEXT', 'Short description') . ' <em>*</em>')));
		$fields->push(new CheckboxField('Vasabladet_AdditionalInfo', _t('Vasabladet.ADDITIONALINFO', 'Additional info (visible only online)')));
		$fields->push(new FieldGroup(new TextareaField('Vasabladet_Text', _t('Vasabladet.TEXT', 'Description'))));
		$fields->push(new FieldGroup(new TextField('Vasabladet_Organizer', _t('Vasabladet.ORGANIZER', 'Organizer'))));
		$fields->push(new FieldGroup(new TextField('Vasabladet_URL', _t('Vasabladet.URL', 'URL'))));
		$fields->push(new FieldGroup(new TextField('Vasabladet_Address', _t('Vasabladet.ADDRESS', 'Address'))));
			
		return $fields;
	}
	
	protected function getPohjalainenFields() {
		$fields = new FieldGroup();
		
		$pohjalainenMunicipalities = array('' => _t('AdvancedDropdownField.NONESELECTED', '(None selected)')) + self::getPohjalainenMunicipalities();
		
		$pohjalainenCategories = array(
			'1' => _t('Pohjalainen.CATEGORY_TAPAHTUMAT', 'Tapahtumat'),
			'10' => _t('Pohjalainen.CATEGORY_NAYTELMAT', 'Näytelmät'),
			'2' => _t('Pohjalainen.CATEGORY_NAYTTELYT', 'Näyttelyt'),
			'3' => _t('Pohjalainen.CATEGORY_URHEILU', 'Urheilu'),
			'4' => _t('Pohjalainen.CATEGORY_YHDISTYKSET', 'Yhdistykset')
		);
		
		$fields->push(new DropdownField('Pohjalainen_Category', _t('Pohjalainen.CATEGORY', 'Category'), $pohjalainenCategories));		
		$fields->push(new DropdownField('Pohjalainen_MunicipalityZIP', _t('Pohjalainen.MUNICIPALITY', 'Municipality'), $pohjalainenMunicipalities));		
		$fields->push(new CheckboxField('Pohjalainen_PostInIlkka', _t('Pohjalainen.POSTINILKKA', 'Post also in Ilkka')));
		$fields->push(new TextField('Pohjalainen_Title', _t('Pohjalainen.TITLE', 'Title') . ' <em>*</em>'));
		$fields->push(new TextField('Pohjalainen_ShortText', _t('Pohjalainen.SHORTTEXT', 'Short description') . ' <em>*</em>'));
		$fields->push($descriptionGroup = new FieldGroup(
				new CheckboxField('Pohjalainen_HasText', _t('Pohjalainen.HASTEXT', 'Long description (visible on web only)')),
				$longDescription = new FieldGroup(new TextareaField('Pohjalainen_Text', _t('Pohjalainen.TEXT', 'Description')))
		));
		$fields->push(new TextField('Pohjalainen_URL', _t('Pohjalainen.URL', 'URL')));
		$fields->push(new TextField('Pohjalainen_Place', _t('Pohjalainen.PLACE', 'Place')));
		$fields->push(new TextField('Pohjalainen_Address', _t('Pohjalainen.ADDRESS', 'Address')));
		
		$descriptionGroup->setID('PohjalainenDescriptionGroup');
		$longDescription->setID('PohjalainenLongDescriptionGroup');
				
		return $fields;
	}	
	
	public static function getPohjalainenMunicipalities() {
		return array(
			'62900' => _t('Pohjalainen.MUNICIPALITY_ALAJÄRVI', 'Alajärvi'),
			'63300' => _t('Pohjalainen.MUNICIPALITY_ALAVUS', 'Alavus'),
			'60800' => _t('Pohjalainen.MUNICIPALITY_ILMAJOKI', 'Ilmajoki'),
			'64900' => _t('Pohjalainen.MUNICIPALITY_ISOJOKI', 'Isojoki'),
			'61500' => _t('Pohjalainen.MUNICIPALITY_ISOKYRÖ', 'Isokyrö'),
			'61600' => _t('Pohjalainen.MUNICIPALITY_JALASJÄRVI', 'Jalasjärvi'),
			'64350' => _t('Pohjalainen.MUNICIPALITY_KARIJOKI', 'Karijoki'),
			'64260' => _t('Pohjalainen.MUNICIPALITY_KASKINEN', 'Kaskinen'),
			'61800' => _t('Pohjalainen.MUNICIPALITY_KAUHAJOKI', 'Kauhajoki'),
			'62200' => _t('Pohjalainen.MUNICIPALITY_KAUHAVA', 'Kauhava'),
			'67100' => _t('Pohjalainen.MUNICIPALITY_KOKKOLA', 'Kokkola'),
			'66200' => _t('Pohjalainen.MUNICIPALITY_KORSNÄS', 'Korsnäs'),
			'64100' => _t('Pohjalainen.MUNICIPALITY_KRISTIINANKAUPUNKI', 'Kristiinankaupunki'),
			'68500' => _t('Pohjalainen.MUNICIPALITY_KRUUNUPYY', 'Kruunupyy'),
			'61300' => _t('Pohjalainen.MUNICIPALITY_KURIKKA', 'Kurikka'),
			'66400' => _t('Pohjalainen.MUNICIPALITY_LAIHIA', 'Laihia'),
			'62100' => _t('Pohjalainen.MUNICIPALITY_LAPUA', 'Lapua'),
			'66100' => _t('Pohjalainen.MUNICIPALITY_MAALAHTI', 'Maalahti'),
			'65610' => _t('Pohjalainen.MUNICIPALITY_MUSTASAARI', 'Mustasaari'),
			'64200' => _t('Pohjalainen.MUNICIPALITY_NÄRPIÖ', 'Närpiö'),
			'68820' => _t('Pohjalainen.MUNICIPALITY_PEDERSÖREN KUNTA', 'Pedersören kunta'),
			'68600' => _t('Pohjalainen.MUNICIPALITY_PIETARSAARI', 'Pietarsaari'),
			'60100' => _t('Pohjalainen.MUNICIPALITY_SEINÄJOKI', 'Seinäjoki'),
			'66900' => _t('Pohjalainen.MUNICIPALITY_UUSIKAARLEPYY', 'Uusikaarlepyy'),
			'65100' => _t('Pohjalainen.MUNICIPALITY_VAASA', 'Vaasa'),
			'66500' => _t('Pohjalainen.MUNICIPALITY_VÄHÄKYRÖ', 'Vähäkyrö'),
			'66600' => _t('Pohjalainen.MUNICIPALITY_VÖYRI', 'Vöyri')
		);		
	}
	
	public function getValidator() {
		return new Event_Validator($this);
	}
	
	protected function sendNetTicketEmail() {
		$origLocale = i18n::get_locale();
			
		i18n::set_locale(self::$NetTicket_EmailLocale);
				
		$email = new Email();
		$email->setTemplate('NetTicket_EmailNotification');
		$email->populateTemplate(array(
			'Organizer' => $this->Organizer(), 
			'Association' => $this->Association(),
			'Event' => $this
		));
		$email->setTo(self::$NetTicket_EmailAddress);
		$email->setSubject('=?UTF-8?B?' . base64_encode(_t('NetTicket_EmailNotification.SUBJECT', 'Tickets for an event') . ' "' . $this->Title . '"') . '?=');
		if (IM_Controller::$default_email_address != '')
			$email->setFrom(IM_Controller::$default_email_address);

		try {
			@$email->send();
		}
		catch (Exception $e) {
			// silently catch email sending errors...
		}
		
		i18n::set_locale($origLocale);		
	}
	
	public function onPublish() {
		if ($this->NetTicket_PublishTo && $this->NetTicket_AcceptTerms) 
			$this->sendNetTicketEmail();
	}
	
	public function onLogCreate($logItem) {
		if ($this->Status == 'Accepted') {
			$logItem->Comment = 'Event.WASPUBLISHED';
			$logItem->write();			
		}
		
		$logItem->AddChangedField('Status', $this->Status, '', 'Event.STATUS');
		$logItem->AddChangedField('PriceType', $this->PriceType, '', 'Event.COMMISSIONTAB');
		$logItem->AddChangedField('AssociationID', $this->AssociationID, '');
		$logItem->AddChangedField('Association', $this->Association()->Name, '', 'Association.SINGULARNAME');
		$logItem->AddChangedField('OrganizerID', $this->OrganizerID, '');
		$logItem->AddChangedField('Organizer', $this->Organizer()->FullName, '', 'AssociationOrganizer.SINGULARNAME');
		$logItem->AddChangedField('MunicipalID', $this->MunicipalID, '');
		$logItem->AddChangedField('Municipal', $this->Municipal()->Name, '', 'Municipal.SINGULARNAME');		
		
		foreach ($this->Languages() as $language) {
			foreach (self::$translatableFields as $transField) {
				$fieldName = $transField . '_' . $language->Locale;
				$fieldLabel = 'Event.' . strtoupper($transField);
				if (isset($changedFields[$fieldName])) {
					$logItem->AddChangedField($fieldName, $this->getField($fieldName), '', $fieldLabel);
				}
			}
		}
		
		$logItem->AddChangedField('GoogleMAP', $this->GoogleMAP, '', 'Event.ADDRESS');
		$logItem->AddChangedField('Homepage', $this->Homepage, '', 'Event.HOMEPAGE');
		$logItem->AddChangedField('ShowGoogleMap', $this->ShowGoogleMap, '', 'Event.SHOWGOOGLEMAP');
		$logItem->AddChangedField('Vasabladet_PublishTo', $this->Vasabladet_PublishTo, '', 'Vasabladet.PUBLISH');
		$logItem->AddChangedField('Pohjalainen_PublishTo', $this->Pohjalainen_PublishTo, '', 'Pohjalainen.PUBLISH');
		$logItem->AddChangedField('NetTicket_PublishTo', $this->NetTicket_PublishTo, '', 'NetTicket.PUBLISH');
	}
	
	public function onLogEdit($logItem) {
		$hasRealChanges = false;
		
		$changedFields = $this->getChangedFields(false, 2);
		if (isset($changedFields['Status'])) {
			if ($changedFields['Status']['before'] != 'Accepted' && $changedFields['Status']['after'] == 'Accepted') {
				$logItem->Comment = 'Event.WASPUBLISHED';
				$logItem->write();			
			}
			else if ($changedFields['Status']['before'] == 'Accepted' && $changedFields['Status']['after'] != 'Accepted') {
				$logItem->Comment = 'Event.WASUNPUBLISHED';
				$logItem->write();			
			}
			
			$logItem->AddChangedField('Status', $changedFields['Status']['before'], $changedFields['Status']['after'], 'Event.STATUS');
			$hasRealChanges = true;
		}
		
		if (isset($changedFields['PriceType'])) {
			$logItem->AddChangedField('PriceType', $changedFields['PriceType']['before'], $changedFields['PriceType']['after'], 'Event.COMMISSIONTAB');
			$hasRealChanges = true;
		}
				
		if (isset($changedFields['AssociationID'])) {
			$logItem->AddChangedField('AssociationID', $changedFields['AssociationID']['before'], $changedFields['AssociationID']['after']);
			
			$beforeObject = DataObject::get_by_id('Association', (int)$changedFields['AssociationID']['before']);
			$afterObject = DataObject::get_by_id('Association', (int)$changedFields['AssociationID']['after']);
			$logItem->AddChangedField('Association', ($beforeObject ? $beforeObject->Name : ''), ($afterObject ? $afterObject->Name : ''), 'Association.SINGULARNAME');
			$hasRealChanges = true;
		}		
		
		if (isset($changedFields['OrganizerID'])) {
			$logItem->AddChangedField('OrganizerID', $changedFields['OrganizerID']['before'], $changedFields['OrganizerID']['after']);
			
			$beforeObject = DataObject::get_by_id('AssociationOrganizer', (int)$changedFields['OrganizerID']['before']);
			$afterObject = DataObject::get_by_id('AssociationOrganizer', (int)$changedFields['OrganizerID']['after']);
			$logItem->AddChangedField('Organizer', ($beforeObject ? $beforeObject->FullName : ''), ($afterObject ? $afterObject->FullName : ''), 'AssociationOrganizer.SINGULARNAME');
			$hasRealChanges = true;
		}		
		
		if (isset($changedFields['MunicipalID'])) {
			$logItem->AddChangedField('MunicipalID', $changedFields['MunicipalID']['before'], $changedFields['MunicipalID']['after']);
			
			$beforeObject = DataObject::get_by_id('Municipal', (int)$changedFields['MunicipalID']['before']);
			$afterObject = DataObject::get_by_id('Municipal', (int)$changedFields['MunicipalID']['after']);
			$logItem->AddChangedField('Municipal', ($beforeObject ? $beforeObject->FullName : ''), ($afterObject ? $afterObject->FullName : ''), 'Municipal.SINGULARNAME');
			$hasRealChanges = true;
		}		
		
		foreach ($this->Languages() as $language) {
			foreach (self::$translatableFields as $transField) {
				$fieldName = $transField . '_' . $language->Locale;
				$fieldLabel = 'Event.' . strtoupper($transField);
				if (isset($changedFields[$fieldName])) {
					$logItem->AddChangedField($fieldName, $changedFields[$fieldName]['before'], $changedFields[$fieldName]['after'], $fieldLabel);
					$hasRealChanges = true;
				}
			}
		}
		
		if (isset($changedFields['GoogleMAP'])) {
			$logItem->AddChangedField('GoogleMAP', $changedFields['GoogleMAP']['before'], $changedFields['GoogleMAP']['after'], 'Event.ADDRESS');
			$hasRealChanges = true;
		}
		
		if (isset($changedFields['Homepage'])) {
			$logItem->AddChangedField('Homepage', $changedFields['Homepage']['before'], $changedFields['Homepage']['after'], 'Event.HOMEPAGE');
			$hasRealChanges = true;
		}		
		
		if (isset($changedFields['ShowGoogleMap'])) {
			$logItem->AddChangedField('ShowGoogleMap', $changedFields['ShowGoogleMap']['before'], $changedFields['ShowGoogleMap']['after'], 'Event.SHOWGOOGLEMAP');
			$hasRealChanges = true;
		}				
		
		if (isset($changedFields['Vasabladet_PublishTo'])) {
			$logItem->AddChangedField('Vasabladet_PublishTo', $changedFields['Vasabladet_PublishTo']['before'], $changedFields['Vasabladet_PublishTo']['after'], 'Vasabladet.PUBLISH');
			$hasRealChanges = true;
		}			
		
		if (isset($changedFields['Pohjalainen_PublishTo'])) {
			$logItem->AddChangedField('Pohjalainen_PublishTo', $changedFields['Pohjalainen_PublishTo']['before'], $changedFields['Pohjalainen_PublishTo']['after'], 'Pohjalainen.PUBLISH');
			$hasRealChanges = true;
		}					
		
		if (isset($changedFields['NetTicket_PublishTo'])) {
			$logItem->AddChangedField('NetTicket_PublishTo', $changedFields['NetTicket_PublishTo']['before'], $changedFields['NetTicket_PublishTo']['after'], 'NetTicket.PUBLISH');
			$hasRealChanges = true;
		}							
		
		return $hasRealChanges;
	}
}

class Event_Validator extends RequiredFields {
	protected $event = null;
	
	public function __construct($eventObject) { 
		$this->event = $eventObject;
		
		parent::__construct(); 
	}
   
	function php($data) { 
		$valid = parent::php($data); 
		if(isset($_REQUEST['ctf']['childID'])) { 
			$id = (int)$_REQUEST['ctf']['childID']; 
		} elseif(isset($_REQUEST['ID'])) { 
			$id = (int)$_REQUEST['ID']; 
		} else { 
			$id = null; 
		} 
	  
		$isDraft = $this->event->Status == 'Draft' ? true : false;
		$isPublish = ($this->event->Status == 'Accepted' || $this->event->Status == 'Preliminary') ? true : false;

		if (isset($data['Status'])) {
			if ($data['Status'] == 'Draft')
				$isDraft = true;
			else
				$isDraft = false;

			if ($data['Status'] == 'Accepted' || $data['Status'] == 'Preliminary') {
				$isPublish = true;
			}
			else 
				$isPublish = false;
		}
		
		// Can only change organizer to a organizer in a association where Im moderator		
		$myusers = $this->event->getMyUsers(null, 'moderators', true);	
		if (!empty($data['OrganizerID']) && !eCalendarExtension::isAdmin() ) {					
			if (!in_array($data['OrganizerID'], $myusers['All']) && $this->event->ID == 0) {
				$this->validationError('OrganizerID', sprintf(_t('eCalendarAdmin.ERROR_PERMISSION', 'Not allowed to set this value for %s'), _t('AssociatianOrganizer.SINGULARNAME', 'Organizer')));
				return false;
			}
		} 

		// Check start/end dates
		if (empty($data['EventDates']) && $isPublish) {
			$this->validationError('EventDates', _t('Event.ERROR_EVENTDATESMISSING', 'Missing or invalid event dates'));
			return false;
		}

		// Check start/end dates
		if (empty($data['PriceType']) && $isPublish) {
			$this->validationError('PriceType', _t('Event.ERROR_MISSINGPRICE', 'You must select if the event is free or not'));
			return false;
		}

		$requiredFields = array(		
			'AssociationID' => 'Association.SINGULARNAME',
			'OrganizerID' => 'AssociationOrganizer.SINGULARNAME',
			'Categories' => 'EventCategory.PLURALNAME',
			'MunicipalID' => 'Municipal.SINGULARNAME',			
			'Status' => 'Event.STATUS',
			'Languages' => 'Event.EVENTLANGUAGES'
		);
		
		if ($isPublish && !empty($data['Vasabladet_PublishTo'])) {
			$requiredFields['Vasabladet_Municipality'] = 'Vasabladet.MUNICIPALITY_VALIDATION';
			$requiredFields['Vasabladet_Category'] = 'Vasabladet.CATEGORY_VALIDATION';
			$requiredFields['Vasabladet_ShortText'] = 'Vasabladet.SHORTTEXT_VALIDATION';
		}	
		
		if ($isPublish && !empty($data['Pohjalainen_PublishTo'])) {
			$requiredFields['Pohjalainen_Title'] = 'Pohjalainen.TITLE_VALIDATION';
			$requiredFields['Pohjalainen_ShortText'] = 'Pohjalainen.SHORTTEXT_VALIDATION';
		}				
		
		if ($isPublish && !empty($data['NetTicket_PublishTo'])) {
			if (empty($data['NetTicket_AcceptTerms'])) {
				$this->validationError('NetTicket_AcceptTerms', _t('NetTicket.ACCEPTTERMS_VALIDATION', 'You must accept the NetTicket agreement'));
				return false;
			}
		}
		
		if (!isset($data['OrganizerID'])) {
			unset($requiredFields['OrganizerID']);	
		}

		$locale_objs = DataObject::get('CalendarLocale');
		$locale_list = array('' => '');
		if ($locale_objs)
			$locale_list = $locale_objs->map('ID', 'Locale');

		$req = 0;
		foreach ($locale_list as $locale_id => $locale_locale) {
			if (isset($data['Title_'.$locale_locale])) {
				$requiredFields['Title_'.$locale_locale] = 'Event.TITLE';
				$req++;
			}		
		}

		foreach ($locale_list as $locale_id => $locale_locale) {
			/*if (isset($data['EventText_'.$locale_locale]) && !$isDraft) {	
				$requiredFields['EventText_'.$locale_locale] = 'Event.DESCRIPTION';
			}*/
			if (isset($data['EventTextShort_'.$locale_locale]) && !$isDraft) {
				$requiredFields['EventTextShort_'.$locale_locale] = 'Event.DESCRIPTIONSHORT';
			}			
			if (isset($data['PriceType']) && $data['PriceType'] == 'NotFree' && isset($data['PriceText_'.$locale_locale]) && !$isDraft) {
				$requiredFields['PriceText_'.$locale_locale] = 'Event.PRICETEXT';
			}
		}

		if ($req == 0) {
			$requiredFields['Title_'.Translatable::default_locale()] = 'Event.TITLEDEFAULT';
			//$requiredFields['EventText_'.Translatable::default_locale()] = 'Event.EVENTTEXTDEFAULT';
			$requiredFields['EventTextShort_'.Translatable::default_locale()] = 'Event.EVENTTEXTSHORTDEFAULT';
		}

		foreach ($requiredFields as $key => $value) {
			if (isset($data[$key]) && empty($data[$key]) && $isPublish) {
				$this->validationError($key, sprintf(_t('DialogDataObjectManager.FILLOUT', 'Please fill out %s'), _t($value, $value)));
				return false;
			}
		}
       
      return $valid; 
   }
}

?>