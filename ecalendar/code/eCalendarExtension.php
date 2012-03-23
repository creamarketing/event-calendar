<?php 
class eCalendarExtension extends Extension {
	public function extraStatics() {
		
	} 
	
	public static $allowed_actions = array(		
		'OrganizerLoginForm',
		'OrganizerRegistrationForm',
	
	);
	
	public function getRequirementsForPopup() {
		Requirements::css('ecalendar/css/eCalendarAdmin.css');
	}
	
	public function IncludeIMConfirmScripts() {
		Requirements::css('ecalendar/css/IMConfirmScripts.css');
		Requirements::javascript('ecalendar/javascript/IMConfirmScripts.js');
	}
	
	public function FakeDOMRequirements() {
		Requirements::javascript('dialog_dataobject_manager/javascript/dialog_dataobject_manager.js');
		Requirements::css('dialog_dataobject_manager/css/DialogDataObjectManager.css');
		Requirements::css('dialog_dataobject_manager/css/smoothness/jquery-ui-1.8.6.custom.css');		
		
		// javascript localization
		Requirements::javascript(SAPPHIRE_DIR . '/javascript/i18n.js');
		Requirements::add_i18n_javascript('dialog_dataobject_manager/javascript/lang');		
	}	
	
	public function getECalendarRequirements() {
		// jQuery and jQuery ui
		Requirements::javascript(THIRDPARTY_DIR . '/jquery/jquery.js');
		Requirements::javascript(THIRDPARTY_DIR.'/jquery-livequery/jquery.livequery.js');		
		Requirements::javascript(THIRDPARTY_DIR . '/jquery-form/jquery.form.js');
		Requirements::javascript('dialog_dataobject_manager/javascript/jquery-ui-1.8.16.custom.min.js');
		Requirements::css('ecalendar/css/smoothness/jquery-ui-1.8.6.custom.css');
		
		// javascript localization
		Requirements::javascript(SAPPHIRE_DIR . '/javascript/i18n.js');
		Requirements::add_i18n_javascript('ecalendar/javascript/lang');
		
		// qTip jQuery tooltip plugin
		Requirements::javascript('ecalendar/javascript/jquery.qtip-1.0.0-rc3.min.js');			
    //	Requirements::javascript('ecalendar/javascript/AdvancedDropdownField.js');
	}
			
	public static function isAdmin($member = null) {
		
		if ($member) {
			
		} else {
			$member = Member::currentUser();
		}
		
		if ($member) {
			return (
				( $member->inGroup('administrators') 
					|| $member->inGroup('eventadmins')
				)
			);
		} 
		
		return false;
	}
	
	public static function isAdminPage() {
		return Controller::curr()->class == 'eCalendarAdmin';
	}
	
	// What is this useful for, lended from some other e-project partly...
	public static function getAdminID() {
		$member = Member::CurrentUser();
		if ($member && ($member->inGroup('eventadmins') || $member->inGroup('administrators'))) {
			return $member->currentUserID();
		}
		
		return null;
	}	
	
	//public static function 
	
	public function OrganizerLoginForm() {
		return AssociationOrganizer::OrganizerLoginForm($this->owner);
	}
	
	public function OrganizerRegistrationForm() {
		return AssociationOrganizer::OrganizerRegistrationForm($this->owner);
	}
	
	public function IsVerifiedEmail() {
		$member = Member::currentUser();
		if ($member) {
			if (strlen($member->EmailVerified) || self::IsAdmin() ) {
				return true;
			}
		}
		return false;
	}
	
	public function IsLoggedInAsOrganizer() {
		$member = Member::currentUser();
		
		if ($member) {
			return (
				( $member->inGroup('eventusers') )
			);
		} 
		
		return false;
	}
	
	public function IsLoggedIn() {
		$member = Member::currentUser();
		
		if ($member) {
			return true;
		} 
		
		return false;
	}
	
	public function IsBackWebRequest() {
		if (Director::CurrentPage() == 'SecurityAdmin' || Director::CurrentPage() == 'eCalendarAdmin') {
			return true;
		}
		return false;
	}
	
	public function IsMunicipalModerator( $member = null ) {
		if ( count(PermissionExtension::getMyMunicipals( $member )) > 0 ) {
			return true;
		}
		
		return false;
	}
	
	// Copied partly from ContentController.php
	public function LoggedInMessage() {
		$member = Member::currentUser();
		$logInMessage = '';
		if($member) {
			$firstname = Convert::raw2xml($member->FirstName);
			$surname = Convert::raw2xml($member->Surname);
			$logInMessage = _t('ContentController.LOGGEDINAS', 'Logged in as') ." {$firstname} {$surname} - <a href=\"Security/logout\">". _t('ContentController.LOGOUT', 'Log out'). "</a>";
		}
			
		return $logInMessage;
	}
	
