<?php

class EventPage extends Page {	
	public function getShowCategories() {	
		return true;
	}
	
	public function getShowEventsToday() {
		return true;
	}
	
	// Apply parent MenuTitle to children
	function MenuTitle() {
		$childActive = null;
		$children = $this->Children();
		foreach ($children as $child) {
			if ($child->isSection()) {
				$childActive = $child;
				break;
			}
		}
		
		if ($childActive) 
			return $childActive->Parent()->MenuTitle;

		return $this->MenuTitle;
	}
	
	function NormalLink() {
		return parent::Link();
	}
	
	// Apply child link to parent
	function Link() {
		$childActive = null;
		$children = $this->Children();
		foreach ($children as $child) {
			if ($child->isSection()) {
				$childActive = $child;
				break;
			}
		}
				
		if ($childActive)
			return $childActive->Link();
				
		return parent::Link();
	}
	
	function TranslatableEventLink() {
		$action = Controller::curr()->getRequest()->param('Action');
		$id = (int)Controller::curr()->getRequest()->param('ID');
				
		if (($action == 'showEvent' || $action == 'showCategory') && $id)
			return Controller::join_links($this->Link(), $action, $id);
		else if ($action == 'showResults')
			return Controller::join_links($this->Link(), $action);
		
		return false;
	}
}

class EventPage_Controller extends Page_Controller {
	static $extensions = array(
		'CreaDataObjectExtension',
		'eCalendarExtension'
	);
	
	static $allowed_actions = array(
		'EventSearchForm',
		'showResults',
		'showEvent',
		'reportEvent',
		'reportEvent_HTML',
		'showCategory'
	);
	
	protected $totalItems = 0;
	protected $itemsPerPage = 0; // Use remote default
	protected $startItem = 0;
	protected $rssFeedLink = '';
	protected $layoutTemplate = 'EventPage';
	protected $pageTemplate = 'Page';
	protected $eventPDFTemplate = 'PDFEvent';
	protected $maxVisibleDates = 10;
	protected $showResultsRedirect = 'showResults';
	
	public function init() {
		parent::init();
		
		Validator::set_javascript_validation_handler('none');
		
		Session::set('ThemeFromEventPageID', 0);
	}
	
	public function index() {
		return $this->renderWith(array($this->layoutTemplate, $this->pageTemplate));
	}
	
	public function EventSearchForm() {
		
		$eventsForArray = array(
			'' => _t('AdvancedDropdownField.NONESELECTED', '(None selected)'),
			'today' => _t('EventPage.EVENTSFOR_TODAY', 'Today'),
			'tomorrow' => _t('EventPage.EVENTSFOR_TOMORROW', 'Tomorrow'),
			'week' => _t('EventPage.EVENTSFOR_WEEKFORWARD', 'A week forward'),
			'two-weeks' => _t('EventPage.EVENTSFOR_TWOWEEKSFORWARD', 'Two weeks forward'),
			'month' => _t('EventPage.EVENTSFOR_MONTHFORWARD', 'A month forward'),
			'custom' => _t('EventPage.EVENTSFOR_CUSTOM', 'Custom')
		);
		
		$categories = array('' => _t('AdvancedDropdownField.NONESELECTED', '(None selected)'));
		$municipalities = array('' => _t('AdvancedDropdownField.NONESELECTED', '(None selected)'));
		
		$eventService = new RemoteDataService();
		
		try {
			$xml = $eventService->request('categories?Locale=' . i18n::get_locale());
			$resources = $eventService->getValues($xml->getBody(), 'Categories', 'Category');
		
			foreach ($resources as $resource) {
				$categories += array($resource->ID => $resource->Name);
			}
		}
		catch (Exception $e) {
			
		}
		
		try {
			$xml = $eventService->request('municipalities?Locale=' . i18n::get_locale());
			$resources = $eventService->getValues($xml->getBody(), 'Municipalities', 'Municipality');
		
			foreach ($resources as $resource) {
				$municipalities += array($resource->ID => $resource->Name);
			}		
		}
		catch (Exception $e) {
			
		}
		
		$fields = new FieldSet(
				new TextField('Keywords', _t('EventPage.KEYWORDS', 'Keyswords')),
				new AdvancedDropdownField('EventsFor', _t('EventPage.EVENTSFOR', 'Events for'), $eventsForArray),
				$datesGroup = new FieldGroup(
					$dateFrom = new DateFieldEx('DateFrom', _t('EventPage.DATEFROM', 'From')),
					$dateTo = new DateFieldEx('DateTo', _t('EventPage.DATETO', 'To'))
				),
				new AdvancedDropdownField('Categories', _t('EventCategory.PLURALNAME', 'Categories'), $categories, '', false, false, 'selectCategoryDropdown', 'showCategoryDropdown'),
				new AdvancedDropdownField('Municipalities', _t('Municipal.PLURALNAME', 'Municipalitites'), $municipalities, '', false, false, 'selectMunicipalityDropdown', 'showMunicipalityDropdown')
		);

		$datesGroup->addExtraClass('dateRangeGroup');
		$dateFrom->addExtraClass('date-from');
		$dateTo->addExtraClass('date-to');
		
		$actions = new FieldSet(
				/*new FormAction('showAll', _t('EventPage.SHOWALL', 'Show all'), null, null, 'showallbutton'),*/
				new FormAction('performSearch', _t('EventPage.SEARCH', 'Search'))
		);
						
		$this->updateSearchFormFields($fields);
		
		$form = new Form($this, 'EventSearchForm', $fields, $actions);
		$form->disableSecurityToken();
			
		return $form;
	}
	
