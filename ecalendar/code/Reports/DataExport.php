<?php

class DataExport extends ReportController {
	
	protected $orientation = 'portrait';
	
	public function ReportForm() {
		$form = parent::ReportForm();
		Requirements::javascript('ecalendar/javascript/Reports/DataExport.js');
		Requirements::css('ecalendar/css/Reports/DataExport.css');
			
		return $form;
	}
	
	protected function ReportActions() {
		$actions = parent::ReportActions();
		$actions->push(new FormAction('SavePlaintextFile', _t('DataExport.SAVEPLAINTEXTFILE', 'Save plaintext file'), null, null, 'hidden'));
		return $actions;
	}
	
	protected function ReportOptionFields() {
		$fields = parent::ReportOptionFields();
		
		$organizersArray = array('' => _t('AdvancedDropdownField.NONESELECTED', '(None selected)'));
		$associationArray = array('' => _t('AdvancedDropdownField.NONESELECTED', '(None selected)'));
		$municipalsArray = array('' => _t('AdvancedDropdownField.NONESELECTED', '(None selected)'));
		$categoriesArray = array('' => _t('AdvancedDropdownField.NONESELECTED', '(None selected)'));
		$languagesArray = array('' => _t('AdvancedDropdownField.NONESELECTED', '(None selected)'));
		
		$associations = null;
		$organizers = null;
		$municipals = Municipal::toDropdownList();
		$categories = EventCategory::toDropdownList();
		$languages = CalendarLocale::toDropdownList();
		
		/* -- Associations that current user have the permission to choose from */

		if (eCalendarExtension::isAdmin()) {	
			$associations = Association::toDropdownList();
			$organizers = AssociationOrganizer::toDropdownList(true);
		} else {
			$currentUser = Member::currentUser();			
			
			$where_assoc = '';
			$where_organizer = '';

			$myassociations = $currentUser->getMyAssociations(null, 'organizers', true);			
			$where_assoc.= "(
				Association.ID IN ('".implode("','", $myassociations)."')
			)";	

			$myusers = $currentUser->getMyUsers(null, 'moderators', true);
			$where_organizer.= "(
				AssociationOrganizer.ID IN ('".implode("','", $myusers['All'])."')
			)";
		
			$association_objs = DataObject::get('Association', $where_assoc);
			if ($association_objs) {
				$associations = $association_objs->map('ID', 'NameHierachyAsText');
			}	