	public function getEmailVerifySubject () {
		return AssociationOrganizer::getEmailVerifySubject();	
	}	
	
	public function VerifyEmail() {			
		$sqlSafe = Convert::raw2sql($_GET);
	
		if (isset($sqlSafe['email'])) {
			$member = Member::get_one("Member", "Email='".urldecode($sqlSafe['email'])."'");
			if ($member && !$member->EmailVerified) {
				$correcthash = md5($member->ID.$member->Email.'--'.strtotime($member->Created));
				$correcthash = $member->Hash; // Had changed, how is it possible?
				
				// success
				if (isset($sqlSafe['hash']) && $sqlSafe['hash'] == $correcthash) {
					$member->EmailVerified = date('Y-m-d H:i:s');
					$member->write();					
										
					self::memberRegisteredNotification($member);
					self::SendMemberWelcomeMessage($member);
					
					return Director::redirect($this->owner->Link() . '?Verify=ok');					
				}			
			} elseif ($member) { // Already verified
				return Director::redirect($this->owner->Link() . '?Verify=ok2');	
			}
		}
		
		return Director::redirect($this->owner->Link() . '?Verify=fail');	
	}	
			
	/*public function getChildrenIDRecursive( $object, $return = array(), $childrenMethod = "AllChildren", $rootCall = true ) {
				
		if($object->hasMethod($childrenMethod)) {
			$children = $object->$childrenMethod();
		} else {
			user_error(
				sprintf("Can't find the method '%s' on class '%s' for getting tree children", 
						$childrenMethod, 
						get_class($object)
				), 
				E_USER_ERROR
			);
		}
			
		
		if($children) {			
			foreach($children as $child) {
				$foundAChild = true;	
				$return[$child->ID] = $child->ID;
				
				$morechildren = self::getChildrenIDRecursive( $child, $return, $childrenMethod, false );			
				
				if ($morechildren) {
					foreach ($morechildren as $childId) {
						$return[$childId] = $childId;				
					}
				} 
			}			
		}
		
		if(isset($foundAChild) && $foundAChild) {
			return $return;
		} else {
			return false;
		}
	}*/
	
	function getChildrenOneLevel($object, $childrenMethod = "getChildren") {
		
		if($object->hasMethod($childrenMethod)) {
			$children = $object->$childrenMethod();
		} else {
			user_error(
				sprintf("Can't find the method '%s' on class '%s' for getting tree children", 
						$childrenMethod, 
						get_class($object)
				), 
				E_USER_ERROR
			);
		}
			
		return $children;
	}

	function getChildrenRecursive($parent_object, $tree_set = null, $childrenMethod = "getChildren") {				
		if (!$tree_set) {
			$tree_set = new DataObjectSet();
		}
		// getOneLevel() returns a one-dimensional array of childs       
		$tree = self::getChildrenOneLevel(&$parent_object, $childrenMethod);     
		
		if ( $tree->Count() > 0 && $tree ){      
			$tree_set->merge( $tree );
		}
		
		foreach ($tree as $key => $object) {
			self::getChildrenRecursive(&$object, &$tree_set);
		}   
		
		return $tree_set;
	}
	
	function getChildrenIDRecursive($parent_object, $childrenMethod = "getChildren") {
		$tree_set = self::getChildrenRecursive(&$parent_object, null, $childrenMethod);
		if ($tree_set) {
			$map = $tree_set->Map('ID', 'ID');
		} else {
			$map = array();
		}
		
		return $map;
	}

	public static function FindClosestMembers($association, $direction = 'parent', $memberType = 'Moderator', $excludeIDs = array()) {
		$members = new DataObjectSet();

		// Get members of specific type in this association
		$permissions = $association->AssociationPermissions();
		if ($permissions->Count()) {
			foreach ($permissions as $perm) {
				if ($perm->Type == $memberType) {
					$assocOrg = $perm->AssociationOrganizer();
					if ($assocOrg->exists() && !in_array($assocOrg->ID, $excludeIDs))
						$members->push($assocOrg);
				}
			}
		}
		
		// No members yet, try parent
		if ($direction == 'parent' && !$members->Count()) {
			$parent = $association->Parent();
			if ($parent->exists()) { // We have a parent?
				// If so, merge them with our current ones
				$parentMembers = self::FindClosestMembers($parent, $direction, $memberType, $excludeIDs);
				$members->merge($parentMembers);
			}	
		}
		
		return $members;
	}
	
	public static function FindAdministrators() {
		$group = DataObject::get_one("Group", "Code = 'administrators' OR Code = 'eventadmins'"); 
		$admins = $group->Members();
		if ($admins->Count()) 
			return $admins;
		
		return false;
	}
	
