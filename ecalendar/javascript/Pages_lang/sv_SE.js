if(typeof(ss) == 'undefined' || typeof(ss.i18n) == 'undefined') {
	if(typeof(console) != 'undefined') console.error('Class ss.i18n not defined');
} else {
	ss.i18n.addDictionary('sv_SE', {
		'Validation.FIELD_REQUIRED': 'Detta fält är obligatoriskt',
		'Validation.EMAIL_INVALID': 'Den här epostadressen innehåller fel',
		'Validation.HOMEPAGE_INVALID': 'Den här hemsideadressen innehåller fel',
		'Validation.PASSWORD_MISSMATCH': 'Lösenorden matchar inte',
		'Validation.PASSWORD_TOOSHORT': 'Lösenordet är för kort (minimi längd 6 tecken)',
		'Validation.PASSWORD_NEEDSDIGITS': 'Lösenordet måste innehålla åtminstone en siffra',
		'Validation.MUST_ACCEPT_TERMS': 'Du måste acceptera användarvillkoren',
		'Validation.MUST_CREATEORSELECT': 'Du måste skapa en ny arrangör eller välja en existerande ur listan',
		'Validation.ORGANIZER_ATLEASTONENAME': 'Du måste ange åtminstone ett namn'
	});
}