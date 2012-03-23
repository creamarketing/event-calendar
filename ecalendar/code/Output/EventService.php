<?php

/*
 * EventService can be used from external website to acquire data about events
 * 
 */

class SimpleXMLExtended extends SimpleXMLElement{   
  public function addCData($cdata_text){   
   $node= dom_import_simplexml($this);   
   $no = $node->ownerDocument;   
   $node->appendChild($no->createCDATASection($cdata_text));   
  }   
} 

class EventService extends Controller {
	
	protected $defaultLocale = 'en_US';
	protected $defaultSort = 'Date';
	protected $defaultSortDir = 'ASC';
	protected $defaultFormat = 'xml';
	protected $defaultFeed = 'None';
	protected $defaultStart = 0;
	protected $defaultLimit = 10;
	protected $maxItemsPerQuery = 100;
	protected $allowedSortFields = array('Date', 'Period', 'Title', 'Municipality', 'Categories', 'Place');
	
	protected $currentLocale;
	protected $currentSortField;
	protected $currentSortDir;
	protected $currentFormat;
	protected $currentStart;
	protected $currentLimit;
	protected $currentFeed;
	
	protected $quirks = '';
	
	private $invalidQuery = 0;
	
	function init() {
		parent::init();
	
		$data = $_GET;
		
		$locale = !empty($_GET['Locale']) ? $_GET['Locale'] : $this->defaultLocale;
		$sort_field = !empty($_GET['SortField']) ? $_GET['SortField'] : $this->defaultSort;
		$sort_dir = !empty($_GET['SortDir']) ? strtoupper($_GET['SortDir']) : $this->defaultSortDir;
		$format = !empty($_GET['Format']) ? strtolower($_GET['Format']) : $this->defaultFormat;
		$start = !empty($_GET['Start']) ? (int)$_GET['Start'] : $this->defaultStart;
		$limit = !empty($_GET['Limit']) ? (int)$_GET['Limit'] : $this->defaultLimit;
		$feed = !empty($_GET['Feed']) ? $_GET['Feed'] : $this->defaultFeed;
		$this->quirks = !empty($_GET['quirks']) ? $_GET['quirks'] : '';
		
		// Force locale change for Vasabladet and Pohjalainen
		if ($feed == 'Vasabladet')
			$locale = 'sv_SE';
		else if ($feed == 'Pohjalainen')
			$locale = 'fi_FI';		
		
		// Sanity checks
		$this->invalidQuery++;
		foreach (Translatable::get_allowed_locales() as $allowedLocale) {
			if ($allowedLocale == $locale) {
				$this->currentLocale = $locale;
				i18n::set_locale($locale);
				Translatable::set_current_locale($locale);
				$this->invalidQuery--;
				break;
			}
		}
		
		$this->invalidQuery++;
		if ($sort_dir == 'ASC' || $sort_dir == 'DESC') {
			$this->currentSortDir = $sort_dir;
			$this->invalidQuery--;
		}
		
		$this->invalidQuery++;
		if ($format == 'xml' || $format == 'rss') {
			$this->currentFormat = $format;
			$this->invalidQuery--;
		}		
		
		$this->invalidQuery++;
		foreach ($this->allowedSortFields as $allowedSortField) {
			if ($allowedSortField == $sort_field) {
				$this->currentSortField = $sort_field;
				$this->invalidQuery--;
				break;
			}
		}
		
		$this->invalidQuery++;
		if ($start >= 0) {
			$this->currentStart = $start;
			$this->invalidQuery--;
		}
		
		$this->invalidQuery++;
		if ($limit >= 0) {
			if ($limit > $this->maxItemsPerQuery)
				$limit = $this->maxItemsPerQuery;
			if ($limit == 0)
				$limit = $this->defaultLimit;
			
			$this->currentLimit = $limit;
			$this->invalidQuery--;			
		}
		
		$this->invalidQuery++;
		if (in_array($feed, array('None', 'Vasabladet', 'Pohjalainen'))) {
			$this->currentFeed = $feed;
			$this->invalidQuery--;
			
			if (($feed == 'Vasabladet' || $feed == 'Pohjalainen') && empty($_GET['Limit']))
				$this->currentLimit = $this->maxItemsPerQuery;
		}
		
		// Prevent generation of warnings if xml parsing fails, catch exceptions instead
		libxml_use_internal_errors(true); 		
	}
	
	protected function hasQuirk($quirk) {
		if (strpos($this->quirks, $quirk) !== false)
			return true;
		return false;
	}
	
	protected function returnResponse($data, $format = 'xml') {
		$response = new SS_HTTPResponse();

		if ($format == 'xml') {
			$response->setBody($data->asXML());
			$response->addHeader('Content-Type', 'application/xml');
		}
		else if ($format == 'rss') {
			$response->setBody($data->asXML());
			$response->addHeader('Content-Type', 'application/rss+xml');
		}
		else 
			$response->setBody($data);
			
		return $response;
	}	
	
