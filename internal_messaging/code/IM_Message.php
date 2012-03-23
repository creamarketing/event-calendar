<?php

class IM_Message extends DataObject {
	static $db = array(
		'FromID' => 'Int',
		'ToID' => 'Int',
		'ToMany' => 'Text',
		'Subject' => 'Varchar(127)',
		'Body' => 'Text',
		'RecipientType' => "Enum('Member,ManyMembers,Other', 'Member')",
		'RecipientClassName' => 'Varchar',
		'Status' => "Enum('Read,Unread','Unread')",
		'RestoreTo' => 'Int',
		'Priority' => "Enum('System,High,Medium,Low', 'Medium')",
		'EmailBodyOnly' => 'Boolean'
	);
		
	static $defaults = array(
		'RecipientType' => 'Member',
		'RecipientClassName' => 'Member',
		'Status' => 'Unread',
		'RestoreTo' => 0,
		'FromID' => 0,
		'Priority' => 'Medium',
		'EmailBodyOnly' => false
	);
	
	static $has_one = array(
		'MessageBox' => 'IM_MessageBox'
	);
		
	public function getNiceFrom() {
		$fromUser = DataObject::get_by_id('Member', (int)$this->FromID);
		$result = null;
		
		if ($fromUser) 
			$result = $fromUser->FirstName . ' ' . $fromUser->Surname;
		else if ((int)$this->FromID == 0)
			return _t('IM_Message.SYSTEM_SENDER', 'System');
		
		$this->extend('updateNiceFrom', $result);
		
		if ($result === null)
			return _t('IM_Message.UNKNOWN_SENDER', 'Unknown sender');
		
		return $result;
	}
	
	public function getNiceTo() {
		$result = null;
		
		if ($this->RecipientType == 'Member') {
			$toUser = DataObject::get_by_id('Member', (int)$this->ToID);
			if ($toUser) {
				return $toUser->FirstName . ' ' . $toUser->Surname;
			}
		} 
		else if ($this->RecipientType == 'ManyMembers') {
			$recipients = array();
			$toUsers = explode(',', $this->ToMany);
			if (is_array($toUsers)) {
				foreach ($toUsers as $toUserID) {
					$toUser = DataObject::get_by_id('Member', (int)$toUserID);
					if ($toUser)
						$recipients[] = $toUser->FirstName . ' ' . $toUser->Surname;
				}
			}
			$recipientsString = implode(', ', $recipients);
			if(mb_strlen($recipientsString) > 32)
				return mb_substr($recipientsString, 0, 32) . '...';
			return $recipientsString;
		}
		
		$this->extend('updateNiceTo', $result);
		
		if ($result === null)
			return _t('IM_Message.UNKNOWN_RECIPIENT', 'Unknown recipient');		
		
		return $result;
	}	
	
	public function getAllowedRecipientClassNames() {
		$classNames = array('Member');
		
		$this->extend('updateAllowedRecipientClassNames', $classNames);
			
		return $classNames;
	}
	
	public function getNiceDate() {
		return date('d.m.Y H:i', strtotime($this->Created));
	}
	
	public function getDateSort() {
		return date('Y-m-d H:i:s', strtotime($this->Created));
	}
	
	public function getNiceStatus() {
		if ($this->Status == 'Read') {
			return '<span class="message-read">&nbsp;</span>';
		}
		return '<span class="message-unread">&nbsp;</span>';
	}
	
	public function IsTrash() {
		$messageBox = $this->MessageBox();
		if ($messageBox->exists() && $messageBox->Type == 'Trashbox')
			return true;
		return false;
	}
	
	public function CanReply() {
		$messageBox = $this->MessageBox();
		if ($messageBox->exists() && $messageBox->Type == 'Inbox')
			return true;
		return false;		
	}
	
	public function IsInbox() {
		return $this->CanReply();
	}
	