	protected function updateSearchFormFields($fields) {
		$this->extend('updateSearchFormFields', $fields);		
	}
	
	public function EventCategories() {
		$categories = new DataObjectSet();
		
		$eventService = new RemoteDataService();
			
		try {			
			$xml = $eventService->request('categories?Locale=' . i18n::get_locale());
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
			$xml = $eventService->request('search?Locale=' . i18n::get_locale() . '&Limit=5');
			$events = $eventService->getValues($xml->getBody(), 'Events', 'Event');
		} 
		catch (Exception $e) {
			
		}
	
		return $events->getRange(0, 5);
	}
	
	public function performSearch($data, $form) {
		$keywords = isset($data['Keywords']) ? Convert::raw2sql($data['Keywords']) : '';
		$eventsFor = isset($data['EventsFor']) ? Convert::raw2sql($data['EventsFor']) : '';
		$dateFrom = isset($data['DateFrom']) ? Convert::raw2sql($data['DateFrom']) : '';
		$dateTo = isset($data['DateTo']) ? Convert::raw2sql($data['DateTo']) : '';
		$categories = isset($data['Categories']) ? Convert::raw2sql($data['Categories']) : '';
		$municipalities = isset($data['Municipalities']) ? Convert::raw2sql($data['Municipalities']) : '';
					
		Session::clear('searchParameters');
		Session::save();
		
		Session::set('searchParameters', array(
			'showAll' => false,
			'Keywords' => $keywords,
			'EventsFor' => $eventsFor,
			'DateFrom' => $dateFrom,
			'DateTo' => $dateTo,
			'Categories' => $categories,
			'Municipalities' => $municipalities
		));
		
		Director::redirect($this->showResultsRedirect);
	}
	
	public function showAll($data, $form) {
		Session::clear('searchParameters');
		Session::save();
		
		Session::set('searchParameters', array(
			'showAll' => true,
			'Keywords' => '',
			'EventsFor' => '',
			'DateFrom' => '',
			'DateTo' => '',
			'Categories' => '',
			'Municipalities' => ''
		));			
		
		
		Director::redirect($this->showResultsRedirect);
	}
	
