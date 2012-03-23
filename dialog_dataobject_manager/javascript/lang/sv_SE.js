if(typeof(ss) == 'undefined' || typeof(ss.i18n) == 'undefined') {
	if(typeof(console) != 'undefined') console.error('Class ss.i18n not defined');
} else {
	ss.i18n.addDictionary('sv_SE', {
		'DialogDataObjectManager.SAVE': 'Spara',
		'DialogDataObjectManager.CLOSE': 'Stäng',
		'DialogDataObjectManager.DELETE': 'Radera?',
		'DialogDataObjectManager.SEARCH': 'Sök',
		'DialogDataObjectManager.LOADING': 'Laddar',
		'DialogDataObjectManager.CONTINUE': 'Fortsätt',
		'DialogDataObjectManager.BACK': 'Tillbaka',
		'DialogDataObjectManager.PUBLISH': 'Publicera',
		'DialogDataObjectManager.UNPUBLISH': 'Avpublicera',
		'DialogDataObjectManager.SAVE_UNPUBLISHED': 'Spara som utkast',
		'DialogDataObjectManager.SAVE_PUBLISHED': 'Spara ändringar',
		'DialogDataObjectManager.FIRSTSAVEMSG': 'Sparar för första gången, vänligen vänta.',
		'DialogDataObjectManager.VALIDATIONERROR': 'Data saknas',
		'DialogDataObjectManager.ACTIVATE': 'Aktivera',
		'DialogDataObjectManager.DEACTIVATE': 'Passivera',
		'DialogDataObjectManager.ACCEPT': 'Godkänn',
		'DialogDataObjectManager.REJECT': 'Förkasta'
	});
}