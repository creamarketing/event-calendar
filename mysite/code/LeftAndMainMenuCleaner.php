<?php

class LeftAndMainMenuCleaner extends LeftAndMainDecorator {
	function init() { 
		CMSMenu::remove_menu_item('CommentAdmin'); 
		CMSMenu::remove_menu_item('ReportAdmin'); 
		CMSMenu::remove_menu_item('PdfAdmin'); 
		CMSMenu::remove_menu_item('Help'); 
		
		LeftAndMain::setLogo('mysite/images/liito_transparent.png', 'padding:2px 30px 2px 30px; top:1px; line-height:25px; background-position: right center;');
		LeftAndMain::setApplicationName(_t('Branding.NAME', 'Ostrobothnia eventcalendar'), _t('Branding.LOGO_NAME', 'Ostrobothnia eventcalendar'), Director::baseURL());
		LeftAndMain::set_loading_image('mysite/images/loading.png');
	} 
}

?>