	public static function FindSystemAdministrator() {
		$admin = DataObject::get_one("AssociationOrganizer", "SystemAdmin = 1");
		if ($admin) 
			return $admin;
		
		return false;
	}	
	
	public static function memberRegisteredNotification($organizer) {		
		if (!($organizer instanceof AssociationOrganizer))
			return;		
		
		$permissions = $organizer->AssociationPermissions();
		if ($permissions->Count()) {
			foreach ($permissions as $perm) {
				$sent = false;
				// Check for moderators in our associations				
				$association = $perm->Association();
				if ($association->exists()) {
					$assocModerators = self::FindClosestMembers($association, 'parent', 'Moderator', array($organizer->ID));
					foreach ($assocModerators as $moderator) {
						self::SendMemberRegisteredEmail($moderator, $organizer, $association);
						$sent = true;
					}
					
					// If no moderators, sending to municipal moderator
					if ($sent == false) { 
						$moderators = $association->Municipal()->AssociationOrganizers();
						if ($moderators->Count()) {
							foreach ($moderators as $moderator) {
								self::SendMemberRegisteredEmail($moderator, $organizer, $association);
								$sent = true;
							}
						}									
					}
					
					// Send to admins then..
					if ($sent == false) { 
						$admins = eCalendarExtension::FindAdministrators();
						if ($admins) {
							foreach ($admins as $admin) {
								self::SendMemberRegisteredEmail($admin, $organizer, $association);
								$sent = true;
							}
						}
					}
				}
			}
		}
	}
	
	public static function SendMemberSelfNewInviteRequestMessage($creator, $member, $association) {
		$subject = _t('NotificationTask.SELFNEWINVITEMSG_SUBJECT', 'Invitation');
		$body = sprintf(
					_t('NotificationTask.SELFNEWINVITEMSG_BODY1', 'You have invited "%s" to "%s".'), 
					$member->FullName,
					$association->Name
				) . "\n\n";		
		$body .= _t('NotificationTask.SELFNEWINVITEMSG_BODY2', 'You will receive a message when the invitation has been answered.'). "\n";
		
		$msg = new IM_Message();
		$msg->Subject = $subject;
		$msg->Body = $body;								
		$msg->ToID = $creator->ID;
		$msg->FromID = 0;
		$msg->send(false);
	} 	
	
	public static function SendMemberSelfNewRequestMessage($organizer, $association) {
		$subject = _t('NotificationTask.SELFNEWREQUESTMSG_SUBJECT', 'Association permission');
		$body = sprintf(
					_t('NotificationTask.SELFNEWREQUESTMSG_BODY1', 'You requested for permissions in %s %s.'), 
					strtolower(_t('Association.TYPE_'.strtoupper($association->Type), $association->Type)), 
					$association->Name
				) . "\n\n";		
		$body .= _t('NotificationTask.SELFNEWREQUESTMSG_BODY2', 'You will receive a message when the request is accepted.'). "\n";
		
		$msg = new IM_Message();
		$msg->Subject = $subject;
		$msg->Body = $body;								
		$msg->ToID = $organizer->ID;
		$msg->FromID = 0;
		$msg->send(false);
	} 
	
	public static function SendMemberWelcomeMessage($member) {
		$originalLocale = i18n::get_locale();
	
		$currentLocale = $member->Locale; 
		i18n::set_locale($currentLocale);		
		
		$subject = _t('RegistrationPage.WELCOMEMSG_SUBJECT', 'Welcome!');
		$body = _t('RegistrationPage.WELCOMEMSG_BODY1', 'Welcome to the Ostrobothnia Event Calendar') . "\n\n";
		$body .= _t('RegistrationPage.WELCOMEMSG_BODY2', 'Your Email is verified and you can now start working with your events.'). "\n";
		$body .= _t('RegistrationPage.WELCOMEMSG_BODY3', 'When your profile is fully approved you will recieve a message and all your events can then be published.'). "\n";
		
		$msg = new IM_Message();
		$msg->Subject = $subject;
		$msg->Body = $body;								
		$msg->ToID = $member->ID;
		$msg->FromID = 0;
		$msg->EmailBodyOnly = true;
		$msg->send(false);
		
		i18n::set_locale($originalLocale);
	}
	
