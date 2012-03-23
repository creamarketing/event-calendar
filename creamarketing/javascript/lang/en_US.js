if(typeof(ss) == 'undefined' || typeof(ss.i18n) == 'undefined') {
	if(typeof(console) != 'undefined') console.error('Class ss.i18n not defined');
} else {
	ss.i18n.addDictionary('en_US', {
		'LoggableDataObject.CLOSE': 'Close',
		'LoggableDataObject.DETAILSLOADING': 'Loading details...',
		'LoggableDataObject.DETAILSTITLE': 'Details for log row'
	});
}