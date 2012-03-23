<?php

/**
 * A form field which outputs a google map, input field for an address which moves
 * the map.
 *
 * Currently saves the address of a given point. 
 *
 * @todo Save the Long, Lat fields as well
 * @package googlemapselectionfield
 */

class GoogleMapSelectableField extends FormField {

	/**
	 * @var Mixed
	 */
	private $startLat, $startLong, $mapWidth, $mapHeight, $zoom;
	
	/**
	 * @param String - Name of Field
	 * @param String - Title for Field
	 * @param Int - Start Latitude
	 * @param Int - Starting Map Longitude
	 * @param String - Width of map (px or % to be included)
	 * @param String - Height of map (px or % to be included)
	 * @param Int - Zoom Level (1 to 12)
	 */
	function __construct($name = "", $title = "", $startLat = 0, $startLong = 0, $mapWidth = '300px', $mapHeight = '300px', $zoom = '2') {
		if(strpos($mapWidth, 'px') === false && strpos($mapWidth, '%') === false) $mapWidth .= "px";
		if(strpos($mapHeight, 'px') === false || strpos($mapHeight, '%') !== false){
			$mapHeight = str_replace("%","",$mapHeight);
			$mapHeight .= "px";
		}
		parent::__construct($name, $title);
		$this->startLat = $startLat;
		$this->startLong = $startLong;
		$this->mapWidth = $mapWidth;
		$this->mapHeight = $mapHeight;
		$this->zoom = $zoom;
		
	}
	
	
	function Field() {
		Requirements::javascript(Director::protocol() . "maps.google.com/maps?file=api&amp;v=2&amp;key=". EditableGoogleMapSelectableField::$api_key ."&amp;sensor=true;&hl=" . i18n::get_lang_from_locale(i18n::get_locale()));
		Requirements::javascriptTemplate("googlemapselectionfield/javascript/GoogleMapSelectionField.js", array(
			'Name' => $this->name,
			'DefaultLat' => $this->startLat,
			'DefaultLon' => $this->startLong,
			'MapWidth' => $this->mapWidth,
			'MapHeight' => $this->mapHeight,
			'Zoom' => $this->zoom
		));
		return "
			<div class=\"field text googleMapField\">
				<label class=\"left\">$this->Title</label>
				<input type=\"text\" id=\"{$this->id()}\" name=\"{$this->name}\" value=\"". (!empty($this->value) ? $this->value : _t('EditableFormField.ENTERADDRESS', 'Enter Address')) ."\" class=\"text googleMapAddressField\"/>
				<input type=\"submit\" value=\"". _t('EditableFormField.GO', 'Go') ."\" class=\"submit googleMapAddressSubmit\" />
				<input type=\"hidden\" id=\"{$this->id()}_MapURL\" name=\"{$this->name}_MapURL\" />
				<input type=\"hidden\" id=\"{$this->id()}_Default\" name=\"{$this->name}_Default\" value=\"". _t('EditableFormField.ENTERADDRESS', 'Enter Address')."\" disabled=\"disabled\"/>
				<div id=\"map_{$this->name}\" style=\"width: $this->mapWidth; height: $this->mapHeight;\"></div>
			</div>";
	}
}
