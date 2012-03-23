<?php
class Page extends SiteTree {

	public static $db = array(
	);

	public static $has_one = array(
	);

}
class Page_Controller extends ContentController {

	/**
	 * An array of actions that can be accessed via a request. Each array element should be an action name, and the
	 * permissions or conditions required to allow the user to access it.
	 *
	 * <code>
	 * array (
	 *     'action', // anyone can access this action
	 *     'action' => true, // same as above
	 *     'action' => 'ADMIN', // you must have ADMIN permissions to access this action
	 *     'action' => '->checkAction' // you can only access this action if $this->checkAction() returns true
	 * );
	 * </code>
	 *
	 * @var array
	 */
	public static $allowed_actions = array (
	);

	protected $useDefaultLayout = true;
	
	public function init() {
		parent::init();

		if($this->dataRecord->hasExtension('Translatable')) {
			i18n::set_locale($this->dataRecord->Locale);
			// Set the locale so we can use FormatI18N to format dates in templates etc.
			setlocale(LC_ALL, $this->dataRecord->Locale.'.utf8');
			Requirements::insertHeadTags('<meta http-equiv="Content-language" content="' . $this->dataRecord->Locale . '" />');
		}
		// Note: you should use SS template require tags inside your templates 
		// instead of putting Requirements calls here.  However these are 
		// included so that our older themes still work
		Requirements::themedCSS('layout'); 
		Requirements::themedCSS('typography'); 
		Requirements::themedCSS('form');
		
		if (isset($_GET['clear_theme']))
			Session::set('ThemeFromEventPageID', 0);
	}
	
	public function renderWithThemePage($action = 'index') {
		$themePageID = (int)Session::get('ThemeFromEventPageID');
		if ($themePageID) {
			$themePage = DataObject::get_by_id('EventPageCustomizable', $themePageID);
			if ($themePage) {				
				$this->useDefaultLayout = false;
				
				$themeData = $themePage->getThemeData();
				return $this->customise($themeData)->renderWith(array($this->CurrentPage()->ClassName . '_' . $action, $this->CurrentPage()->ClassName, 'Page', 'Page'));
			}
		}
		return $this;
	}
	
	public function index() {
		return $this->renderWithThemePage();
	}
	
	public function getUseDefaultLayout() {
		return $this->useDefaultLayout;
	}
	
	public function Menu($level) {
		$menu = parent::Menu($level);
		$themePageID = (int)Session::get('ThemeFromEventPageID');
		
		if ($menu->exists()) {
			foreach ($menu as $menuItem) {
				if ($menuItem->ClassName == 'EventPage' && $themePageID) {
					$themePage = DataObject::get_by_id('EventPageCustomizable', $themePageID);
					if ($themePage)
						$menuItem->setField('SpecialLink', $themePage->Link());
					else
						$menuItem->setField('SpecialLink', false);
				}
				else {
					$menuItem->setField('SpecialLink', false);
				}
			}
		}
		return $menu;
	}
}