	public function send($saveToSentbox = true) {			
		$fromUser = DataObject::get_by_id('Member', (int)$this->FromID);
		$toUser = null;
		
		if ($this->RecipientType == 'Member')
			$toUser = DataObject::get_by_id('Member', (int)$this->ToID);
			
		if ($fromUser) {
			// Create a sentbox if FROM doesn't have one
			if ($fromUser->IM_Sentbox()->exists() != true) {
				$messageBox = new IM_MessageBox();
				$messageBox->OwnerID = $fromUser->ID;
				$messageBox->Type = 'Sentbox';
				$messageBox->write();
				
				$fromUser->IM_SentboxID = $messageBox->ID;
				DB::manipulate(array(
					'Member' => array(
						'command' => 'update',
						'fields' => array('IM_SentboxID' => $messageBox->ID),
						'where' => "ID = {$fromUser->ID}"
					)
				));
			} 
		}
		if ($toUser) {
			// Create a inbox if TO doesn't have one
			if ($toUser && $toUser->IM_Inbox()->exists() != true) {
				$messageBox = new IM_MessageBox();
				$messageBox->OwnerID = $toUser->ID;
				$messageBox->Type = 'Inbox';
				$messageBox->write();
				
				$toUser->IM_InboxID = $messageBox->ID;
				DB::manipulate(array(
					'Member' => array(
						'command' => 'update',
						'fields' => array('IM_InboxID' => $messageBox->ID),
						'where' => "ID = {$toUser->ID}"
					)
				));				
			} 		
		}

		// Create the mail for FROM (the sender of this message)
		if ($saveToSentbox && $fromUser) {
			$message = new IM_Message();
			$message->FromID = $this->FromID;
			$message->ToID = $this->ToID;
			$message->Subject = $this->Subject;
			$message->Body = $this->Body;
			$message->MessageBoxID = $fromUser->IM_SentboxID;
			$message->RecipientType = $this->RecipientType;
			$message->RecipientClassName = $this->RecipientClassName;
			$message->ToMany = $this->ToMany;
			$message->Status = 'Read';
			$message->Priority = $this->Priority;
			$message->EmailBodyOnly = $this->EmailBodyOnly;
			$message->write();			
		}
			
		// Normal message
		if ($this->RecipientType == 'Member' && $toUser) {
			// Create the mail for TO (the recipient of this message)
			$message = new IM_Message();
			$message->FromID = $this->FromID;
			$message->ToID = $this->ToID;
			$message->Subject = $this->Subject;
			$message->Body = $this->Body;
			$message->Priority = $this->Priority;
			$message->EmailBodyOnly = $this->EmailBodyOnly;
			$message->MessageBoxID = $toUser->IM_InboxID;
			$message->write();
		}
		else if ($this->RecipientType == 'ManyMembers') {
			// Send to multiple members
			$memberIDs = explode(',', $this->ToMany);				
			if (is_array($memberIDs)) {
				foreach ($memberIDs as $memberID) {
					$member = DataObject::get_by_id('Member', (int)$memberID);
					if ($member) {
						$message = new IM_Message();
						$message->FromID = $this->FromID;
						$message->ToID = $member->ID;
						$message->Subject = $this->Subject;
						$message->Body = $this->Body;
						$message->Priority = $this->Priority;
						$message->EmailBodyOnly = $this->EmailBodyOnly;
						$message->send(false);		
					}
				}
			}				
		}
		else if ($this->RecipientType == 'Other' && $this->RecipientClassName == 'Mixed') {
			// Send to multiple members
			$memberIDs = explode(',', $this->ToMany);				
			if (is_array($memberIDs)) {
				foreach ($memberIDs as $memberID) {
					$classname_id = explode('_', $memberID);
					
					if (count($classname_id) == 2 && in_array($classname_id[0], $this->getAllowedRecipientClassNames())) {
						$message = new IM_Message();
						$message->FromID = $this->FromID;
						$message->ToID = (int)$classname_id[1];
						$message->RecipientType = 'Other';
						$message->RecipientClassName = $classname_id[0];
						$message->Subject = $this->Subject;
						$message->Body = $this->Body;
						$message->Priority = $this->Priority;
						$message->EmailBodyOnly = $this->EmailBodyOnly;
						$message->send(false);
					} else {
						$member = DataObject::get_by_id('Member', (int)$memberID);
						if ($member) {
							$message = new IM_Message();
							$message->FromID = $this->FromID;
							$message->ToID = $member->ID;
							$message->Subject = $this->Subject;
							$message->Body = $this->Body;
							$message->Priority = $this->Priority;
							$message->EmailBodyOnly = $this->EmailBodyOnly;
							$message->send(false);		
						}
					}					
				}
			}				
		}		
		
		$this->extend('extendedSend', $saveToSentbox);
	}
	
	public function onAfterWrite() {
		parent::onAfterWrite();
		
		if ($this->isChanged('ID', 2)) {
			$changedFields = $this->getChangedFields(true, 2);
			if ($changedFields['ID']['before'] == 0 && $changedFields['ID']['after'] != 0) {
				if ($this->IsInbox()) {
					$owner = $this->MessageBox()->Owner();
					if (($owner->IM_Inbox_EmailNotification == true || $this->Priority == 'System') && $owner && strlen($owner->Email)) {
						$email = new Email();
						$email->setTemplate(IM_Controller::$default_email_template);
						$email->populateTemplate(array('Member' => $owner, 'Message' => $this, 'LoginLink' => Director::absoluteBaseURL()));
						$email->setTo($owner->Email);
						$email->setSubject('=?UTF-8?B?' . base64_encode($this->Subject) . '?=');
						if (IM_Controller::$default_email_address != '')
							$email->setFrom(IM_Controller::$default_email_address);
												
						$preventEmailSend = false;
						$this->extend('beforeEmailSent', $email, $owner, &$preventEmailSend);
						
						try {
							if (!$preventEmailSend)
								@$email->send();
						}
						catch (Exception $e) {
							// silently catch email sending errors...
						}
					}
				}
			}
		}
	}
}

?>