	static public function ArrayToXML($root_element_name, $ar) {
		$xml = new SimpleXMLElement("<?xml version=\"1.0\"?><{$root_element_name}></{$root_element_name}>"); 
		$f = create_function('$f,$c,$a',' 
					foreach($a as $k=>$v) { 
						if(is_array($v)) { 
							$ch=$c->addChild($k); 
							$f($f,$ch,$v); 
						} else { 
							$c->addChild($k,$v); 
						} 
					}'); 
		$f($f,$xml,$ar); 
		return $xml;
	}
	
	protected function EventToElement($event, $root) {
		$repeatDates = $event->RepeatDates();
		$repeatDates->sort('SortStartTime');
		
		$root->addChild('ID', $event->ID);
		$root->addChild('Start', $event->Start);
		$root->addChild('End', $event->End);
		$root->addChild('Link', $event->AbsoluteLink());
		$root->addChild('Title', self::safeXMLData($event->Title));
		$root->addChild('EventText', self::safeXMLData($event->EventText));
		$root->addChild('EventTextShort', self::safeXMLData($event->EventTextShort));
		$root->addChild('Homepage', self::safeXMLData($event->Homepage));
		$root->addChild('Place', self::safeXMLData($event->Place));
		$root->addChild('PostalAddress', self::safeXMLData($event->PostalAddress));
		$root->addChild('PostalCode', $event->PostalCode);
		$root->addChild('PostalOffice', self::safeXMLData($event->PostalOffice));
		$root->addChild('GoogleMAP', self::safeXMLData($event->GoogleMAP));
		$root->addChild('ShowGoogleMap', ($event->ShowGoogleMap == true ? 1 : 0));
		
		$root->addChild('PriceType', $event->PriceType);
		$root->addChild('PriceText', self::safeXMLData($event->PriceText));
			
		$categoriesElement = $root->addChild('Categories');
		if ($event->Categories()) {
			foreach ($event->Categories() as $category) {
				$childElement = $categoriesElement->addChild('Category');
				
				$childElement->addChild('ID', $category->ID);
				$childElement->addChild('Name', self::safeXMLData($category->getField('Name_' . $this->currentLocale)));
			}
		}
		
		$linksandfilesElement = $root->addChild('Attachments');
		if ($event->EventLinks()) {
			foreach ($event->EventLinks() as $link) {
				if (!$link->OnlySelectedLocales || count($link->Locales("Locale = '$this->currentLocale'"))) {
					$childElement = $linksandfilesElement->addChild('Link');
					$childElement->addChild('ID', $link->ID);
					$childElement->addChild('Title', self::safeXMLData($link->Name));
					$childElement->addChild('Link', $link->Link);
				}
			}			
		}
		
		if ($event->EventFiles()) {
			foreach ($event->EventFiles() as $file) {
				if (!$file->OnlySelectedLocales || count($file->Locales("Locale = '$this->currentLocale'"))) {
					$childElement = $linksandfilesElement->addChild('File');
					$childElement->addChild('ID', $file->ID);
					$childElement->addChild('Title', self::safeXMLData($file->Title));
					$childElement->addChild('Link', $file->getLink());				
				}
			}
		}
		
		if ($event->Municipal()->exists()) {
			$childElement = $root->addChild('Municipality', self::safeXMLData($event->Municipal()->getField('Name_' . $this->currentLocale)));
			$childElement->addAttribute('id', $event->MunicipalID);
		}
		
		$datesElement = $root->addChild('Dates');
		if ($repeatDates) {
			foreach ($repeatDates as $date) {
				$childElement = $datesElement->addChild('Date');
				
				$childElement->addChild('ID', $date->ID);
				$childElement->addChild('Start', date('Y-m-d H:i:s', strtotime($date->NiceStartTime)));
				$childElement->addChild('End', date('Y-m-d H:i:s', strtotime($date->NiceEndTime)));
			}
		}		
		
		$eventImagesElement = $root->addChild('Images');
		if ($event->EventImages()) {
			foreach ($event->EventImages() as $eventImage) {
				if ($eventImage->Image() && $eventImage->Image()->exists()) {
					$eventImageImg = $eventImage->Image();					
					
					$childElement = $eventImagesElement->addChild('Image');									
					$childElement->addChild('ID', $eventImageImg->ID);
					
					// Medium size
					$imageMedium = $eventImageImg->PaddedImage(200, 200);
					$childElement->addChild('URL', $imageMedium->AbsoluteLink());
					$childElement->addChild('Width', $imageMedium->getWidth());
					$childElement->addChild('Height', $imageMedium->getHeight());
					
					// Large size
					$imageLarge = $eventImageImg->PaddedImage(440, 440);
					
					$childElement->addChild('URL_L', $imageLarge->AbsoluteLink());
					$childElement->addChild('Width_L', $imageLarge->getWidth());
					$childElement->addChild('Height_L', $imageLarge->getHeight());
				}	
			}
		}
				
		// Association
		$associationElement = $root->addChild('Association');
		$association = $event->Association();
		if ($association) {
			$associationElement->addChild('Name', self::safeXMLData($association->getField('Name_' . $this->currentLocale)));
			$associationElement->addChild('PostalAddress', self::safeXMLData($association->PostalAddress));
			$associationElement->addChild('PostalCode', $association->PostalCode);
			$associationElement->addChild('PostalOffice', self::safeXMLData($association->PostalOffice));
			$associationElement->addChild('Phone', self::safeXMLData($association->Phone));
			$associationElement->addChild('Email', self::safeXMLData($association->Email));
			$associationElement->addChild('Homepage', self::safeXMLData($association->Homepage));
			
			$logo = $association->Logo();
			if ($logo && $logo->exists()) {
				$childElement = $associationElement->addChild('Logo');
				
				$resizedLogo = $logo->PaddedImage(142, 80);
				
				$childElement->addChild('ID', $logo->ID);
				$childElement->addChild('URL', $resizedLogo->AbsoluteLink());
				$childElement->addChild('Width', $resizedLogo->getWidth());
				$childElement->addChild('Height', $resizedLogo->getHeight());
			}			
		}
	}
		
	protected function outputInvalidQuery() {
		$xml = self::ArrayToXML('EventService', 
				array('Error' => array(
					'Code' => '1', 
					'Text' => 'Invalid query')
				));
		
		return $this->returnResponse($xml, $this->currentFormat);
	}
	
	public function search() {
		$data = $_GET;
		
		$searchParameters['Keywords'] = isset($data['Keywords']) ? $data['Keywords'] : '';
		$searchParameters['Categories'] = isset($data['Categories']) ? $data['Categories'] : '';
		$searchParameters['Municipalities'] = isset($data['Municipalities']) ? $data['Municipalities'] : '';
		$searchParameters['Period'] = isset($data['Period']) ? $data['Period'] : '';
		$searchParameters['StartDate'] = isset($data['StartDate']) ? $data['StartDate'] : '';
		$searchParameters['EndDate'] = isset($data['EndDate']) ? $data['EndDate'] : '';
		
		if ($this->invalidQuery)
			return $this->outputInvalidQuery();
			
		$where = array();
		$join = array();

		if (!empty($searchParameters['Keywords'])) {
			$keywordsString = Convert::raw2sql($searchParameters['Keywords']);
			$keywordsString = str_replace(',', ' ', $keywordsString);
			$keywordsString = str_replace(' ', '%', $keywordsString);

			$languages = Translatable::get_allowed_locales();

			$orWhere = array();

			if (count($languages)) {
				foreach ($languages as $lang) {
					$orWhere[] = 'Title_' . $lang . ' LIKE \'%' . $keywordsString . '%\'';
					$orWhere[] = 'EventText_' . $lang . ' LIKE \'%' . $keywordsString . '%\'';
					$orWhere[] = 'EventTextShort_' . $lang . ' LIKE \'%' . $keywordsString . '%\'';
					$orWhere[] = 'Place_' . $lang . ' LIKE \'%' . $keywordsString . '%\'';
					$orWhere[] = 'Association.Name_' . $lang . ' LIKE \'%' . $keywordsString . '%\'';
				}
			}				

			$where[] = '(' . implode(' OR ', $orWhere) . ')';
		} 
		if (!empty($searchParameters['Categories'])) {
			$ids = explode(',', $searchParameters['Categories']);
			$orWhere = array();
			
			if (count($ids)) {
				foreach ($ids as $id) {
					$orWhere[] = 'EventCategory.ID = ' . (int)$id;
				}
			}
			else {
				$orWhere[] = 'EventCategory.ID = ' . (int)$searchParameters['Categories'];
			}
			
			$where[] = '(' . implode(' OR ', $orWhere) . ')';
			$join[] = 'LEFT JOIN Event_Categories ON Event_Categories.EventID = Event.ID LEFT JOIN EventCategory ON EventCategory.ID = Event_Categories.EventCategoryID';			
		}
		if (!empty($searchParameters['Municipalities'])) {
			$filterMunicipalities = array();
			$inputMunicipalitites = explode(',', $searchParameters['Municipalities']);
			if (is_array($inputMunicipalitites)) {
				foreach ($inputMunicipalitites as $inputMunicipalityID) {
					if (DataObject::get_by_id('Municipal', (int)$inputMunicipalityID)) {
						$filterMunicipalities[] = (int)$inputMunicipalityID;
					}
				}
			
				$where[] = "Event.MunicipalID IN ('" . implode("','", $filterMunicipalities) . "')";
			}
		}
			
		$startDate = null;
		$endDate = null;		
		
		if (!empty($searchParameters['Period'])) { 
			$period = $searchParameters['Period'];
			
			if ($period == 'today') {
				$startDate = date('Y-m-d', time());
				$endDate = date('Y-m-d', time());	
			} 
			else if ($period == 'yesterday') {
				$startDate = date('Y-m-d', time()-60*60*24);
				$endDate = date('Y-m-d', time()-60*60*24);
			}
			else if ($period == 'tomorrow') {
				$startDate = date('Y-m-d', time()+60*60*24);
				$endDate = date('Y-m-d', time()+60*60*24);	
			}
			else if ($period == 'week') {
				$startDate = date('Y-m-d', time());
				$endDate = date('Y-m-d', time()+60*60*24*7);	
			}			
			else if ($period == 'two-weeks') {
				$startDate = date('Y-m-d', time());
				$endDate = date('Y-m-d', time()+60*60*24*7*2);	
			}						
			else if ($period == 'month') {
				$startDate = date('Y-m-d', time());
				$endDate = date('Y-m-d', time()+60*60*24*7*4);	
			}
			else if ($period == 'custom') {
				$startDate = (!empty($searchParameters['StartDate']) ? Convert::raw2sql($searchParameters['StartDate']) : null);
				$endDate = (!empty($searchParameters['EndDate']) ? Convert::raw2sql($searchParameters['EndDate']) : null);
			}
			
			$where[] = "(EventDate.Date >= '$startDate' AND EventDate.Date <= '$endDate')";
			$join[] = "LEFT JOIN EventDate ON (EventDate.EventID = Event.ID AND EventDate.Date >= '$startDate' AND EventDate.Date <= '$endDate')";
		} else {
			$startDate = date('Y-m-d', time());
			$where[] = "EventDate.Date >= '$startDate'";
			$join[] = "LEFT JOIN EventDate ON (EventDate.EventID = Event.ID AND EventDate.Date >= '$startDate')";
		}

		// Combine search parameters and get the result
		$where[] = "Event.Status = 'Accepted'";
		
		// Filter Vasabladet only
		if ($this->currentFeed == 'Vasabladet')
			$where[] = "Event.Vasabladet_PublishTo = 1";
		// Filter Pohjalainen only
		else if ($this->currentFeed == 'Pohjalainen')
			$where[] = "Event.Pohjalainen_PublishTo = 1";
		
		// Only if association is active
		$join[] = 'LEFT JOIN Association ON Association.ID = Event.AssociationID';
		$where[] = "Association.Status = 'Active'";
		
		$countQuery = new SQLQuery('COUNT(DISTINCT Event.ID)', array_merge(array('Event'), $join), implode(' AND ', $where), '', '');
		$totalItems = $countQuery->execute()->value();
		
		$sortPart = $this->buildSortPart($startDate);
				
		$events = DataObject::get('Event', implode(' AND ', $where), $sortPart, implode(' ', $join), "{$this->currentStart}, {$this->currentLimit}");
				
		if ($this->currentFormat == 'xml') {
			$xml = new SimpleXMLElement("<?xml version=\"1.0\"?><EventService></EventService>");
			$xml->addChild('TotalItems', $totalItems);
			$xml->addChild('Items', $events ? $events->Count() : 0);
			$xml->addChild('Start', $this->currentStart);
			$xml->addChild('Limit', $this->currentLimit);	
			$xml->addChild('Locale', $this->currentLocale);
			$xml->addChild('SortField', $this->currentSortField);
			$xml->addChild('SortDir', $this->currentSortDir);
			$xml->addChild('RootLink', Director::absoluteURL(Director::baseURL()));
			$xml->addChild('ServiceLink', Director::absoluteURL(''));
			$xml->addChild('EventsLink', Director::absoluteURL(singleton('EventPage_Controller')->ControllerLink()));
			$eventsElement = $xml->addChild('Events');
		
			if ($events) {
				foreach ($events as $event) {
					$childElement = $eventsElement->addChild('Event');
					$this->EventToElement($event, $childElement);
				}
			}		
		}
		else if ($this->currentFormat == 'rss') {
			$url = Controller::curr()->getRequest()->getURL();
			$urlParams = array();
			if (count(Controller::curr()->getRequest()->getVars()) > 1) {
				foreach (Controller::curr()->getRequest()->getVars() as $urlParamKey => $urlParamValue) {
					if ($urlParamKey != 'url')
						$urlParams[] .= $urlParamKey . '=' . rawurlencode($urlParamValue);
				}
				if (count($urlParams)) 
					$url .= '?' . implode('&', $urlParams);				
			}
						
			$xml = new SimpleXMLExtended("<?xml version=\"1.0\"?><rss version=\"2.0\" xmlns:atom=\"http://www.w3.org/2005/Atom\" xmlns:event=\"http://events.osterbotten.fi/event-dtd\"></rss>");
			$channel = $xml->addChild('channel');
			$channel->addChild('language', i18n::get_lang_from_locale($this->currentLocale));
			$channel->addChild('title', 'Tapahtumat | Evenemang | Events');
			$channel->addChild('description', 'Hakutulokset | Sökresultat | Search results');
			$channel->addChild('link', Director::absoluteURL($url));	
			$atomLink = $channel->addChild('link', null, 'http://www.w3.org/2005/Atom');
			$atomLink->addAttribute('href', Director::absoluteURL($url));
			$atomLink->addAttribute('rel', 'self');
			$atomLink->addAttribute('type', 'application/rss+xml');		
			
			if ($events) {
				$tmpEvents = $events;
				
				if ($this->hasQuirk('pubDateAsClosestDate')) {
					$tmpEvents->sort('ClosestDate', 'DESC');
					$channel->addChild('pubDate', $tmpEvents->First()->getClosestDate()->Rfc822());
				}
				else {
					$tmpEvents->sort('PublishedDate', 'DESC');
					$channel->addChild('pubDate', $tmpEvents->First()->dbObject('PublishedDate')->Rfc822());
				}				
				
				// Makes validator happier if "item" goes last
				foreach ($events as $event) {
					$childElement = $channel->addChild('item');
					$this->EventToRssElement($event, $childElement);
				}				
			}
		}

		return $this->returnResponse($xml, $this->currentFormat);	
	}
	
	public function event() {
		$data = $_GET;
		
		if ($this->invalidQuery)
			return $this->outputInvalidQuery();
		
		$eventID = !empty($data['ID']) ? (int)$data['ID'] : 0;
		
		$event = DataObject::get_by_id('Event', $eventID);
		
		if (!$event || ($event->Association() && $event->Association()->Status != 'Active')) {
			return $this->outputInvalidQuery();
		}
		
		if ($this->currentFormat == 'xml') {
			$xml = new SimpleXMLElement("<?xml version=\"1.0\"?><EventService></EventService>");
			$xml->addChild('Locale', $this->currentLocale);
			$childElement = $xml->addChild('Event');
			$this->EventToElement($event, $childElement);		
		}
		else if ($this->currentFormat == 'rss') {
			$url = Controller::curr()->getRequest()->getURL();
			$urlParams = array();
			if (count(Controller::curr()->getRequest()->getVars()) > 1) {
				foreach (Controller::curr()->getRequest()->getVars() as $urlParamKey => $urlParamValue) {
					if ($urlParamKey != 'url')
						$urlParams[] .= $urlParamKey . '=' . rawurlencode($urlParamValue);
				}
				if (count($urlParams)) 
					$url .= '?' . implode('&', $urlParams);				
			}
			
			
			$xml = new SimpleXMLExtended("<?xml version=\"1.0\"?><rss version=\"2.0\" xmlns:atom=\"http://www.w3.org/2005/Atom\" xmlns:event=\"http://events.osterbotten.fi/event-dtd\"></rss>");
			$channel = $xml->addChild('channel');
			$channel->addChild('language', i18n::get_lang_from_locale($this->currentLocale));
			$channel->addChild('title', 'Tapahtuma | Evenemang | Event');
			$channel->addChild('description', 'Tapahtuma | Evenemang | Event');
			$channel->addChild('link', Director::absoluteURL($url));	
			$channel->addChild('pubDate', $event->dbObject('PublishedDate')->Rfc822());
			$atomLink = $channel->addChild('link', null, 'http://www.w3.org/2005/Atom');
			$atomLink->addAttribute('href', Director::absoluteURL($url));
			$atomLink->addAttribute('rel', 'self');
			$atomLink->addAttribute('type', 'application/rss+xml');
				
			$childElement = $channel->addChild('item');
			$this->EventToRssElement($event, $childElement);
		}		
		
		return $this->returnResponse($xml, $this->currentFormat);
	}
		
	public function rss() {
		$data = $_GET;		
		
		if ($this->invalidQuery)
			return $this->outputInvalidQuery();
		
		$locale = 'en_US';
		
		if (isset($data['Locale'])) {
			if (in_array($data['Locale'], Translatable::get_allowed_locales()))
				$locale = $data['Locale'];
		}
		
		i18n::set_locale($locale);
			
		$startDate = date('Y-m-d', time());
		$where = "Event.Status = 'Accepted' AND Association.Status = 'Active' AND EventDate.Date >= '$startDate'";
		$join = "LEFT JOIN EventDate ON (EventDate.EventID = Event.ID AND EventDate.Date >= '$startDate') LEFT JOIN Association ON Association.ID = Event.AssociationID";

		// Filter Vasabladet only
		if ($this->currentFeed == 'Vasabladet')
			$where .= " AND Event.Vasabladet_PublishTo = 1";
		// Filter Pohjalainen only
		else if ($this->currentFeed == 'Pohjalainen')
			$where .= " AND Event.Pohjalainen_PublishTo = 1";		
		
		$countQuery = new SQLQuery('COUNT(DISTINCT Event.ID)', array('Event', $join), $where);
		$totalItems = $countQuery->execute()->value();		
		
		$sortPart = $this->buildSortPart($startDate);

		$events = DataObject::get('Event', $where, $sortPart, $join, "{$this->currentStart}, {$this->currentLimit}");
						
		$xml = new SimpleXMLExtended("<?xml version=\"1.0\"?><rss version=\"2.0\" xmlns:atom=\"http://www.w3.org/2005/Atom\" xmlns:event=\"http://events.osterbotten.fi/event-dtd\"></rss>");
		$channel = $xml->addChild('channel');
		$channel->addChild('language', i18n::get_lang_from_locale($this->currentLocale));
		$channel->addChild('title', 'Tapahtumat | Evenemang | Events');
		$channel->addChild('description', 'Kaikki tapahtumat | Alla evenemang | All events');
		$channel->addChild('link', Director::absoluteURL('rss'));	
		$atomLink = $channel->addChild('link', null, 'http://www.w3.org/2005/Atom');
		$atomLink->addAttribute('href', Director::absoluteURL('rss'));
		$atomLink->addAttribute('rel', 'self');
		$atomLink->addAttribute('type', 'application/rss+xml');
				
		if ($events) {
			$tmpEvents = $events;
			
			if ($this->hasQuirk('pubDateAsClosestDate')) {
				$tmpEvents->sort('ClosestDate', 'DESC');
				$channel->addChild('pubDate', $tmpEvents->First()->getClosestDate()->Rfc822());
			}
			else {
				$tmpEvents->sort('PublishedDate', 'DESC');
				$channel->addChild('pubDate', $tmpEvents->First()->dbObject('PublishedDate')->Rfc822());
			}			
			
			// Makes validator happier if "item" goes last
			foreach ($events as $event) {
				$childElement = $channel->addChild('item');
				$this->EventToRssElement($event, $childElement);
			}
		}		
		
		return $this->returnResponse($xml, 'rss');
	}
	
	protected function EventToRssElement($event, $root) {
		$eventNamespace = 'http://events.osterbotten.fi/event-dtd';
		
		$repeatDates = $event->RepeatDates();
		$repeatDates->sort('SortStartTime');
		
		// Find the closest date
		$closestDate = $event->ClosestDate;
		
		$desc = '';
		if (!$this->hasQuirk('hidePeriodInDescription')) {
			if ($event->Start == $event->End)
				$desc .= date('d.m.Y H:i', strtotime($event->Start));
			else 
				$desc .= date('d.m.Y H:i', strtotime($event->Start)) . ' - ' . date('d.m.Y H:i', strtotime($event->End));
		}
		if (!$this->hasQuirk('hideImagesInDescription')) {
			if ($event->EventImages()) {
				$desc .= '<p>';
				foreach ($event->EventImages() as $eventImage) {
					$image = $eventImage->Image();
					if ($image && $image->exists()) {
						$img_alt = '';
						$img_url = $image->PaddedImage(200, 200)->AbsoluteLink();
						$img_width = $image->PaddedImage(200, 200)->getWidth();
						$img_height = $image->PaddedImage(200, 200)->getHeight();

						$desc .= "<img src='$img_url' width='$img_width' height='$img_height' alt='$img_alt'/>";
					}
				}
				$desc .= '</p>';
			}			
		}
		if (!$this->hasQuirk('hideShortTextInDescription'))
			$desc .= '<p>' . $event->EventTextShort . '</p>';
		if (!$this->hasQuirk('hideTextInDescription'))
			$desc .= '<p>' . $event->EventText . '</p>';
				
		$guid = $root->addChild('guid', 'EventCalendar_' . $event->ID);
		$guid->addAttribute('isPermaLink', 'false');
		$root->addChild('link', $event->AbsoluteLink());
		$root->addChild('title', self::safeXMLData($event->Title));
		
		// Quirks mode to set pubdate as closestDate
		if ($this->hasQuirk('pubDateAsClosestDate')) {
			$root->addChild('pubDate', $closestDate->Rfc822());
			$root->addChild('closestDate', $closestDate->Rfc822(), $eventNamespace);
		}
		else {
			$root->addChild('pubDate', $event->dbObject('PublishedDate')->Rfc822());
			$root->addChild('closestDate', $closestDate->Rfc822(), $eventNamespace);
		}
		$descNode = $root->addChild('description');
		$descNode->addCData($desc);
		
		if ($event->Categories()) {
			foreach ($event->Categories() as $category) {
				$childElement = $root->addChild('category', $category->getField('Name_' . $this->currentLocale));
			}
		}		
		
		// Add some more info
		if (!$this->hasQuirk('simplifyRSS')) {
			$root->addChild('dateperiod', self::safeXMLData($event->DatePeriodNice), $eventNamespace);
			$root->addChild('address', self::safeXMLData($event->GoogleMAP), $eventNamespace);
			$root->addChild('location', self::safeXMLData($event->Place), $eventNamespace);
			$root->addChild('organizer', self::safeXMLData($event->Organizer()->Name), $eventNamespace);
		}
		
		// Vasabladet feed
		if ($this->currentFeed == 'Vasabladet') {			
			if ($event->Vasabladet_PublishTo == true) {
				$root->addChild('vasabladet_title', self::safeXMLData($event->Title), $eventNamespace);
				$root->addChild('vasabladet_municipality_id', $event->Vasabladet_Municipality, $eventNamespace);
				$root->addChild('vasabladet_category_id', $event->Vasabladet_Category, $eventNamespace);
				$root->addChild('vasabladet_subcategory_id', $event->Vasabladet_SubCategory, $eventNamespace);
				$root->addChild('vasabladet_short_description', self::safeXMLData($event->Vasabladet_ShortText), $eventNamespace);
				if ($event->Vasabladet_AdditionalInfo) {
					$additionalInfo = $root->addChild('vasabladet_additional_information', '', $eventNamespace);
					$additionalInfo->addChild('vasabladet_text', self::safeXMLData($event->Vasabladet_Text), $eventNamespace);
					$additionalInfo->addChild('vasabladet_organizer', self::safeXMLData($event->Vasabladet_Organizer), $eventNamespace);
					$additionalInfo->addChild('vasabladet_url', self::safeXMLData($event->Vasabladet_URL), $eventNamespace);
					$additionalInfo->addChild('vasabladet_address', self::safeXMLData($event->Vasabladet_Address), $eventNamespace);
				}
			}
		}
		// Pohjalainen feed
		else if ($this->currentFeed == 'Pohjalainen') {			
			if ($event->Pohjalainen_PublishTo == true) {
				$eventAssociation = $event->Association();
				
				$root->addChild('pohjalainen_organizer_name', self::safeXMLData($eventAssociation->Name), $eventNamespace);
				$root->addChild('pohjalainen_organizer_address', self::safeXMLData($eventAssociation->PostalAddress), $eventNamespace);
				$root->addChild('pohjalainen_organizer_municipality', self::safeXMLData($eventAssociation->Municipal()->Name), $eventNamespace);
				$root->addChild('pohjalainen_organizer_homepage', self::safeXMLData($eventAssociation->Homepage), $eventNamespace);
				$root->addChild('pohjalainen_organizer_email', self::safeXMLData($eventAssociation->Email), $eventNamespace);
				
				$root->addChild('pohjalainen_event_title', self::safeXMLData($event->Pohjalainen_Title), $eventNamespace);
				$root->addChild('pohjalainen_event_ilkka', ($event->Pohjalainen_PostInIlkka ? '1' : '0'), $eventNamespace);
				$root->addChild('pohjalainen_event_category', (int)$event->Pohjalainen_Category, $eventNamespace);
				$root->addChild('pohjalainen_event_shorttext', self::safeXMLData($event->Pohjalainen_ShortText), $eventNamespace);
				if ($event->Pohjalainen_HasText) 
					$root->addChild('pohjalainen_event_text', self::safeXMLData($event->Pohjalainen_Text), $eventNamespace);
				$root->addChild('pohjalainen_event_homepage', self::safeXMLData($event->Pohjalainen_URL), $eventNamespace);
				$root->addChild('pohjalainen_event_municipality', (int)$event->Pohjalainen_MunicipalityZIP, $eventNamespace);
				$root->addChild('pohjalainen_event_place', self::safeXMLData($event->Pohjalainen_Place), $eventNamespace);
				$root->addChild('pohjalainen_event_address', self::safeXMLData($event->Pohjalainen_Address), $eventNamespace);
			}
		}		
		
		// Add event dates too
		if (!$this->hasQuirk('simplifyRSS')) {
			$datesElement = $root->addChild('dates', null, $eventNamespace);
			$datesExtElement = $root->addChild('dates-ext', null, $eventNamespace);
			if ($repeatDates) {
				foreach ($repeatDates as $date) {
					$datesElement->addChild('date', $date->Rfc822(), $eventNamespace);
				}

				foreach ($repeatDates as $date) {
					$dateExt = $datesExtElement->addChild('date', null, $eventNamespace);
					$dateExt->addChild('start', $date->Rfc822(), $eventNamespace);
					if ($date->HasEndTime) {
						$dateExt->addChild('end', $date->Rfc822(false), $eventNamespace);	
					}
				}
			}		
		}
	}		
	
	public function allEvents() {
		$data = $_GET;		
		
		if ($this->invalidQuery)
			return $this->outputInvalidQuery();
		
		$queryMunicipalities = isset($data['Municipalities']) ? $data['Municipalities'] : '';
		$whereMunicipalities = '';
		
		if (!empty($queryMunicipalities)) {
			$filterMunicipalities = array();
			$inputMunicipalitites = explode(',', $queryMunicipalities);
			if (is_array($inputMunicipalitites)) {
				foreach ($inputMunicipalitites as $inputMunicipalityID) {
					if (DataObject::get_by_id('Municipal', (int)$inputMunicipalityID)) {
						$filterMunicipalities[] = (int)$inputMunicipalityID;
					}
				}
			
				$whereMunicipalities = " AND Event.MunicipalID IN ('" . implode("','", $filterMunicipalities) . "')";
			}
		}		
			
		$startDate = date('Y-m-d', time());
		$where = "Event.Status = 'Accepted' AND Association.Status = 'Active' AND EventDate.Date >= '$startDate'" . $whereMunicipalities;
		$join = "LEFT JOIN EventDate ON (EventDate.EventID = Event.ID AND EventDate.Date >= '$startDate') LEFT JOIN Association ON Association.ID = Event.AssociationID";
		
		// Filter Vasabladet only
		if ($this->currentFeed == 'Vasabladet')
			$where .= " AND Event.Vasabladet_PublishTo = 1";
		// Filter Pohjalainen only
		else if ($this->currentFeed == 'Pohjalainen')
			$where .= " AND Event.Pohjalainen_PublishTo = 1";		
		
		$countQuery = new SQLQuery('COUNT(DISTINCT Event.ID)', array('Event', $join), $where);
		$totalItems = $countQuery->execute()->value();		
		
		$sortPart = $this->buildSortPart($startDate);

		$events = DataObject::get('Event', $where, $sortPart, $join, "{$this->currentStart}, {$this->currentLimit}");
						
		if ($this->currentFormat == 'xml') {
			$xml = new SimpleXMLElement("<?xml version=\"1.0\"?><EventService></EventService>");
			$xml->addChild('TotalItems', $totalItems);
			$xml->addChild('Items', $events ? $events->Count() : 0);
			$xml->addChild('Start', $this->currentStart);
			$xml->addChild('Limit', $this->currentLimit);
			$xml->addChild('Locale', $this->currentLocale);
			$xml->addChild('SortField', $this->currentSortField);
			$xml->addChild('SortDir', $this->currentSortDir);
			$eventsElement = $xml->addChild('Events');
		
			if ($events) {
				foreach ($events as $event) {
					$childElement = $eventsElement->addChild('Event');
					$this->EventToElement($event, $childElement);
				}
			}		
		}
		else if ($this->currentFormat == 'rss') {
			$url = Controller::curr()->getRequest()->getURL();
			$urlParams = array();
			if (count(Controller::curr()->getRequest()->getVars()) > 1) {
				foreach (Controller::curr()->getRequest()->getVars() as $urlParamKey => $urlParamValue) {
					if ($urlParamKey != 'url')
						$urlParams[] .= $urlParamKey . '=' . rawurlencode($urlParamValue);
				}
				if (count($urlParams)) 
					$url .= '?' . implode('&', $urlParams);				
			}
			
			$xml = new SimpleXMLExtended("<?xml version=\"1.0\"?><rss version=\"2.0\" xmlns:atom=\"http://www.w3.org/2005/Atom\" xmlns:event=\"http://events.osterbotten.fi/event-dtd\"></rss>");
			$channel = $xml->addChild('channel');
			$channel->addChild('language', i18n::get_lang_from_locale($this->currentLocale));
			$channel->addChild('title', 'Tapahtumat | Evenemang | Events');
			$channel->addChild('description', 'Kaikki tapahtumat | Alla evenemang | All events');
			$channel->addChild('link', Director::absoluteURL($url));	
			$atomLink = $channel->addChild('link', null, 'http://www.w3.org/2005/Atom');
			$atomLink->addAttribute('href', Director::absoluteURL($url));
			$atomLink->addAttribute('rel', 'self');
			$atomLink->addAttribute('type', 'application/rss+xml');		
			
			if ($events) {
				$tmpEvents = $events;
				
				if ($this->hasQuirk('pubDateAsClosestDate')) {
					$tmpEvents->sort('ClosestDate', 'DESC');
					$channel->addChild('pubDate', $tmpEvents->First()->getClosestDate()->Rfc822());
				}
				else {
					$tmpEvents->sort('PublishedDate', 'DESC');
					$channel->addChild('pubDate', $tmpEvents->First()->dbObject('PublishedDate')->Rfc822());
				}				
				
				// Makes validator happier if "item" goes last
				foreach ($events as $event) {
					$childElement = $channel->addChild('item');
					$this->EventToRssElement($event, $childElement);
				}				
			}			
		}

		return $this->returnResponse($xml, $this->currentFormat);
	}
	
	public function categories() {
		$data = $_GET;		
		
		if ($this->invalidQuery)
			return $this->outputInvalidQuery();			
		
		$queryMunicipalities = isset($data['Municipalities']) ? $data['Municipalities'] : '';
		$whereMunicipalities = '';
		
		if (!empty($queryMunicipalities)) {
			$filterMunicipalities = array();
			$inputMunicipalitites = explode(',', $queryMunicipalities);
			if (is_array($inputMunicipalitites)) {
				foreach ($inputMunicipalitites as $inputMunicipalityID) {
					if (DataObject::get_by_id('Municipal', (int)$inputMunicipalityID)) {
						$filterMunicipalities[] = (int)$inputMunicipalityID;
					}
				}
			
				$whereMunicipalities = " AND Event.MunicipalID IN ('" . implode("','", $filterMunicipalities) . "')";
			}
		}
		
		$categories = DataObject::get('EventCategory', 'Inactive = 0', 'AlwaysLast ASC, Name_' . $this->currentLocale . ' ASC');
						
		$xml = new SimpleXMLElement("<?xml version=\"1.0\"?><EventService></EventService>");
		$xml->addChild('Items', $categories ? $categories->Count() : 0);
		$xml->addChild('Locale', $this->currentLocale);
		$categoriesElement = $xml->addChild('Categories');
		
		if ($categories) {
			$startDate = date('Y-m-d', time());
			
			foreach ($categories as $category) {
				$childElement = $categoriesElement->addChild('Category');
				
				$childElement->addChild('ID', $category->ID);
				$childElement->addChild('Name', $category->getField('Name_' . $this->currentLocale));
				
				$where = "Event.Status = 'Accepted' AND Association.Status = 'Active' AND EventDate.Date >= '$startDate' AND EventCategory.ID = {$category->ID}" . $whereMunicipalities;
				$joinDate = "LEFT JOIN EventDate ON (EventDate.EventID = Event.ID AND EventDate.Date >= '$startDate')";
				$joinCategories = 'LEFT JOIN Event_Categories ON Event_Categories.EventID = Event.ID LEFT JOIN EventCategory ON EventCategory.ID = Event_Categories.EventCategoryID';
				$joinAssociation = 'LEFT JOIN Association ON Association.ID = Event.AssociationID';
						
				$countQuery = new SQLQuery('COUNT(DISTINCT Event.ID)', array('Event', $joinDate, $joinCategories, $joinAssociation), $where);
				$totalEvents = $countQuery->execute()->value();
				
				$childElement->addChild('Events', $totalEvents);
			}
		}		

		return $this->returnResponse($xml, $this->currentFormat);		
	}
	
	public function municipalities() {
		$data = $_GET;		
		
		if ($this->invalidQuery)
			return $this->outputInvalidQuery();	
		
		$municipalities = DataObject::get('Municipal', '', 'AlwaysLast ASC, Name_' . $this->currentLocale . ' ASC');
						
		$xml = new SimpleXMLElement("<?xml version=\"1.0\"?><EventService></EventService>");
		$xml->addChild('Items', $municipalities ? $municipalities->Count() : 0);
		$xml->addChild('Locale', $this->currentLocale);
		$municipalitiesElement = $xml->addChild('Municipalities');
		
		if ($municipalities) {
			$startDate = date('Y-m-d', time());
			
			foreach ($municipalities as $municipality) {
				$childElement = $municipalitiesElement->addChild('Municipality');
				
				$childElement->addChild('ID', $municipality->ID);
				$childElement->addChild('Name', $municipality->getField('Name_' . $this->currentLocale));
				
				$where = "Event.Status = 'Accepted' AND Association.Status = 'Active' AND EventDate.Date >= '$startDate' AND Event.MunicipalID = {$municipality->ID}";
				$joinDate = "LEFT JOIN EventDate ON (EventDate.EventID = Event.ID AND EventDate.Date >= '$startDate')";	
				$joinAssociation = 'LEFT JOIN Association ON Association.ID = Event.AssociationID';
						
				$countQuery = new SQLQuery('COUNT(DISTINCT Event.ID)', array('Event', $joinDate, $joinAssociation), $where);
				$totalEvents = $countQuery->execute()->value();							
				
				$childElement->addChild('Events', $totalEvents);
			}
		}		

		return $this->returnResponse($xml, $this->currentFormat);		
	}	
		
	public function index() {
		return $this->outputInvalidQuery();
	}
	
	protected function buildSortPart($startDate) {
		$sortPart = '';
		$dateDefaultSort = "(SELECT MIN(EventDate.Date) AS EventMinDate FROM EventDate WHERE (EventDate.Date >= '$startDate' AND EventDate.EventID = Event.ID)) ASC, ";
		
		if ($this->currentSortField == 'Date' && $this->currentSortDir == 'ASC')
			$sortPart = "(SELECT MIN(EventDate.Date) AS EventMinDate FROM EventDate WHERE (EventDate.Date >= '$startDate' AND EventDate.EventID = Event.ID)) " . $this->currentSortDir;
		else if ($this->currentSortField == 'Date' && $this->currentSortDir == 'DESC')
			$sortPart = "(SELECT MAX(EventDate.Date) AS EventMaxDate FROM EventDate WHERE (EventDate.Date >= '$startDate' AND EventDate.EventID = Event.ID)) " . $this->currentSortDir;	
		
		if ($this->currentSortField == 'Period')
			$sortPart = $dateDefaultSort . "(SELECT CONCAT_WS(' - ', MIN(EventDate.Date), MAX(EventDate.Date)) AS EventPeriod FROM EventDate WHERE EventDate.EventID = Event.ID) " . $this->currentSortDir;
		
		if ($this->currentSortField == 'Title') {
			//$sortPart = $dateDefaultSort . "Event.Title_" . $this->currentLocale . ' ' . $this->currentSortDir;
			$sortPart = $dateDefaultSort . "(SELECT CASE (SELECT CASE Count(Locale) WHEN 0 THEN (SELECT Locale FROM CalendarLocale WHERE CalendarLocale.ID = (SELECT CalendarLocaleID FROM Event_Languages WHERE Event_Languages.EventID = Event.ID LIMIT 1)) ELSE Locale END AS Locale FROM CalendarLocale WHERE (CalendarLocale.Locale = '{$this->currentLocale}' AND CalendarLocale.ID IN (SELECT CalendarLocaleID FROM Event_Languages WHERE Event_Languages.EventID = Event.ID))) WHEN 'sv_SE' THEN Title_sv_SE WHEN 'fi_FI' THEN Title_fi_FI WHEN 'en_US' THEN Title_en_US ELSE Title END FROM Event TmpEvent WHERE TmpEvent.ID = Event.ID) " . $this->currentSortDir;
		}
			
		if ($this->currentSortField == 'Place') {
			//$sortPart = $dateDefaultSort . "Event.Place_" . $this->currentLocale . ' ' . $this->currentSortDir;		
			$sortPart = $dateDefaultSort . "(SELECT CASE (SELECT CASE Count(Locale) WHEN 0 THEN (SELECT Locale FROM CalendarLocale WHERE CalendarLocale.ID = (SELECT CalendarLocaleID FROM Event_Languages WHERE Event_Languages.EventID = Event.ID LIMIT 1)) ELSE Locale END AS Locale FROM CalendarLocale WHERE (CalendarLocale.Locale = '{$this->currentLocale}' AND CalendarLocale.ID IN (SELECT CalendarLocaleID FROM Event_Languages WHERE Event_Languages.EventID = Event.ID))) WHEN 'sv_SE' THEN Place_sv_SE WHEN 'fi_FI' THEN Place_fi_FI WHEN 'en_US' THEN Place_en_US ELSE Place END FROM Event TmpEvent WHERE TmpEvent.ID = Event.ID) " . $this->currentSortDir;
		}
			
		if ($this->currentSortField == 'Municipality')
			$sortPart = $dateDefaultSort . "(SELECT Municipal.NAME_" . $this->currentLocale . " FROM Municipal WHERE Municipal.ID = Event.MunicipalID) " . $this->currentSortDir;
		
		if ($this->currentSortField == 'Categories') 
			$sortPart = $dateDefaultSort . "(SELECT GROUP_CONCAT(Name_$this->currentLocale) FROM EventCategory WHERE EventCategory.ID IN (SELECT EventCategoryID FROM Event_Categories WHERE Event_Categories.EventID = Event.ID)) " . $this->currentSortDir;
	
		// Getting a locale for an event
		// (SELECT Locale FROM CalendarLocale WHERE CalendarLocale.ID IN (SELECT CalendarLocaleID FROM Event_Languages WHERE Event_Languages.EventID = 192))

		// Is the current locale in the locale list?
		// (SELECT Locale, CASE Locale WHEN 'en_US' THEN 'Yes' ELSE 'No' END AS InLocaleList FROM CalendarLocale WHERE CalendarLocale.ID IN (SELECT CalendarLocaleID FROM Event_Languages WHERE Event_Languages.EventID = 192))
		
		//SELECT Locale FROM CalendarLocale WHERE (CalendarLocale.Locale = 'fi_FI' AND CalendarLocale.ID IN (SELECT CalendarLocaleID FROM Event_Languages WHERE Event_Languages.EventID = 192))
		
		//SELECT CASE Count(Locale) WHEN 0 THEN 'Not in list' ELSE Locale END AS Locale FROM CalendarLocale WHERE (CalendarLocale.Locale = 'fi_FI' AND CalendarLocale.ID IN (SELECT CalendarLocaleID FROM Event_Languages WHERE Event_Languages.EventID = 192))
		
		// Rediculously complex query for getting the right locale
		// SELECT CASE Count(Locale) WHEN 0 THEN (SELECT Locale FROM CalendarLocale WHERE CalendarLocale.ID = (SELECT CalendarLocaleID FROM Event_Languages WHERE Event_Languages.EventID = 192 LIMIT 1)) ELSE Locale END AS Locale FROM CalendarLocale WHERE (CalendarLocale.Locale = 'fi_FI' AND CalendarLocale.ID IN (SELECT CalendarLocaleID FROM Event_Languages WHERE Event_Languages.EventID = 192))		

		// SELECT CASE (SELECT CASE Count(Locale) WHEN 0 THEN (SELECT Locale FROM CalendarLocale WHERE CalendarLocale.ID = (SELECT CalendarLocaleID FROM Event_Languages WHERE Event_Languages.EventID = 192 LIMIT 1)) ELSE Locale END AS Locale FROM CalendarLocale WHERE (CalendarLocale.Locale = 'sv_SE' AND CalendarLocale.ID IN (SELECT CalendarLocaleID FROM Event_Languages WHERE Event_Languages.EventID = 192))) WHEN 'sv_SE' THEN Title_sv_SE WHEN 'fi_FI' THEN Title_fi_FI WHEN 'en_US' THEN Title_en_US ELSE Title END FROM Event WHERE Event.ID = 192
		
		return $sortPart;
	}
	
	static public function safeXMLData($input) {		
		$input = mb_convert_encoding($input, 'UTF-8', mb_detect_encoding($input));
		$input = self::stripInvalidXml($input);		
		return htmlspecialchars($input, ENT_QUOTES, 'UTF-8');
	}
	
	/**
	 * Removes invalid XML
	 *
	 * @access public
	 * @param string $value
	 * @return string
	 */
	static protected function stripInvalidXml($value)
	{
		$ret = "";
		$current;
		if (empty($value)) 
		{
			return $ret;
		}

		$length = strlen($value);
		for ($i=0; $i < $length; $i++)
		{
			$current = ord($value{$i});
			if (($current == 0x9) ||
				($current == 0xA) ||
				($current == 0xD) ||
				(($current >= 0x20) && ($current <= 0xD7FF)) ||
				(($current >= 0xE000) && ($current <= 0xFFFD)) ||
				(($current >= 0x10000) && ($current <= 0x10FFFF)))
			{
				$ret .= chr($current);
			}
			else
			{
				$ret .= "";
			}
		}
		return $ret;
	}
}

?>
