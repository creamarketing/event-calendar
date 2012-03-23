<?php

class LogEntryDecorator extends DataObjectDecorator {	
	public function onAfterWrite() {
		if (!$this->owner->hasLoggedCreate && $this->owner->isChanged('ID', 2)) {			
			$changedFields = $this->owner->getChangedFields(true, 2);

			if ($changedFields['ID']['before'] == 0 && $changedFields['ID']['after'] >= 1) {
				$logItem = new LogEntry();
				$logItem->Time = date('Y-m-d H:i:s');
				$logItem->Type = $this->owner->ClassName;
				$logItem->Action = 'Created';
				$logItem->ItemID = $this->owner->ID;
				$logItem->UserID = Member::currentUserID();
				$logItem->write();			
				
				if ($this->owner->hasMethod('onLogCreate'))
					$this->owner->onLogCreate($logItem);
				
				$this->owner->hasLoggedCreate = true;
			}
		}
		else if (!$this->owner->hasLoggedEdit) {
			$logItem = new LogEntry();
			$logItem->Time = date('Y-m-d H:i:s');
			$logItem->Type = $this->owner->ClassName;
			$logItem->Action = 'Edited';
			$logItem->ItemID = $this->owner->ID;
			$logItem->UserID = Member::currentUserID();
			$logItem->write();			
				
			if ($this->owner->hasMethod('onLogEdit')) {
				$hasRealChanges = $this->owner->onLogEdit($logItem);
				if (!$hasRealChanges)
					$logItem->delete();
				else {
					if (!$logItem->FieldChanges()->exists() && strlen(!$logItem->Comment))
						$logItem->delete();
				}
			}
			
			$this->owner->hasLoggedEdit = true;
		}
	}
	
	public function onBeforeDelete() {
		if (!$this->owner->hasLoggedDelete) {
			$logItem = new LogEntry();
			$logItem->Time = date('Y-m-d H:i:s');
			$logItem->Type = $this->owner->ClassName;
			$logItem->Action = 'Deleted';
			$logItem->ItemID = $this->owner->ID;
			$logItem->UserID = Member::currentUserID();
			$logItem->write();

			if ($this->owner->hasMethod('onLogDelete'))
				$this->owner->onLogDelete($logItem);
			
			$this->owner->hasLoggedDelete = true;
		}
	}
}

?>
