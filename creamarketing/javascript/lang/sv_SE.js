if(typeof(ss) == 'undefined' || typeof(ss.i18n) == 'undefined') {
	if(typeof(console) != 'undefined') console.error('Class ss.i18n not defined');
} else {
	ss.i18n.addDictionary('sv_SE', {
		'LoggableDataObject.CLOSE': 'Stäng',
		'LoggableDataObject.DETAILSLOADING': 'Laddar detaljer...',
		'LoggableDataObject.DETAILSTITLE': 'Detaljer för loggrad'
	});
}