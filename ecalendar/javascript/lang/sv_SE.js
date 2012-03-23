if(typeof(ss) == 'undefined' || typeof(ss.i18n) == 'undefined') {
	if(typeof(console) != 'undefined') console.error('Class ss.i18n not defined');
} else {
	ss.i18n.addDictionary('sv_SE', {
		'Event.REPEATAFTER_DAYS': 'dagar',		
		'Event.REPEATAFTER_WEEKS': 'veckor',		
		'Event.REPEATAFTER_MONTHS': 'månader',
		'Event.REPEATEVERY': 'varje',
		'Event.REPEATEVERYSECOND': 'varannan',
		'Event.REPEATEVERYEVERY': 'varje',
		'Event.REPEATEVERYTH': ':e ',
		'Event.DAY': 'dag',
		'Event.WEEK': 'vecka',
		'Event.MONTH': 'månad',
		'Event.TIMES': 'gånger',
		'Event.REPEATUNTIL': 'tills',
		'Event.REPEATFROM': 'från',
		'Event.TIME_SHORT': 'kl.',
		
		'ReportController.LOADING': 'Genererar rapport...',
		'ReportController.PRINT': 'Skriv ut',
		'ReportController.SAVEPDF': 'Spara PDF',
		'ReportController.CLOSE': 'Stäng',
		
		'ReportEvent.TITLE': 'Anmäl evenemanget som osakligt',
		'ReportEvent.SUBMIT': 'Skicka',
		'ReportEvent.CLOSE': 'Stäng',
		
		'ConfirmDialog.YES': 'Ja',
		'ConfirmDialog.NO': 'Nej',
		'ConfirmDialog.TITLE': 'Är du säker?',
		
		'PermissionRequest.TITLE': 'Ansökan om rättighet',
		'PermissionRequest.ACCEPT': 'Är du säker på att du vill godkänna denna ansökan?',
		'PermissionRequest.REJECT': 'Är du säker på att du vill förkasta denna ansökan?',
		
		'AcceptMember.TITLE': 'Bekräftelse av användare',
		'AcceptMember.ACCEPT': 'Är du säker på att du vill godkänna användaren samt publicera alla relaterade evenemang?',
		
		'Association.HANDLENEWTITLE' : 'Ny arrangörsförfrågan',
		'Association.CONFIRMACCEPT' : 'Är du säker på att du vill godkänna denna arrangör?',
		'Association.CONFIRMREJECT' : 'Är du säker på att du vill avvisa denna arrangör?',
		
		'UserInviteRequest.TITLE': 'Arrangörsinbjudan',
		'UserInviteRequest.ACCEPT': 'Är du säker på att du vill godkänna denna inbjudan?',
		'UserInviteRequest.REJECT': 'Är du säker på att du vill förkasta denna inbjudan?'

	});
}