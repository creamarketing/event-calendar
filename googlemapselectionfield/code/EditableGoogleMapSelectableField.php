<?php
/**
 * Shows a google map on the front end, allowing the user to move
 * and drag the marker round to select a point then saves this point
 * in a hidden field for the form submission
 *
 */

class EditableGoogleMapSelectableField extends EditableFormField {
	
	static $singular_name = 'Google Map';
	
	static $plural_name = 'Google Maps';
	
	static $api_key = "";
	
	public function Icon()  {
		return 'googlemapselectionfield/images/' . strtolower($this->class) . '.png';	
	}
	
	public function getFieldConfiguration() {
		$zoomLevels = array();
		for($i = 1; $i < 20; $i++) {
			$message = ($i == 1) ? _t('EditableFormField.LOWEST', 'Lowest') : "";
			$message = ($i == 19) ? _t('EditableFormField.HIGHEST', 'Highest') : $message;
			$zoomLevels[$i] = ($message) ? $i .' - '. $message : $i;
		}
		$fields = parent::getFieldConfiguration();
		$fields->merge(new FieldSet(
			new TextField(
				"Fields[$this->ID][CustomSettings][StartLant]", _t('EditableFormField.STARTLATITUDE', 'Starting Point Latitude'), 
				($this->getSetting('StartLant')) ? $this->getSetting('StartLant') : '10'
			),
			new TextField(
				"Fields[$this->ID][CustomSettings][StartLong]", _t('EditableFormField.STARTLONGITUDE', 'Starting Point Longitude'),
				($this->getSetting('StartLong')) ? $this->getSetting('StartLong') : '10'
			),
			new DropdownField(
				"Fields[$this->ID][CustomSettings][StartZoom]", _t('EditableFormField.STARTZOOM', 'Starting Zoom Level'),
				$zoomLevels,
				($this->getSetting('StartZoom')) ? $this->getSetting('StartZoom') : '1'
			),
			new TextField(
				"Fields[$this->ID][CustomSettings][MapWidth]", _t('EditableFormField.MAPWIDTH', 'Map Width'),
				($this->getSetting('MapWidth')) ? $this->getSetting('MapWidth') : '300px'
			),
			new TextField(
				"Fields[$this->ID][CustomSettings][MapHeight]", _t('EditableFormField.MAPHEIGHT', 'Map Height'),
				($this->getSetting('MapHeight')) ? $this->getSetting('MapHeight') : '300px'
			)
		));
		return $fields;
	}
	public function getFormField() {
		return new GoogleMapSelectableField($this->Name, $this->Title, 
			$this->getSetting('StartLant'),
			$this->getSetting('StartLong'),
			$this->getSetting('MapWidth'),
			$this->getSetting('MapHeight'),
			$this->getSetting('StartZoom'));
	}
	
	/**
	 * Return a formated output
	 */
	public function getValueFromData($data) {
		$address = (isset($data[$this->Name])) ? $data[$this->Name] : _t('EditableFormField.UNKNOWN', 'Unknown');
		$url = (isset($data[$this->Name.'_MapURL'])) ? ' ('. $data[$this->Name.'_MapURL'] .')': "";
		return $address . $url; 
	}
}
?>