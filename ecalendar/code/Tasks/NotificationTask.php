<?php

class NotificationTask extends DailyTask {
	
	function process() {
		$ret = '<br />START: '.date('d.m.Y H:i:s').'<br />';
		$originalLocale = i18n::get_locale();
		
		# 1. A Nofication about users that havent logged in for 15 months to user
		$time = strtotime('-18 months');
		if (isset($_GET['force'])) {
			$time = strtotime('-1 h');		
		}
		
		$where = "
				LastVisited <= '".date('Y-m-d', $time)." 23:59:59' 
				AND LastVisited >= '".date('Y-m-d', $time)." 00:00:00'
		";
		
		$organizers = DataObject::get('AssociationOrganizer', $where);
		if ($organizers) {
			foreach ($organizers as $organizer) {				
				$currentLocale = $organizer->Locale; 
				i18n::set_locale($currentLocale);
			
				$keepAliveLink = SecureLinkRequest::generate_link('KeepMemberAlive', $organizer->ID, strtotime($organizer->LastVisited), date('Y-m-d', strtotime('+1 week')));
				$subject = _t('NotificationTask.INACTIVENOTICEU_SUBJECT', 'Calender account inactive');
				$body =  _t('NotificationTask.INACTIVENOTICEU_BODY1', 'Our system indicates that your account has been inactive for a longer time (15 months)') . "\n\n";
				$body .= _t('NotificationTask.INACTIVENOTICEU_BODY2', 'If you dont login or use the link below your account in one week, it will be permanently removed.') . "\n\n";
				$keepAliveLinkHTML = '<a href="'.$keepAliveLink.'">'._t('NotificationTask.INACTIVENOTICEU_HERE', 'here').'</a>';
				$body .= sprintf(_t('NotificationTask.INACTIVENOTICEU_BODY3', 'Click %s to prevent removal of the account.'), $keepAliveLinkHTML);
				// We dont want to send a internal message cause would be stupid if the user has to login to see it :P
				//eCalendarAdmin::sendEmail($subject, $body, $organizer->Email);
				$message = new IM_Message();
				$message->Subject = $subject;
				$message->Body = $body;
				$message->ToID = $organizer->ID;
				$message->Priority = 'System'; // Will ALWAYS send copy to email
				$message->send(false);
			}
		}
		
		# 1. B Nofication about users that havent logged in for 15 months to MODERATOR
		$time = strtotime('-15 months -1 week');		
		if (isset($_GET['force2'])) {
			$time = strtotime('-1 h');		
		}
		
		$where = "
				LastVisited <= '".date('Y-m-d', $time)." 23:59:59' 
				AND LastVisited >= '".date('Y-m-d', $time)." 00:00:00'
		";
		
		$organizers = DataObject::get('AssociationOrganizer', $where);
		if ($organizers) {
			foreach ($organizers as $organizer) {	
				$sentOnce = array();		
				$associationPermissions = $organizer->AssociationPermissions();
				if ($associationPermissions) {
					$keepAliveLink = SecureLinkRequest::generate_link('KeepMemberAlive', $organizer->ID, strtotime($organizer->LastVisited), date('Y-m-d', strtotime('+1 week')));
					
					foreach ($associationPermissions as $associationPermission) {
						$association = $associationPermission->Association();
						$moderators = eCalendarExtension::FindClosestMembers($association, 'parent', 'Moderator', array($organizer->ID));
						if ($moderators->Count() > 0) {
							foreach ($moderators as $moderator) {
								if (!isset($sentOnce[$moderator->ID])) {
										$this->SendDeleteNoticeModerator($moderator, $organizer, $keepAliveLink);
										$sentOnce[$moderator->ID] = true;
								}							
							}
						} else {
							$municipal = $association->Municipal();							
							$municipalModerators = $municipal->AssociationOrganizers("AssociationOrganizer.ID NOT IN ('".implode("','", array($organizer->ID))."')");
							if ($municipalModerators->exists()) {
								foreach ($municipalModerators as $municipalModerator) {	
									if (!isset($sentOnce[$municipalModerator->ID])) {
										$this->SendDeleteNoticeModerator($municipalModerator, $organizer, $keepAliveLink);
										$sentOnce[$municipalModerator->ID] = true;
									}
								}
							}
							else {
								// Send to administrators then
								$admins = eCalendarExtension::FindAdministrators();
								if ($admins) {
									foreach ($admins as $admin) {
										if (!isset($sentOnce[$admin->ID])) {
											$this->SendDeleteNoticeModerator($admin, $organizer, $keepAliveLink);
											$sentOnce[$admin->ID] = true;
										}	
									}
								}
							}
						}
					}
				}
				
				$organizer->CallerClass = 'NotificationTask';
				$organizer->WillBeDeleted = date('Y-m-d', strtotime('+1 week'));
				$organizer->write();
			}
		}
		
		# 1. C Deleting users that didnt do a login
		$time = time();			
		
		$where = "
				WillBeDeleted <= '".date('Y-m-d', $time)." 23:59:59' 
				AND WillBeDeleted >= '".date('Y-m-d', $time)." 00:00:00'
		";
		
		$organizers = DataObject::get('AssociationOrganizer', $where);
		if ($organizers) {
			foreach ($organizers as $organizer) {					
				// Checking if user is connected to upcoming events still!!
				$events = $organizer->Events('DATE(Event.End) >= CURDATE()', 'Event.End DESC');
				if ($events->Count() > 0) {
					$ret.= 'Waiting for some events to end before able to delete the user '.$organizer->FullName.'<br />';
					$event = $events->First();						
					$organizer->WillBeDeleted = date('Y-m-d', strtotime($event->End));
					$organizer->write();
				} else { // Found no events so deleting user
					$events = $organizer->Events();
					if ($events->Count() > 0) {
						foreach ($events as $event) {
							$ret.= 'Deleting event '.$event->Title.' start '.$event->Start.'<br />';
							$event->delete();
						}
					}
					
					$okDelete = true; 
					$municipalPermissions = $organizer->MunicipalPermissions();
					if ($municipalPermissions) { // Found out that the user is a MunicipalModerator, checking if he is the only one in a municipal then not deleting
						foreach($municipalPermissions as $municipalPermission) {
							$municipalOrganizers = $municipalPermission->AssociationOrganizers();
							if ($municipalOrganizers->Count() == 1) {
								$ret.= 'User '.$organizer->FullName.' is the only moderator for the municipal '.$municipalPermission->Name.', delete skipped<br />';							
								$okDelete = false;
								break;
							}
						}
					}
					$associationPermissions = $organizer->AssociationPermissions();
					if ($okDelete) {
						$ret.= 'Deleting user '.$organizer->FullName.'<br />';
						$organizer->delete();
					}
					// Checking if all Associations now is empty, then also deleting association
					if ($associationPermissions) {
						foreach ($associationPermissions as $associationPermission) {
							$association = $associationPermission->Association();
							if ($association->AssociationPermissions()->Count() == 0) {
								$ret.= 'Deleting association '.$associtaion->Name.'<br />';
								$associtaion->delete();
							}
						}
					}
				}					
			}
		}	
				
		# Notification about users that havent been confirmed after 48h to moderator
		# Climbing up one level after every 48 hours
		$time1 = strtotime('-48 hours');			
		$time2 = strtotime('-30 days');			
				
		$where = "
				PermissionPublish = 0
				AND ModeratorVerifiedID = 0
				AND EmailVerified < '".date('Y-m-d H:i:s', $time1)."' AND EmailVerified IS NOT NULL
				AND Notification.ID IS NULL				
				AND Member.Created > '".date('Y-m-d H:i:s', $time2)."'			
		";
		
		$organizers = DataObject::get(
			'AssociationOrganizer', 
			$where,
			null, 
			"LEFT JOIN Notification ON AssociationOrganizer.ID = Notification.AboutAssociationOrganizerID
				AND Notification.Created > '".date('Y-m-d H:i:s', $time1)."' AND Notification.Type = 'Unconfirmed'"
		);
		if ($organizers) {		
			foreach ($organizers as $organizer) {					
				$sentOnce = array();			
				$moderatornotifications = $organizer->Notifications("Type = 'Unconfirmed'")->map('ID', 'MemberID');
				if (!is_array($moderatornotifications)) {
					$moderatornotifications = array();
				}			
				$associationPermissions = $organizer->AssociationPermissions();
				if ($associationPermissions) {
					foreach ($associationPermissions as $associationPermission) {
						$association = $associationPermission->Association();						
						$excludeIDs = array_merge(array($organizer->ID), $moderatornotifications);
						$moderators = eCalendarExtension::FindClosestMembers($association, 'parent', 'Moderator', $excludeIDs);
						if ($moderators->Count() > 0) {							
							foreach ($moderators as $moderator) {		
								if (!isset($sentOnce[$moderator->ID])) {
									$this->SendUnconfirmedNoticeModerator($moderator, $organizer);
									$sentOnce[$moderator->ID] = true;
								}
							}
						} else { // Didnt find any moderators so checking if there are Municipal moderators
							$municipal = $association->Municipal();							
							$municipalModerators = $municipal->AssociationOrganizers("AssociationOrganizer.ID NOT IN ('".implode("','", $moderatornotifications)."')");
							if ($municipalModerators->exists()) {
								foreach ($municipalModerators as $municipalModerator) {	
									if (!isset($sentOnce[$municipalModerator->ID])) {
										$this->SendUnconfirmedNoticeModerator($municipalModerator, $organizer);
										$sentOnce[$municipalModerator->ID] = true;
									}
								}
							}
							else {
								// Send to administrators then
								$admins = eCalendarExtension::FindAdministrators();
								if ($admins) {
									foreach ($admins as $admin) {
										if (!isset($sentOnce[$admin->ID])) {
											$this->SendUnconfirmedNoticeModerator($admin, $organizer);
											$sentOnce[$admin->ID] = true;
										}	
									}
								}
							}
						}
					}					
				}
			}
		}
		i18n::set_locale($originalLocale);
		$ret.= '<br />:STOP<br />';
		$ret = str_replace("<br />", "<br />\n", $ret);
		echo $ret;
	}
	
