if(typeof(ss) == 'undefined' || typeof(ss.i18n) == 'undefined') {
	if(typeof(console) != 'undefined') console.error('Class ss.i18n not defined');
} else {
	ss.i18n.addDictionary('en_US', {
		'DialogDataObjectManager.SAVE': 'Save',
		'DialogDataObjectManager.CLOSE': 'Close',
		'DialogDataObjectManager.DELETE': 'Delete?',
		'DialogDataObjectManager.SEARCH': 'Search'
	});
}