if(typeof(ss) == 'undefined' || typeof(ss.i18n) == 'undefined') {
	if(typeof(console) != 'undefined') console.error('Class ss.i18n not defined');
} else {
	ss.i18n.addDictionary('fi_FI', {
		'LoggableDataObject.CLOSE': 'Sulje',
		'LoggableDataObject.DETAILSLOADING': 'Lataa tiedot...',
		'LoggableDataObject.DETAILSTITLE': 'Logirivin tiedot'
	});
}