<?php
class EventDate extends DataObject {
	
	static $extensions = array(		
		'PermissionExtension'
	);
	
	static $db = array(
		'Date' => 'Date',
		'StartTime'	=> 'Time',
		'EndTime' => 'Time'
	);
	
	static $has_one = array(
		'Event' => 'Event',	
	);	
	
	static $default_sort = 'Date';
	
	public function getSortStartTime() {
		return date('Y-m-d H:i:s', strtotime($this->Date . ' ' . $this->StartTime));
	}
	
	public function getSortEndTime() {
		return date('Y-m-d H:i:s', strtotime($this->Date . ' ' . $this->EndTime));
	}	
	
	public function getNiceStartTime() {			
		return date('d.m.Y H:i', strtotime($this->Date . ' ' . $this->StartTime));
	}
 
	public function getNiceEndTime() {
		return date('d.m.Y H:i', strtotime($this->Date . ' ' . $this->EndTime));
	}
		
	public function getHasEndTime() {
		if ($this->StartTime == $this->EndTime)
			return false;
		return true;
	}
	
	function Rfc822($start = true) {
		if ($start)
			return date('r', strtotime($this->Date . ' ' . $this->StartTime));
		else
			return date('r', strtotime($this->Date . ' ' . $this->EndTime));
	}	
	
	public function getWeekDayNice() {	
		//setlocale(LC_TIME, i18n::get_locale().'.utf8');
		$unixtimestamp = strtotime($this->Date . ' ' . $this->StartTime);
		return _t('EventDate.' . strtoupper(date('l', $unixtimestamp)), date('l', $unixtimestamp));
	}
	
	public function getShortWeekDayNice() {	
		//setlocale(LC_TIME, i18n::get_locale().'.utf8');
		$unixtimestamp = strtotime($this->Date . ' ' . $this->StartTime);
		return _t('EventDate.SHORT_' . strtoupper(date('l', $unixtimestamp)), substr(date('l', $unixtimestamp), 0, 2));
	}
	
	public function getShortWeekDayUgly() {	
		//setlocale(LC_TIME, i18n::get_locale().'.utf8');
		$unixtimestamp = strtotime($this->Date . ' ' . $this->StartTime);
		return date('N', $unixtimestamp) . '. ' . _t('EventDate.SHORT_' . strtoupper(date('l', $unixtimestamp)), substr(date('l', $unixtimestamp), 0, 2));
	}
	
	public function getCMSFields() {		
		
		$fields = new FieldSet(				
			$DTSet = new DialogTabSet('TabSet',		
				$generalTab = new Tab(
					'GeneralTab', 
					_t('EventCategory.GENERALTAB', 'General'),
					$date = new DateFieldEx('Date', _t('EventDate.DATE', 'Date')),
					$startTime = new TimeFieldEx('StartTime', _t('EventDate.STARTTIME', 'Start time')),
					$endTime = new TimeFieldEx('EndTime', _t('EventDate.ENDTIME', 'End time'))
		 		)			
			)
		);
	
		/*$startDate->setConfig('dateformat', 'dd.MM.YYYY');
		$startDate->setConfig('showcalendar', true);
		$endDate->setConfig('dateformat', 'dd.MM.YYYY');
		$endDate->setConfig('showcalendar', true);		*/
		
		$this->extend('updateCMSFields', $fields);
		return $fields;
	}
		
}

?>