	public function showResults() {
		// Pagination
		if (!isset($_GET['start']) || !is_numeric($_GET['start']) || (int)$_GET['start'] < 1) 
			$_GET['start'] = 0;
		$this->startItem = (int)$_GET['start'];				
		
		$sortByField = isset($_GET['sortByField']) ? Convert::raw2sql($_GET['sortByField']) : 'Date';
		$sortDir = isset($_GET['sortDir']) ? Convert::raw2sql($_GET['sortDir']) : 'ASC';
			
		$results = $this->updateSearchData();
		
		if ($results)
			$results->setPageLimits($this->startItem, $this->itemsPerPage, $this->totalItems);
		
		/*$customData['sort'] = 'CourseCode';
		$customData['sort_dir'] = 'ASC';
		
		if (isset($_GET['sort']) && isset($_GET['sort_dir'])) {
			$customData['sort'] = $_GET['sort'];
			$customData['sort_dir'] = $_GET['sort_dir'];
			
			switch ($customData['sort']) {
				case 'code': $customData['sort'] = 'CourseCode'; break;
				case 'name': $customData['sort'] = 'NameList'; break;
				case 'location': $customData['sort'] = 'MainLocationOffice'; break;
				case 'freespots': $customData['sort'] = 'FreeSpots'; break;
				case 'startdate': $customData['sort'] = 'RecDateStart'; break;
				case 'stopdate': $customData['sort'] = 'RecDateEnd'; break;
				default: $customData['sort'] = 'CourseCode'; break;
			}			
			
			$results->sort($customData['sort'], $customData['sort_dir']);
		}*/	
		
		$customData['showResults'] = true;
		$customData['Events'] = $results;
		$customData['RSSLink'] = $this->rssFeedLink;
		$customData['SearchText'] = null;
		
		$searchParameters = Session::get('searchParameters');
		if ($searchParameters) {
			if (!empty($searchParameters['EventsFor'])) {
				$eventsFor = $searchParameters['EventsFor'];
				
				if ($eventsFor == 'today') 
					$customData['SearchText'] = date('d.m.Y');
				else if ($eventsFor == 'tomorrow')
					$customData['SearchText'] = date('d.m.Y', strtotime('+1 day'));
				else if ($eventsFor == 'week')
					$customData['SearchText'] = date('d.m.Y') . ' - ' . date('d.m.Y', strtotime('+1 week'));
				else if ($eventsFor == 'two-weeks')
					$customData['SearchText'] = date('d.m.Y') . ' - ' . date('d.m.Y', strtotime('+2 weeks'));
				else if ($eventsFor == 'month')
					$customData['SearchText'] = date('d.m.Y') . ' - ' . date('d.m.Y', strtotime('+1 month'));				
				else if ($eventsFor == 'custom')
					$customData['SearchText'] = date('d.m.Y', strtotime($searchParameters['DateFrom'])) . ' - ' . date('d.m.Y', strtotime($searchParameters['DateTo']));	
			}
		}
		
		if (count($results) == 0)
			$customData['nothingFound'] = true;
		
		$this->beforeShowResults($customData);
		
		$customData['SortByLink']['Date'] = Controller::join_links($this->Link(), 'showResults?sortByField=Date&sortDir=ASC');
		$customData['SortByLink']['Period'] = Controller::join_links($this->Link(), 'showResults?sortByField=Period&sortDir=ASC');
		$customData['SortByLink']['Title'] = Controller::join_links($this->Link(), 'showResults?sortByField=Title&sortDir=ASC');
		$customData['SortByLink']['Categories'] = Controller::join_links($this->Link(), 'showResults?sortByField=Categories&sortDir=ASC');
		$customData['SortByLink']['Municipality'] = Controller::join_links($this->Link(), 'showResults?sortByField=Municipality&sortDir=ASC');
		$customData['SortByLink']['Place'] = Controller::join_links($this->Link(), 'showResults?sortByField=Place&sortDir=ASC');
		
		if ($sortByField == 'Date' && $sortDir == 'ASC')
			$customData['SortByLink']['Date'] = Controller::join_links($this->Link(), 'showResults?sortByField=Date&sortDir=DESC');
		else if ($sortByField == 'Period' && $sortDir == 'ASC')
			$customData['SortByLink']['Period'] = Controller::join_links($this->Link(), 'showResults?sortByField=Period&sortDir=DESC');
		else if ($sortByField == 'Title' && $sortDir == 'ASC')
			$customData['SortByLink']['Title'] = Controller::join_links($this->Link(), 'showResults?sortByField=Title&sortDir=DESC');		
		else if ($sortByField == 'Categories' && $sortDir == 'ASC')
			$customData['SortByLink']['Categories'] = Controller::join_links($this->Link(), 'showResults?sortByField=Categories&sortDir=DESC');				
		else if ($sortByField == 'Municipality' && $sortDir == 'ASC')
			$customData['SortByLink']['Municipality'] = Controller::join_links($this->Link(), 'showResults?sortByField=Municipality&sortDir=DESC');				
		else if ($sortByField == 'Place' && $sortDir == 'ASC')
			$customData['SortByLink']['Place'] = Controller::join_links($this->Link(), 'showResults?sortByField=Place&sortDir=DESC');						
		
		$customData['SortByLink'] = new ArrayData($customData['SortByLink']);
		
		return $this->renderWith(array($this->layoutTemplate, $this->pageTemplate), $customData);
	}
	
