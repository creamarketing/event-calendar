<?php

class LogEntry extends DataObject {
	
	static $extensions = array(
		'PermissionExtension'
	);
	
	static $db = array(
		'Time' => 'SS_Datetime',
		'Type' => 'Varchar',
		'Action' => 'Enum("Created,Edited,Deleted,Other","Other")',
		'ItemID' => 'Int',
		'Comment' => 'Varchar(255)'
	);
	
	static $has_one = array(
		'User' => 'Member'
	);
	
	static $has_many = array(
		'FieldChanges' => 'LogEntry_FieldChange'
	);
	
	static $sortfield_override = array(
		'NiceTime' => 'ID'
	);		
			
	public function getNiceTime() {
		return date('d.m.Y H:i', strtotime($this->Time));
	}
	
	public function getNiceType() {
		return _t($this->Type . '.SINGULARNAME', $this->Type);
	}
	
	public function getNiceUser() {
		if (!$this->User()->exists())
			return _t('LogEntry.SYSTEM', 'System');
		return $this->User()->FullName;
	}
	
	public function getNiceAction() {
		return _t('LogEntry.ACTION_' . strtoupper($this->Action), $this->Action);
	}	
		
	public function getLogComment() {
		$translatedComment = _t($this->Comment, '');
		
		if ($translatedComment != '')
			return $translatedComment;
		
		return $this->Comment;
	}
	
	public function getRequirementsForPopup() {
		Requirements::customScript('top.RemoveSaveButton();');
	}
	
	public function getCMSFields() {		
		
		$fieldChanges = new DialogHasManyDataObjectManager($this, 'FieldChanges', 'LogEntry_FieldChange', 
			array(
				'NiceFieldName' => _t('LogEntry_FieldChange.FIELDNAME', 'Field name'),
				'Before' => _t('LogEntry_FieldChange.BEFORE', 'Before'),
				'After' => _t('LogEntry_FieldChange.AFTER', 'After'),
			),
			null,
			"LogEntryID = {$this->ID}"
		);
		
		$fieldChanges->setPluralTitle(_t('LogEntry_FieldChange.PLURALNAME', 'Field changes'));
		$fieldChanges->setMarkingPermission(false);
		$fieldChanges->removePermission('add');
		
		$fields = new FieldSet(      	
			$DTSet = new DialogTabSet('TabSet',		
		      	$generalTab = new Tab('GeneralTab', _t('CalendarLocale.GENERALTAB', 'General'),
					new ReadonlyField('NiceTime', _t('LogEntry.DATE', 'Date'), $this->NiceTime),
					new ReadonlyField('NiceUser', _t('AssociationOrganizer.SINGULARNAME', 'User'), $this->NiceUser),
					new ReadonlyField('NiceType', _t('LogEntry.TYPE', 'Type'), $this->NiceType),
					new ReadonlyField('NiceAction', _t('LogEntry.ACTION', 'Action'), $this->NiceAction),
					new ReadonlyField('LogComment', _t('LogEntry.COMMENT', 'Comment'), $this->LogComment)
		     	)			
			)
		);		
		
		$generalTab->push($fieldChanges);
		
		return $fields;
	}
	
	public function AddChangedField($fieldname, $before, $after, $fieldLabel = '') {	
		// Ignore field if it is the same
		if ($before == $after)
			return false;
		
		$logEntry = new LogEntry_FieldChange();
		$logEntry->LogEntryID = $this->ID;
		$logEntry->FieldName = $fieldname;
		$logEntry->Before = $before;
		$logEntry->After = $after;
		$logEntry->FieldLabel = $fieldLabel;
		$logEntry->write();
		
		return $logEntry;
	}
}

class LogEntry_FieldChange extends DataObject {
	
	static $extensions = array(
		'PermissionExtension'
	);	
	
	static $db = array(
		'FieldName' => 'Varchar(64)',
		'FieldLabel' => 'Varchar(255)',
		'Before' => 'Text',
		'After' => 'Text'
	);
	
	static $has_one = array(
		'LogEntry' => 'LogEntry'
	);
	
	public function getRequirementsForPopup() {
		Requirements::customScript('top.RemoveSaveButton();');
	}	
		
	public function getCMSFields() {		
		$fields = new FieldSet(      	
			$DTSet = new DialogTabSet('TabSet',		
		      	$generalTab = new Tab('GeneralTab', _t('CalendarLocale.GENERALTAB', 'General'),
					new ReadonlyField('NiceFieldName', _t('LogEntry_FieldChange.FIELDNAME', 'Field name'), $this->FieldName),
					new ReadonlyField('Before', _t('LogEntry_FieldChange.BEFORE', 'Before'), $this->Before),
					new ReadonlyField('After', _t('LogEntry_FieldChange.AFTER', 'After'), $this->After)
		     	)			
			)
		);			
		
		return $fields;
	}
	
	public function getNiceFieldName() {
		if (strlen($this->FieldLabel)) 
			return _t($this->FieldLabel, $this->FieldLabel) . ' (' . $this->FieldName . ')';
		
		return $this->FieldName;
	}
}

?>