			$organizers_obj = DataObject::get('AssociationOrganizer', $where_organizer);			
			if ($organizers_obj)
				$organizers = $organizers_obj->map('ID', 'Name');
		}
					
		if ($associations)
			$associationArray += $associations;
		if ($organizers)
			$organizersArray += $organizers;
		if ($municipals)
			$municipalsArray += $municipals;
		if ($categories)
			$categoriesArray += $categories;
		if ($languages)
			$languagesArray += $languages;		

		$statusArray = array(
			'' => _t('AdvancedDropdownField.NONESELECTED', '(None selected)'),
 			'Preliminary' => _t('Event.STATUS_PRELIMINARY', 'Preliminary'),
 			'Accepted' => _t('Event.STATUS_ACCEPTED', 'Accepted'),
 			'Rejected' => _t('Event.STATUS_REJECTED', 'Rejected'),
 			'Cancelled' => _t('Event.STATUS_CANCELLED', 'Cancelled'),
	 	);		

		if (count($associationArray))
			$fields->push(new AdvancedDropdownField('AssociationID', _t('Association.SINGULARNAME', 'Association'), $associationArray));		
		if (count($organizersArray))
			$fields->push(new AdvancedDropdownField('OrganizerID', _t('AssociationOrganizer.SINGULARNAME', 'User'), $organizersArray));
		if (count($municipalsArray))
			$fields->push(new AdvancedDropdownField('Municipalities', _t('Municipal.PLURALNAME', 'Municipalities'), $municipalsArray, '', false, false, 'selectMunicipalitiesDropdown', 'showMunicipalitiesDropdown'));
		if (count($categoriesArray))
			$fields->push(new AdvancedDropdownField('Categories', _t('EventCategory.PLURALNAME', 'Categories'), $categoriesArray, '', false, false, 'selectCategoriesDropdown', 'showCategoriesDropdown'));
		$fields->push(new AdvancedDropdownField('Status', _t('Event.STATUS', 'Status'), $statusArray, 'Accepted'));
		if (count($languagesArray)) {
			$fields->push(new AdvancedDropdownField('LanguageID', _t('CalendarLocale.SINGULARNAME', 'Language'), $languagesArray));
			$fields->push(new CheckboxField('IncludeOtherLanguages', _t('DataExport.INCLUDEOTHERLANGUAGES', 'Use other languages if the selected language doesn\'t exist'), 1));
		}
		$fields->push(new CheckboxField('AlwaysIncludeShortText', _t('DataExport.ALWAYSINCLUDESHORTTEXT', 'Always include short text (even if it is the same as title)'), 1));
				
		return $fields;
	}
	
	public function GenerateReportData() {
		$customFields = array();
		$dataWhere = array();
		$dataJoin[] = 'LEFT JOIN EventDate ON EventDate.EventID = Event.ID';
		$dataJoin[] = 'LEFT JOIN Event_Categories ON Event_Categories.EventID = Event.ID LEFT JOIN EventCategory ON EventCategory.ID = Event_Categories.EventCategoryID';
		$dataJoin[] = 'LEFT JOIN Event_Languages ON Event_Languages.EventID = Event.ID LEFT JOIN CalendarLocale ON CalendarLocale.ID = Event_Languages.CalendarLocaleID';
		
		$where_assoc = '';
		$where_organizer = '';		
		$where_municipal = '';
		$where_categories = '';
		
		if (eCalendarExtension::isAdmin()) {	

		} else {
			$currentUser = Member::currentUser();			

			$myassociations = $currentUser->getMyAssociations(null, 'organizers', true);			
			$where_assoc.= "(
				AssociationID IN ('".implode("','", $myassociations)."')
			)";	

			$myusers = $currentUser->getMyUsers(null, 'moderators', true);
			$where_organizer.= "OrganizerID IN ('".implode("','", $myusers['All'])."')";
		}		
		
		if (!empty($this->data['StartDate'])) {
			$customFields['Start'] = $this->data['StartDate'];
			$date = new Zend_Date($this->data['StartDate'], 'dd.MM.yyyy', i18n::get_locale());
			
			$dataWhere[] = "EventDate.Date >= '" . $date->toString('yyyy-MM-dd') . "'";
		}
		if (!empty($this->data['EndDate'])) {
			$customFields['End'] = $this->data['EndDate'];
			$date = new Zend_Date($this->data['EndDate'], 'dd.MM.yyyy', i18n::get_locale());
			
			$dataWhere[] = "EventDate.Date <= '" . $date->toString('yyyy-MM-dd') . "'";
		}	
		if (!empty($this->data['Status'])) {
			$sqlSafeStatus = Convert::raw2sql($this->data['Status']);
			$dataWhere[] = "(Status = '$sqlSafeStatus' AND Status != 'Draft')";
		}
		else 
			$dataWhere[] = "Status != 'Draft'";
		
		if (!empty($this->data['AssociationID'])) {
			$dataWhere[] = "AssociationID = " . (int)$this->data['AssociationID'];
		}
		if (!empty($this->data['OrganizerID'])) {
			$dataWhere[] = "OrganizerID = " . (int)$this->data['OrganizerID'];
		}		
		if (!empty($this->data['Municipalities'])) {
			$municipalitiesID = explode(',', $this->data['Municipalities']);
			foreach ($municipalitiesID as &$municipalityID)
				$municipalityID = (int)$municipalityID;
			
			$dataWhere[] = "MunicipalID IN ('" . implode("','", $municipalitiesID) . "')";
		}
		if (!empty($this->data['Categories'])) {
			$categoriesID = explode(',', $this->data['Categories']);
			foreach ($categoriesID as &$categoryID)
				$categoryID = (int)$categoryID;
			
			$dataWhere[] = "EventCategory.ID IN ('" . implode("','", $categoriesID) . "')";
		}
		if (empty($this->data['IncludeOtherLanguages']) && !empty($this->data['LanguageID'])) {
			$dataWhere[] = 'CalendarLocale.ID = ' . (int)$this->data['LanguageID'];
		}

		if (!empty($this->data['AlwaysIncludeShortText']))
			$customFields['AlwaysIncludeShortText'] = true;
		else
			$customFields['AlwaysIncludeShortText'] = false;
		
		if (!empty($where_organizer))
			$dataWhere[] = $where_organizer;
		if (!empty($where_assoc))
			$dataWhere[] = $where_assoc;		
		
		// Change locale
		if (!empty($this->data['LanguageID'])) {
			$calendarLocale = DataObject::get_by_id('CalendarLocale', (int)$this->data['LanguageID']);
			if ($calendarLocale) {
				i18n::set_locale($calendarLocale->Locale);
				Translatable::set_current_locale($calendarLocale->Locale);
			}
		}
		
		$events = DataObject::get('Event', implode(' AND ', $dataWhere), 'Created', implode(' ', $dataJoin));
		if ($events) {
			$events->sort('Start', 'ASC');
			$customFields['Events'] = $events;
		}
		
		$resultOutput = $this->renderWith('Reports/DataExport', $customFields);
		$this->StoreReportCache($resultOutput);
		return $resultOutput;
	}

	public function SavePlaintextFile($data, $form) {
		$downloadToken = time();
		if (isset($_REQUEST['DownloadToken'])) {
			$downloadToken = $_REQUEST['DownloadToken'];
		}
		Cookie::set('fileDownloadToken', $downloadToken);
		
		$this->data = $data;
		
		$cache = $this->GetReportCache();
		
		$filedata = str_replace(array("\r\n", "\n\r", "\n", "\r", "\t"), '', $cache);
		if (isset($data['UserOS'])) {
			if ($data['UserOS'] == 'Windows')
				$filedata = str_replace(array('<br/>', '<br />'), "\r\n", $filedata);
			else
				$filedata = str_replace(array('<br/>', '<br />'), "\n", $filedata);
		}
		else
			$filedata = str_replace(array('<br/>', '<br />'), "\n", $filedata);
		
		$filedata = html_entity_decode($filedata, ENT_QUOTES, 'utf-8');
		
		if (!headers_sent()) {
				// set cache-control headers explicitly for https traffic, otherwise no-cache will be used,
				// which will break file attachments in IE
				if (isset($_SERVER['HTTPS'])) {
					header('Cache-Control: private');
					header('Pragma: ');
				}
				header('Content-disposition: attachment; filename=calendar_data.txt');
				header('Content-type: text/html; charset=utf-8'); 
				header('Content-Length: '.count($filedata));
				return $filedata;
		}
		
		return new SS_HTTPResponse('Invalid file', 400);
	}
}

?>