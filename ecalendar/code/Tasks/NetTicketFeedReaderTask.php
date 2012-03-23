<?php

/*
 * NetTicket feeds
 * 
 * http://www.netticket.fi/osterbotten_feed.php
 * http://www.wasateater.fi/osterbotten_feed.php
 * http://market.vaasa.fi/osterbotten_feed.php
 * 
 */

class NetTicketFeedReaderTask extends DailyTask {
	function process() {
		Folder::findOrMake('NetTicket_Images');
		
		$this->parseFeed('http://www.netticket.fi/osterbotten_feed.php');
		$this->parseFeed('http://www.wasateater.fi/osterbotten_feed.php');
		$this->parseFeed('http://market.vaasa.fi/osterbotten_feed.php');
	}
	
	protected function parseFeed($feedUrl) {
		$remoteReader = new RemoteDataService(3600, $feedUrl);
		
		try {		
			$request = $remoteReader->request();
		
			$rss = new SimpleXMLElement($request->getBody());
			$channel = $rss->channel;
			$items = $rss->channel->item;
			
			echo "<br/>Parsing feed: $feedUrl<br/>";
			
			if ($items->count() > 0) {
				foreach ($items as $item) {
					$eventData = $item->children('events', true);
										
					// First parse languages			
					$languages = explode(',', $eventData->languages);
					if (!is_array($languages) && strlen($languages))
						$languages = array($languages);
					else if (!is_array($languages) && !strlen($languages)) {
						// Ignore this event if languages cant be parsed
						//echo 'Ignoring event with guid: ' . $item->guid . '<br/>';
						continue;
					}
					
					$event = DataObject::get_one('Event', "FeedGUID = '" . Convert::raw2sql($item->guid) . "'");
					if (!$event) {
						$event = new Event();
						$event->Status = 'Draft';
						$event->PublishedDate = date('Y-m-d H:i:s', strtotime((string)$item->pubDate[0]));
						$event->FeedGUID = (string)$item->guid[0];
					}
					$systemAdmin = eCalendarExtension::FindSystemAdministrator();
					
					$event->Homepage = trim(sprintf('%s', (string)$item->link[0]));
					$event->MunicipalID = Municipal::getIDFromName((string)$eventData->municipality[0], true);
					$event->CreatorID = $systemAdmin ? $systemAdmin->ID : 0;
					$event->OrganizerID = $systemAdmin ? $systemAdmin->ID : 0;
					$event->AssociationID = Association::getIDFromFeedIdentifier(Convert::raw2sql($item->author));
					$event->write();
					
					// Add the category
					$event->Categories()->add(EventCategory::getIDFromName($item->category, true));
					
					// Add languages
					foreach ($languages as $language) {
						$languageID = CalendarLocale::getIDFromLanguage($language);
						$event->Languages()->add($languageID);
					}
					
					// Import the title and short description
					foreach ($eventData->title as $eventItem) {
						self::copyXMLToDataObject($eventItem, $event, 'Title');
						self::copyXMLToDataObject($eventItem, $event, 'EventTextShort');
					}
					
					// Import the place
					foreach ($eventData->location as $eventItem) 
						self::copyXMLToDataObject($eventItem, $event, 'Place');
					
					// Import the descriptions
					foreach ($eventData->description as $eventItem) 
						self::copyXMLToDataObject($eventItem, $event, 'EventText');
					
					// Import cost
					foreach ($eventData->cost as $eventItem) { 
						self::copyXMLToDataObject($eventItem, $event, 'PriceText');
					}
					if (strlen($event->PriceText)) {
						$event->PriceType = 'NotFree';
					}
					
					// Import the address
					self::copyXMLToDataObject($eventData->locationaddr, $event, 'GoogleMAP', str_replace(array('<br/>', '<br />'), ', ', $eventData->locationaddr), false);
					
					// Import dates
					$times = $eventData->times->children();
					if ($times && $times->count()) {
						foreach ($times as $timeItem) {
							$eventDate = new EventDate();							
							$eventDate->Date = date('Y-m-d', strtotime((string)$timeItem->startdate[0]));
							$eventDate->StartTime = (string)$timeItem->clock[0];
							$eventDate->EndTime = (string)$timeItem->clock[0];
							$eventDate->EventID = $event->ID;
							
							if (!DataObject::get_one('EventDate', "Date = '" . Convert::raw2sql($eventDate->Date) . "' AND StartTime = '" . Convert::raw2sql($eventDate->StartTime) . "'"))
								$eventDate->write();
						}
					}
					
					// Fetch the image
					$imageURL = trim((string)$eventData->imgurl[0]);
					$imageURL = str_replace(' ', '%20', $imageURL);
					if (!$event->EventImages()->exists() && stripos($imageURL, '.jpg') !== false) {						
						$imageFilepath = ASSETS_DIR . '/NetTicket_Images/' . $event->FeedGUID . '.jpg';
						
						echo 'Importing image ' . $imageURL . '<br/>';
						$imageContent = file_get_contents($imageURL, FILE_BINARY, null, null, UploadifyField::convert_bytes('2M'));
						if ($imageContent !== false) {
							// Check for existing image 
							$image = DataObject::get_one('Image', "Filename = '" . $imageFilepath . "'");
							if (!$image) {
								// Create a new one if no existing is found
								$image = new Image();
								$image->setFilename($imageFilepath);
								$image->Title = $event->FeedGUID;
							}
							
							// Save the image content to our image
							$result = file_put_contents($image->getFullPath(), $imageContent, LOCK_EX);
							unset($imageContent);
							
							// If the image is new and we successfully downloaded the image, write changes
							if (!$image->ID && $result !== false) {
								$image->write();
								
								$eventImage = new EventImage();
								$eventImage->EventID = $event->ID;
								$eventImage->ImageID = $image->ID;
								$eventImage->write();
							}
							
							if ($result !== false) 							
								echo 'Downloaded image file to ' . $image->getFullPath() . '<br/>';
						}
					}
					
					// Save changes
					if ($event->Association()->exists()) {
						if ( $event->Association()->Status != 'Active')
							$event->Status = 'Preliminary';
						else
							$event->Status = 'Accepted';
					}
						
					$event->write();
					
				
					echo 'Created/updated event with guid: ' . $event->FeedGUID . '<br/>';
				}
			}
		}
		catch (Exception $e) {
			echo $e->getMessage();
		}
	}
	
	protected static function copyXMLToDataObject($xmlElement, $dataObject, $fieldName, $overrideValue = null, $useLang = true) {
		$origLocale = i18n::get_locale();
						
		if ($useLang) {
			$xmlElementXMLAttributes = $xmlElement->attributes('xml', true);
			$itemLocale = i18n::get_locale_from_lang((string)$xmlElementXMLAttributes['lang'][0]);
			i18n::set_locale($itemLocale);
		}
		
		$value = $overrideValue;
		if ($overrideValue == null)
			$value = (string)$xmlElement[0];
		$value = trim($value);
		$value = str_replace(array('<br/>', '<br />'), "\n", $value);
		$value = str_replace(array('* ', '** ', '*** '), '', $value);
		$value = strip_tags($value);
		
		if ($useLang) {
			$dataObject->setField($fieldName . '_' . $itemLocale, $value);
			i18n::set_locale($origLocale);
		}
		else		
			$dataObject->setField($fieldName, $value);
	}
}

?>
