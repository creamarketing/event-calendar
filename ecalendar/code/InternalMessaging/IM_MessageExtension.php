<?php

class IM_MessageExtension extends Extension {

    function extraStatics() {        
		return array(            
			'db' => array(
			),            
			'has_one' => array(
				), 
			);    
	}	
	
	public function updateNiceTo(&$result) {
		if ($this->owner->RecipientType == 'Other' && $this->owner->RecipientClassName == 'Association') {
			$toAssociation = DataObject::get_by_id('Association', (int)$this->owner->ToID);
			if ($toAssociation) {
				$result = $toAssociation->Name;
			}			
		}
		else if ($this->owner->RecipientType == 'Other' && $this->owner->RecipientClassName == 'Mixed') {
			$recipients = array();
			$toUsers = explode(',', $this->owner->ToMany);
			if (is_array($toUsers)) {
				foreach ($toUsers as $toUser) {
					$user_classname_id = explode('_', $toUser);
					
					if (count($user_classname_id) == 2) {
						if ($user_classname_id[0] == 'Association') {
							$toObject = DataObject::get_by_id('Association', (int)$user_classname_id[1]);
							if ($toObject)
								$recipients[] = $toObject->Name;
						}
					}
					else {
						$toObject = DataObject::get_by_id('Member', (int)$toUser);
						if ($toObject)
							$recipients[] = $toObject->FirstName . ' ' . $toObject->Surname;						
					}
				}
			}
			$recipientsString = implode(', ', $recipients);
			if(mb_strlen($recipientsString) > 32)
				$result = mb_substr($recipientsString, 0, 32) . '...';
			
			$result = $recipientsString;
		}		
	}
	
	public function updateAllowedRecipientClassNames(&$classNames) {
		$classNames[] = 'Association';
	}
	
	public function extendedSend($saveToSentbox) {
		if ($this->owner->RecipientType == 'Other' && $this->owner->RecipientClassName == 'Association') {
			// Send to association members
			$association = DataObject::get_by_id('Association', $this->owner->ToID);
			if ($association) {
				$permissions = $association->AssociationPermissions();
				if ($permissions) {
					foreach ($permissions as $permission) {
						$member = $permission->AssociationOrganizer();
						if ($member) {
							$message = new IM_Message();
							$message->FromID = $this->owner->FromID;
							$message->ToID = $member->ID;
							$message->Subject = $this->owner->Subject;
							$message->Body = $this->owner->Body;
							$message->Priority = $this->owner->Priority;
							$message->EmailBodyOnly = $this->owner->EmailBodyOnly;
							$message->send(false);
						}						
					}
				}
			}
		}
	}
	
	public function beforeEmailSent($email, $member, $preventEmailSend = false) {
		$body = $this->owner->Body;
		
		// Search and append email address to SecureLinks, to make them user locale aware
		$secureLinksIdentifiers = SecureLinks::$allowed_actions;
		if (isset($secureLinksIdentifiers['index']))
			unset($secureLinksIdentifiers['index']);
		if (isset($secureLinksIdentifiers['handleMethodRequest']))
			unset($secureLinksIdentifiers['handleMethodRequest']);
		
		// Append email to each special link
		$emailAppend = '[$1?email=' . rawurlencode($member->Email) . ']';
		
		// Body contains BB tags for links
		$result = preg_replace('/\[(url=(.+?)\/((' . implode('|', $secureLinksIdentifiers) . ')\/\d+\/[a-z0-9]+(\/(\w+))?))\]/', $emailAppend, $body);
		
		$this->owner->Body = $result;
	}
}

?>
