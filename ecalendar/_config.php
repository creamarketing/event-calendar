<?php 

// set default timezone to Finnish, to get correct times
date_default_timezone_set('Europe/Helsinki');

Object::add_extension('DataObjectSet', 'DOSPaginationExtension');
Object::add_extension('Image', 'ImageExtension');
Object::add_extension('File', 'FileExtension');

SortableDataObject::add_sortable_classes(array('EventImage', 'EventLink', 'EventFile', 'CalendarLocale'));

Director::addRules(100, array('EventService/$Action' => 'EventService'));
DialogDataObjectManager::$defaultItemSpecificPermissions = true;
DialogDataObjectManager::$emulateIE7_inPopup = false;
DialogDataObjectManager::$preventNestedLoops = true;
Object::add_extension('IM_Message', 'IM_MessageExtension');
Object::add_extension('IM_Controller', 'IM_ControllerExtension');
IM_Controller::$default_email_address = 'no-reply@yourdomain';
Event::$NetTicket_EmailAddress = '';
Email::setAdminEmail('no-reply@yourdomain');
Object::add_extension('Member', 'Member_AssociationOrganizer');

Object::add_extension('Event', 'LogEntryDecorator');
Object::add_extension('Association', 'LogEntryDecorator');
Object::add_extension('AssociationOrganizer', 'LogEntryDecorator');
Object::add_extension('AssociationPermission', 'LogEntryDecorator');

IM_Controller::$default_email_template = 'EC_IM_NotificationEmail';

Object::useCustomClass('MemberLoginForm', 'CalendarLoginForm');
Object::useCustomClass('Member_ForgotPasswordEmail', 'AssociationOrganizer_ForgotPasswordEmail');
Director::addRules(100, array('Security//$Action/$ID/$OtherID' => 'CalendarSecurity'));

Director::addRules(50, array('SecureLinks//$Action/$ID/$OtherID/$OtherAction' => 'SecureLinks'));

?>