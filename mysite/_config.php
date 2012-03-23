<?php

ini_set("display_errors", 1); 
global $project;
$project = 'mysite';

global $databaseConfig;
$databaseConfig = array(
	"type" => 'MySQLDatabase',
	"server" => 'localhost',
	"username" => '', 
	"password" => '', 
	"database" => '',
	"path" => '',
);

global $database;
$database = $databaseConfig['database'];

require_once('conf/ConfigureFromEnv.php');

Security::setDefaultAdmin('admin','YOURSECRETPASSWORD');

Director::set_environment_type("dev");
Director::set_dev_servers(array(
	'localhost',
	'127.0.0.1'
));

MySQLDatabase::set_connection_charset('utf8');

// This line set's the current theme. More themes can be
// downloaded from http://www.silverstripe.org/themes/
SSViewer::set_theme('obotnia');

// enable nested URLs for this site (e.g. page/sub-page/)
SiteTree::enable_nested_urls();
	
i18n::set_date_format('dd.MM.YYYY');
i18n::set_time_format('HH:mm');

// Add support for multilingual content.
Object::add_extension('SiteTree', 'Translatable');
Object::add_extension('SiteConfig', 'Translatable');
// Set the default and allowed locales
Translatable::set_default_locale('sv_SE');
Translatable::set_allowed_locales(array('sv_SE', 'fi_FI', 'en_US'));
// Redefine common locales (first label used in CMS language selector and site language menu, second used in ModuleAdmin for translatable field headers).
i18n::$common_locales = array(
	'sv_SE' => array('På svenska', 'På svenska'),
	'fi_FI' => array('Suomeksi', 'Suomeksi'),
	'en_US' => array('In English', 'In English'),
);

EditableGoogleMapSelectableField::$api_key = ''; // Your Google Maps v2 API KEY

RemoteDataService::$remoteServiceURL = ''; // Should be http://your-hostname/sub-path/EventService/';