	public static function SendMemberWelcomeMessageAfterAdd($member) {
		$originalLocale = i18n::get_locale();
	
		$currentLocale = $member->Locale; 
		i18n::set_locale($currentLocale);
		
		$subject = _t('AssociationOrganizer.WELCOMEADDMSG_SUBJECT', 'Welcome!');
		$body = _t('AssociationOrganizer.WELCOMEADDMSG_BODY1', 'Welcome to the Ostrobothnia Event Calendar') . "\n\n";
		$body .= _t('AssociationOrganizer.WELCOMEADDMSG_BODY2', 'An account has been created for you and you can now start working with your events.'). "\n";
		$body .= _t('AssociationOrganizer.WELCOMEADDMSG_BODY3', 'When your profile is fully approved you will recieve a message and all your events can then be published.'). "\n\n";
		
		$body .= sprintf(_t('AssociationOrganizer.WELCOMEADDMSG_BODY4', 'Login: %s'), $member->Email). "\n";
		
		if (isset($_POST['Password']) && isset($_POST['Password']['_Password']))
			$body .= sprintf(_t('AssociationOrganizer.WELCOMEADDMSG_BODY5', 'Password: %s'), $_POST['Password']['_Password']). "\n";
		
		$msg = new IM_Message();
		$msg->Subject = $subject;
		$msg->Body = $body;								
		$msg->ToID = $member->ID;
		$msg->FromID = 0;
		$msg->EmailBodyOnly = true;
		$msg->send(false);
		
		i18n::set_locale($originalLocale);
	}	
	
	// Note! Sending a message even if called Email here 
	public static function SendMemberRegisteredEmail($moderator, $organizer, $association) {
		$originalLocale = i18n::get_locale();
	
		$currentLocale = $moderator->Locale; 
		i18n::set_locale($currentLocale);								

		$subject = _t('RegistrationPage.VERIFYNOTICE_SUBJECT', 'Member needs verification');
		$body = _t('RegistrationPage.VERIFYNOTICE_BODY1', 'A new member needs verification.') . "\n\n";
		$body .= _t('AssociationOrganizer.NAME', 'Name') . ': '. $organizer->FullName . "\n";
		$body .= _t('AssociationOrganizer.EMAIL', 'Email') . ': '. $organizer->Email . "\n";
		$body .= _t('AssociationOrganizer.PHONE', 'Phone') . ': '. $organizer->Phone . "\n\n";
		
		if ($association->Status == 'New')
			$body .= "[b]" . _t('Association.SINGULARNAME', 'Association'). ' ** '._t('Association.STATUS_NEW', 'New')." ** [/b]\n";
		else
			$body .= "[b]" . _t('Association.SINGULARNAME', 'Association')."[/b]\n";
		$body .= _t('Association.TYPE', 'Type') . ': ' . _t('Association.TYPE_'.strtoupper( $association->Type ), $association->Type)."\n";
		$body .= _t('Association.NAME', 'Name') . ': ' . $association->Name . "\n";
		$body .= _t('Municipal.SINGULARNAME', 'Municipality') . ': ' . $association->Municipal()->Name . "\n";
		$body .= _t('Event.ADDRESS', 'Address') . ': ' . $association->PostalAddress . ", " . $association->PostalCode . ", " . $association->PostalOffice . "\n";
		$body .= _t('Association.PHONE', 'Phone') . ': ' . $association->Phone . "\n";
		$body .= _t('Association.EMAIL', 'Email') . ': ' . $association->Email . "\n";
		$body .= _t('Association.HOMEPAGE', 'Homepage') . ': ' . $association->Homepage . "\n";
		$body .= _t('Association.LOGO', 'Logo') . ": \n";
		if ($association->LogoID) 
			$body .= "[img]" . $association->Logo()->PaddedImage(142, 80)->AbsoluteLink() . "[/img]\n";		
		
		$acceptLink = SecureLinkRequest::generate_link('AcceptMember', $organizer->ID, '', date('Y-m-d', strtotime('+1 month')));
		$body .= "\n" . sprintf(_t('RegistrationPage.VERIFYNOTICE_BODY2', 'Click [url=%s]here[/url] to accept the account and publish all related events.'), $acceptLink);
		
		$msg = new IM_Message();
		$msg->Subject = $subject;
		$msg->Body = $body;								
		$msg->ToID = $moderator->ID;
		$msg->FromID = 0;
		$msg->send(false);
			
		i18n::set_locale($originalLocale);
	}
	
	public static function formatBytes($b,$p = null) {
		/**
		 * 
		 * @author Martin Sweeny
		 * @version 2010.06.17
		 * 
		 * returns formatted number of bytes. 
		 * two parameters: the bytes and the precision (optional).
		 * if no precision is set, function will determine clean
		 * result automatically.
		 * 
		 **/
		$units = array("B","kB","MB","GB","TB","PB","EB","ZB","YB");
		$c=0;
		if(!$p && $p !== 0) {
			foreach($units as $k => $u) {
				if(($b / pow(1024,$k)) >= 1) {
					$r["bytes"] = $b / pow(1024,$k);
					$r["units"] = $u;
					$c++;
				}
			}
			return number_format($r["bytes"],2) . " " . $r["units"];
		} else {
			return number_format($b / pow(1024,$p)) . " " . $units[$p];
		}
	}
}