	protected function beforeShowResults(&$customData) {
		$this->extend('beforeShowResults', $customData);
	}
	
	public function showCategory() {
		$events = null;
		$categoryID = (int)$this->urlParams['ID'];
				
		Session::clear('searchParameters');
		Session::save();
		
		Session::set('searchParameters', array(
			'showAll' => false,
			'Keywords' => '',
			'EventsFor' => '',
			'DateFrom' => '',
			'DateTo' => '',
			'Categories' => $categoryID,
			'Municipalities' => ''
		));		
		
		return $this->showResults();
	}	
	
	public function showEvent() {
		Requirements::javascript( THIRDPARTY_DIR.'/jquery/jquery.js' );
		Requirements::javascript('ecalendar/javascript/jquery-ui-1.8.6.custom.min.js');
		Requirements::css('ecalendar/css/smoothness/jquery-ui-1.8.6.custom.css');
		Requirements::javascript('sapphire/thirdparty/jquery-form/jquery.form.js');
		Requirements::javascript(SAPPHIRE_DIR . '/javascript/i18n.js');
		Requirements::add_i18n_javascript('ecalendar/javascript/lang');
		
		// AddThis Widget
		Requirements::customScript('var addthis_config = { ui_language: "' . _t('Locale.SHORTCODE', 'en') . '" };');
		Requirements::javascript(Director::protocol() . 's7.addthis.com/js/250/addthis_widget.js#pubid=xa-4ed5daf242d9c4e9');
		
		Requirements::javascript( 'ecalendar/javascript/jquery.cycle.all.js' );
		$script = <<<_HTML
			jQuery(function() {
				if (jQuery('.images').children().length > 1)
				jQuery('.images').cycle({
					fx: 'fade',
					speed:  5000 
				});
			});
_HTML;
		
		// Disable slideshow for now
		//Requirements::customScript($script);
		
		$event = null;
		$id = (int)$this->urlParams['ID'];
		$pdf = isset($_GET['pdf']) ? true : false;
				
		$eventService = new RemoteDataService();
			
		try {
			$xml = $eventService->request('event?Locale=' . i18n::get_locale() . '&ID=' . $id);
			$events = $eventService->getValues($xml->getBody(), 'Event');
							
			if ($events && $events->Count() == 1) {
				$event = $events->First();
				
				$startDate = new SS_Datetime();
				$endDate = new SS_Datetime();
					
				$startDate->setValue($event->Start);
				$endDate->setValue($event->End);
					
				$event->Start = $startDate;
				$event->End = $endDate;

				$images = $eventService->getValues($xml->getBody(), 'Event', 'Images', 'Image');				
				if ($images)
					$event->Images = $images;
				
				$organizer = $eventService->getValues($xml->getBody(), 'Event', 'Organizer');
				if ($organizer)
					$event->Organizer = $organizer;
				
				$association = $eventService->getValues($xml->getBody(), 'Event', 'Association');
				if ($association)
					$event->Association = $association;
				
				$associationLogo = $eventService->getValues($xml->getBody(), 'Event', 'Association', 'Logo');				
				if ($associationLogo) 
					$event->AssociationLogo = $associationLogo;
				
				
				
				/* Files, Links = Attachments */
				$links = $eventService->getValues($xml->getBody(), 'Event', 'Attachments', 'Link');				
				if ($links) {
					$event->Links = $links;
				}				
				
				$files = $eventService->getValues($xml->getBody(), 'Event', 'Attachments', 'File');				
				if ($files) {
					$event->Files = $files;
				}
				
				if ($files->Count() > 0 || $links->Count() > 0) {
					$event->hasAttachments = true;
				} else {
					$event->hasAttachments = false;
				}
				/* -------------------------- */
				
				/*$municipalities = $eventService->getValues($xml->getBody(), 'Event', 'Municipalities', 'Municipality');
				if ($municipalities)
					$event->Municipalities = $municipalities;*/
				
				$categories = $eventService->getValues($xml->getBody(), 'Event', 'Categories', 'Category');
				if ($categories)
					$event->Categories = $categories;					

				$dates = $eventService->getValues($xml->getBody(), 'Event', 'Dates', 'Date');
				if ($dates) {
					$filteredDates = new DataObjectSet();
					$moreFilteredDates = new DataObjectSet();
					
					foreach ($dates as $date) {
						if (strtotime(date('d.m.Y 23:59:59', strtotime($date->Start))) < time())
							continue;
						
						$startDate = new SS_Datetime();
						$endDate = new SS_Datetime();
					
						$startDate->setValue($date->Start);
						$endDate->setValue($date->End);
					
						$date->Start = $startDate;
						$date->End = $endDate;	
						
						if ($date->Start == $date->End)
							$date->HasEndTime = false;
						else
							$date->HasEndTime = true;
						
						if ($filteredDates->Count() > $this->maxVisibleDates)
							$moreFilteredDates->push($date);
						else
							$filteredDates->push($date);
					}
					
					$event->Dates = $filteredDates;
					$event->MoreDates = $moreFilteredDates;
					if ($filteredDates->Count() > 1) {
						$event->OtherDates = true;
					} else {
						$event->OtherDates = false;
					}
				}
						
				$googleAddress = $event->PostalAddress;
				if (!empty($event->PostalCode)) $googleAddress .= ", {$event->PostalCode}";
				if (!empty($event->PostalOffice)) $googleAddress .= ", {$event->PostalOffice}";				
				
				$event->GA_MarkerAddress = $googleAddress;
				$event->GA_MarkerAddressEncoded = urlencode($googleAddress);	
			}
		}		
		catch (Exception $e) {

		}
			
		$customData['showEvent'] = true;
		$customData['Event'] = $event;	
		$customData['ReportURL'] = '';
		if ($event)
			$customData['ReportURL'] = $this->Link() . 'reportEvent/' . $event->ID;
		
		if ($event === null)
			$customData['nothingFound'] = true;				
		
		if ($event && $pdf) {
			Requirements::clear();
			
			$this->beforeShowPDFEvent($customData);
			
			return singleton('PDFRenditionService')->render($this->renderWith($this->eventPDFTemplate, $customData), 'browser', 'event.pdf');
			//return singleton('EventPage')->renderWith('PDFEvent', $customData);
		}
				
		if ($event && $event->ShowGoogleMap == 1) {
			Requirements::javascript(Director::protocol() . 'maps.google.com/maps/api/js?sensor=false');
		
			$customJS = <<<GOOGLEMAPS_JS
				jQuery(function() {
					var gmapcontainer = jQuery('#GoogleMapsContainer');
					
					if (!gmapcontainer.length) 
						return;
		
					gmapcontainer.show();

					options = {
						zoom: 12,
						center: '0,0',
						streetViewControl:true,
						scaleControl: false,
						mapTypeId: google.maps.MapTypeId.ROADMAP,
						mapTypeControl: true,
						mapTypeControlOptions: { style: google.maps.MapTypeControlStyle.HORIZONTAL_BAR }
					};
					
					geocoder = new google.maps.Geocoder();
					var address = '{$event->GA_MarkerAddress}';
					geocoder.geocode( { 'address': address}, function(results, status) {
						if (status == google.maps.GeocoderStatus.OK) {
							options.center = results[0].geometry.location;
							map = new google.maps.Map(gmapcontainer[0],options);
							var marker = new google.maps.Marker({ map: map,  position: options.center, title: '{$event->Title} - ' + address });
						} 
						else {
							//console.log('Google maps error: ' + status);
							gmapcontainer.remove();
						}
					});
				});
GOOGLEMAPS_JS;
			
			Requirements::customScript($customJS);	
		}
		
		$this->beforeShowEvent($customData);
		
		return $this->renderWith(array($this->layoutTemplate, $this->pageTemplate), $customData);
	}
	
