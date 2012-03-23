if(typeof(ss) == 'undefined' || typeof(ss.i18n) == 'undefined') {
	if(typeof(console) != 'undefined') console.error('Class ss.i18n not defined');
} else {
	ss.i18n.addDictionary('sv_SE', {
		'IM_Message.NEWMESSAGE': 'Nytt meddelande',
		'IM_Message.SEARCH': 'Sök'
	});
}