	public function SendDeleteNoticeModerator(&$moderator, &$organizer, $keepAliveLink) {
		$currentLocale = $moderator->Locale; 
		i18n::set_locale($currentLocale);		
		$regpage = DataObject::get_one('RegistrationPage');
		$subject = _t('NotificationTask.INACTIVENOTICEM_SUBJECT', 'Calendar account inactive');
		$body =  sprintf(_t('NotificationTask.INACTIVENOTICEM_BODY1', 'Our system indicates that the account %s has been inactive for a longer time (15 months and one week) and the user himself havent reacted since message last week.'), $organizer->FullName.' - '.$organizer->Email) . "\n\n";
		$body .= _t('NotificationTask.INACTIVENOTICEM_BODY2', 'If the account is not used in another more week it will be permanently removed.') . "\n\n";
		$body .= sprintf(_t('NotificationTask.INACTIVENOTICEM_BODY3', 'Click [url=%s]here[/url] to not delete the account.'), $keepAliveLink);

		$msg = new IM_Message();
		$msg->Subject = $subject;
		$msg->Body = $body;
		$msg->ToID = $moderator->ID;
		$msg->Priority = 'System';
		$msg->send(false);
	}	
		
	public function SendUnconfirmedNoticeModerator(&$moderator, &$organizer) {	
		$currentLocale = $moderator->Locale; 
		i18n::set_locale($currentLocale);
		$checkLink = Director::baseURL().'admin/ecalendar/handleregistrations';
		$subject = _t('NotificationTask.UNCONFIRMEDNOTICEM_SUBJECT', 'User not confirmed');
		$body =  sprintf(_t('NotificationTask.UNCONFIRMEDNOTICM_BODY1', 'Our system indicates that the account %s has not yet been checked.'), $organizer->FullName.' ( '.$organizer->Email.' )')."\n\n";
		$body .= _t('NotificationTask.UNCONFIRMEDNOTICM_BODY2', 'If the account is not checked before 48h the system will try to contact one moderator higher.') . "\n";
		$body .= sprintf(_t('NotificationTask.UNCONFIRMEDNOTICM_BODY3', 'Click [url=%s]this link[/url] to go directly to list of unconfirmed users'), $checkLink) . "\n\n";

		$msg = new IM_Message();
		$msg->Subject = $subject;
		$msg->Body = $body;
		$msg->ToID = $moderator->ID;
		$msg->Priority = 'System';
		$msg->send(false);
		
		$notification = new Notification();
		$notification->Type = 'Unconfirmed';
		$notification->AboutAssociationOrganizerID = $organizer->ID;
		$notification->MemberID = $moderator->ID;		
		$notification->write();
	}
}

?>