if(typeof(ss) == 'undefined' || typeof(ss.i18n) == 'undefined') {
	if(typeof(console) != 'undefined') console.error('Class ss.i18n not defined');
} else {
	ss.i18n.addDictionary('fi_FI', {
		'Event.REPEATAFTER_DAYS': 'päivää',		
		'Event.REPEATAFTER_WEEKS': 'viikkoa',		
		'Event.REPEATAFTER_MONTHS': 'kuukautta',
		'Event.REPEATEVERY': 'joka',
		'Event.REPEATEVERYSECOND': 'joka toinen',
		'Event.REPEATEVERYEVERY': 'joka',
		'Event.REPEATEVERYTH': ':s ',
		'Event.DAY': 'päivä',
		'Event.WEEK': 'viikko',
		'Event.MONTH': 'kuukaus',
		'Event.TIMES': 'kertaa',
		'Event.REPEATUNTIL': 'loppuu',
		'Event.REPEATFROM': 'alkaa',
		'Event.TIME_SHORT': 'klo',

		'ReportController.LOADING': 'Koostaa raporttia...',
		'ReportController.PRINT': 'Tulosta',
		'ReportController.SAVEPDF': 'Tallenna PDF',
		'ReportController.CLOSE': 'Sulje',

		'ReportEvent.TITLE': 'Ilmoita epäasiallinen tapahtuma',
		'ReportEvent.SUBMIT': 'Lähetä',
		'ReportEvent.CLOSE': 'Sulje',

		'ConfirmDialog.YES': 'Kyllä',
		'ConfirmDialog.NO': 'Ei',
		'ConfirmDialog.TITLE': 'Oletko varma?',
		
		'PermissionRequest.TITLE': 'Käyttöoikeushakemus',
		'PermissionRequest.ACCEPT': 'Haluatko varmasti hyväksyä hakemuksen?',
		'PermissionRequest.REJECT': 'Haluatko varmasti hylätä hakemuksen?',
		
		'AcceptMember.TITLE': 'Käyttäjän vahvistaminen',
		'AcceptMember.ACCEPT': 'Haluatko varmasti hyväksyä käyttäjän ja näin ollen julkaista kaikki kyseisen käyttäjän tapahtumat?',	
		
		'Association.HANDLENEWTITLE' : 'Uusi tapahtumajärjestäjä',
		'Association.CONFIRMACCEPT' : 'Haluatko varmasti hyväksyä tapahtumajärjestäjän?',
		'Association.CONFIRMREJECT' : 'Haluatko varmasti hylätä tapahtumajärjestäjän?',
		
		'UserInviteRequest.TITLE': 'Kutsu tapahtumanjärjestäjälle',
		'UserInviteRequest.ACCEPT': 'Oletko varma, että haluat hyväksyä tämän kutsun?',
		'UserInviteRequest.REJECT': 'Oletko varma, että haluat hylätä tämän kutsun?'
	});
}