	protected function beforeShowPDFEvent(&$customData) {
		$this->extend('beforeShowPDFEvent', $customData);
	}
	
	protected function beforeShowEvent(&$customData) {
		$this->extend('beforeShowEvent', $customData);	
	}	
	
	protected function updateSearchData() {
		$events = new DataObjectSet();
		$searchParameters = Session::get('searchParameters');
		
		$sortByField = isset($_GET['sortByField']) ? Convert::raw2sql($_GET['sortByField']) : 'Date';
		$sortDir = isset($_GET['sortDir']) ? Convert::raw2sql($_GET['sortDir']) : 'ASC';		
		
		$this->updateSearchParameters($searchParameters);
				
		if (isset($searchParameters['showAll']) && $searchParameters['showAll'] == true) {
			$eventService = new RemoteDataService();
			
			$municipalitiesQuery = '';
			if (!empty($searchParameters['Municipalities']))
				$municipalitiesQuery .= '&Municipalities=' . $searchParameters['Municipalities'];			
			
			$this->rssFeedLink = RemoteDataService::$remoteServiceURL . 'allEvents?Locale=' . i18n::get_locale() . $municipalitiesQuery . '&Format=rss' . '&SortField=' . $sortByField . '&SortDir=' . $sortDir;
			
			try {
				$xml = $eventService->request('allEvents?Locale=' . i18n::get_locale() . $municipalitiesQuery . '&Start=' . $this->startItem . '&Limit=' . $this->itemsPerPage . '&SortField=' . $sortByField . '&SortDir=' . $sortDir);
				$events = $eventService->getValues($xml->getBody(), 'Events', 'Event');
				$this->totalItems = $eventService->getValue($xml->getBody(), 'TotalItems');
				$this->itemsPerPage = $eventService->getValue($xml->getBody(), 'Limit');
				
				foreach ($events as $event) {
					$startDate = new SS_Datetime();
					$endDate = new SS_Datetime();
					
					$startDate->setValue($event->Start);
					$endDate->setValue($event->End);
					
					$event->Start = $startDate;
					$event->End = $endDate;
					
					//$event->Municipalities = str_replace(',', '<br/>', $event->Municipalities_Municipality_Name);					
				}
			}
			catch (Exception $e) {
			
			}
		}
		else {
			$query = 'Locale=' . i18n::get_locale() . '&Start=' . $this->startItem . '&Limit=' . $this->itemsPerPage . '&SortField=' . $sortByField . '&SortDir=' . $sortDir;
			$startDate = date('Y-m-d', time());
			$endDate = null;
			
			if (!empty($searchParameters['Keywords'])) 
				$query .= '&Keywords=' . rawurlencode($searchParameters['Keywords']);
			if (!empty($searchParameters['Categories']))
				$query .= '&Categories=' . $searchParameters['Categories'];
			if (!empty($searchParameters['Municipalities']))
				$query .= '&Municipalities=' . $searchParameters['Municipalities'];			
			if (!empty($searchParameters['EventsFor'])) {
				$query .= '&Period=' . $searchParameters['EventsFor'];	
				if ($searchParameters['EventsFor'] == 'today') {
					$startDate = date('Y-m-d', time());
					$endDate = date('Y-m-d', time());	
				} 
				else if ($searchParameters['EventsFor'] == 'tomorrow') {
					$startDate = date('Y-m-d', time()+60*60*24);
					$endDate = date('Y-m-d', time()+60*60*24);	
				}
				else if ($searchParameters['EventsFor'] == 'week') {
					$startDate = date('Y-m-d', time());
					$endDate = date('Y-m-d', time()+60*60*24*7);	
				}			
				else if ($searchParameters['EventsFor'] == 'two-week') {
					$startDate = date('Y-m-d', time());
					$endDate = date('Y-m-d', time()+60*60*24*7*2);	
				}						
				else if ($searchParameters['EventsFor'] == 'month') {
					$startDate = date('Y-m-d', time());
					$endDate = date('Y-m-d', time()+60*60*24*7*4);	
				}				
				else if ($searchParameters['EventsFor'] == 'custom') { 
					if (!empty($searchParameters['DateFrom'])) {
						$startDate = date('Y-m-d', strtotime($searchParameters['DateFrom']));
						$query .= '&StartDate=' . rawurlencode($startDate);
					}
					if (!empty($searchParameters['DateTo'])) {
						$endDate = date('Y-m-d', strtotime($searchParameters['DateTo']));
						$query .= '&EndDate=' . rawurlencode($endDate);
					}					
				}
			}
			
			$eventService = new RemoteDataService();
			
			$this->rssFeedLink = RemoteDataService::$remoteServiceURL . 'search?' . $query . '&Format=rss';	
			
			try {
				$xml = $eventService->request('search?' . $query);
				//$events = $eventService->getValues($xml->getBody(), 'Events', 'Event');
				//$this->totalItems = $eventService->getValue($xml->getBody(), 'TotalItems');
				//$this->itemsPerPage = $eventService->getValue($xml->getBody(), 'Limit');				
				
				$xml_body = new SimpleXMLElement($xml->getBody());
				$this->totalItems = $xml_body->TotalItems;
				$this->itemsPerPage =  $xml_body->Limit;
								
				
				$xml_events = $xml_body->Events->Event;
				if ($xml_events && $xml_events->count() > 0) {
					foreach ($xml_events as $xml_event) {
						$event = array();
						foreach ($xml_event->children() as $xml_eventchild) {							
							if ($xml_eventchild->getName() == 'Categories') {
								$event['Categories'] = new DataObjectSet();
								if ($xml_eventchild->Category->count()) {
									foreach ($xml_eventchild->Category as $category) {
										$event['Categories']->push(new ArrayData(array('ID' => (int)$category->ID, 'Name' => sprintf('%s', $category->Name))));
									}
								}
							}
							else if ($xml_eventchild->getName() == 'Dates') {
								$event['Dates'] = new DataObjectSet();
								$event['ShowMoreDates'] = new DataObjectSet();
								
								if ($xml_eventchild->Date->count()) {
									foreach ($xml_eventchild->Date as $date) {
										
										$dateObject = array();
										$dateObject['ID'] = (int)$date->ID;
										$dateObject['Start'] = new SS_Datetime();
										$dateObject['End'] = new SS_Datetime();
																			
										$dateObject['Start']->setValue(sprintf('%s', $date->Start));
										$dateObject['End']->setValue(sprintf('%s', $date->End));
										
										$dateObject['DayOfWeek'] = _t('Date.SHORT_' . strtoupper($dateObject['Start']->Day()), substr($dateObject['Start']->Day(), 0, 3));
										
										if ($dateObject['Start'] == $dateObject['End'])
											$dateObject['HasEnd'] = false;
										else
											$dateObject['HasEnd'] = true;
										
										if ($dateObject['Start']->Format('Y-m-d') < $startDate) {
											$dateObject['ShowInMore'] = false;
										}
										/*else if ($endDate && $dateObject['End']->Format('Y-m-d') > $endDate) {
											$dateObject['ShowInMore'] = false;
										}*/										
										else {
											$dateObject['ShowInMore'] = true;
										}

										$dateObject = new ArrayData($dateObject);
										$event['Dates']->push($dateObject);
										if ($dateObject->ShowInMore && $event['ShowMoreDates']->Count() <= $this->maxVisibleDates)
											$event['ShowMoreDates']->push($dateObject);
									}
								}
								
								$event['ShowMoreDatesPopup'] = $event['ShowMoreDates']->Count() > 1 ? true : false;
								
								if ($event['Dates']->First() == $event['Dates']->Last())
									$event['ShowPeriod'] = false;
								else
									$event['ShowPeriod'] = true;
							}
							else if ($xml_eventchild->getName() == 'Images') {
								
							}
							else if ($xml_eventchild->getName() == 'Attachments') {
								
							}							
							else if ($xml_eventchild->getName() == 'Association') {
								
							}
							else if ($xml_eventchild->getName() == 'Start' || $xml_eventchild->getName() == 'End') {
								$event[$xml_eventchild->getName()] = new SS_Datetime();
								$event[$xml_eventchild->getName()]->setValue(sprintf('%s', $xml_eventchild));
							}
							else {
								$event[$xml_eventchild->getName()] = sprintf('%s', $xml_eventchild);
							}
						}
						
						$events->push(new ArrayData($event));
					}
				}			
			}
			catch (Exception $e) {
				
			}
		}
		
		return $events;
	}
	
