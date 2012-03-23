if(typeof(ss) == 'undefined' || typeof(ss.i18n) == 'undefined') {
	if(typeof(console) != 'undefined') console.error('Class ss.i18n not defined');
} else {
	ss.i18n.addDictionary('fi_FI', {
		'Validation.FIELD_REQUIRED': 'Tämä kenttä on pakollinen',
		'Validation.EMAIL_INVALID': 'Tämä sähköpostiosoite on virheellinen',
		'Validation.HOMEPAGE_INVALID': 'Tämä kotisivuosoite on virheellinen',
		'Validation.PASSWORD_MISSMATCH': 'Salasanat eivät täsmää',
		'Validation.PASSWORD_TOOSHORT': 'Salasana on liian lyhyt (vähimmäispituus 6 merkkiä)',
		'Validation.PASSWORD_NEEDSDIGITS': 'Salasanassa on oltava vähintään yksi numero',
		'Validation.MUST_ACCEPT_TERMS': 'Sinun tulee hyväksyä käyttöehdot',
		'Validation.MUST_CREATEORSELECT': 'Sinun tulee luoda uusi tapahtumajärjestäjä tai valita jo olemassa oleva listasta',
		'Validation.ORGANIZER_ATLEASTONENAME': 'Sinun täytyy antaa ainakin tapahtumajärjestäjän nimi'
	});
}