	protected function updateSearchParameters(&$searchParameters) {
		$this->extend('updateSearchParameters', $searchParameters);
	}

	public function ControllerLink() {
		$page = Translatable::get_one_by_lang('SiteTree', i18n::get_locale(), "ClassName = '" . str_replace('_Controller', '', $this->class) . "'");
 		if (!$page) {
			$page = DataObject::get_one('' . str_replace('_Controller', '', $this->class) . '');
		}
		
		if (!$page) {
			return '';
		}
		
		if ($page->Locale != Translatable::get_current_locale()) {
			$page = $page->getTranslation(Translatable::get_current_locale());
	  }
		
	  return $page->Link();
	}
	
	public function reportEvent() {
		$id = (int)$this->urlParams['ID'];
		
		$fields = new FieldSet(
			new TextareaField('Reason', _t('EventPage.REPORTREASON', 'Reason')),
			new HiddenField('EventID', '', $id)
		);
				
		$form = new Form($this, 'reportEvent', $fields, new FieldSet(new FormAction("submitReportEvent", "")), new RequiredFields('Reason'));
		if (!Director::is_ajax())
			return $form->forTemplate();
		return $form;
	}
	
	public function reportEvent_HTML() {
		return $this->reportEvent()->forAjaxTemplate();
	}
	
	public function submitReportEvent($data, $form) {
		if (!isset($data['EventID']))
			return '';
		
		$id = (int)$data['EventID'];
		
		$event = DataObject::get_by_id('Event', $id);
		if ($event) {
			$body = sprintf(_t('Event.REPORTED_BODY', 'Event "%s" has been reported as invalid.'),$event->Title)."\n\n";
			$body .= $data['Reason'];
			
			$association = $event->Association();
			$moderators = $association->Municipal()->AssociationOrganizers();
			if ($moderators && count($moderators)) {
				foreach ($moderators as $moderator) {
					$origLocale = i18n::get_locale();
					i18n::set_locale($moderator->Locale);

					$msg = new IM_Message();
					$msg->ToID = $moderator->ID;
					$msg->Subject = _t('Event.REPORTED_TITLE', 'Reported event');
					$msg->Body = $body;
					$msg->send(false);

					i18n::set_locale($origLocale);					
				}
			} 
			else {
				// No municipal admins, send to normal admins
				$admins = eCalendarExtension::FindAdministrators();
				if ($admins) {
					foreach ($admins as $admin) {
						$origLocale = i18n::get_locale();
						i18n::set_locale($admin->Locale);

						$msg = new IM_Message();
						$msg->ToID = $admin->ID;
						$msg->Subject = _t('Event.REPORTED_TITLE', 'Reported event');
						$msg->Body = $body;
						$msg->send(false);

						i18n::set_locale($origLocale);					
					}					
				}
			}
		}
		
		return _t('EventPage.REPORTEVENT_SUCCESS', 'Thank you!<br/>We will investigate this as soon as possible.') . '<input type="hidden" name="Result" value="OK"/>';
	}
}

?>