if(typeof(ss) == 'undefined' || typeof(ss.i18n) == 'undefined') {
	if(typeof(console) != 'undefined') console.error('Class ss.i18n not defined');
} else {
	ss.i18n.addDictionary('fi_FI', {
		'DialogDataObjectManager.SAVE': 'Tallenna',
		'DialogDataObjectManager.CLOSE': 'Sulje',
		'DialogDataObjectManager.DELETE': 'Poista?',
		'DialogDataObjectManager.SEARCH': 'Hae',
		'DialogDataObjectManager.LOADING': 'Ladataan',
		'DialogDataObjectManager.CONTINUE': 'Jatka',
		'DialogDataObjectManager.BACK': 'Palaa edelliseen',
		'DialogDataObjectManager.PUBLISH': 'Julkaise',
		'DialogDataObjectManager.UNPUBLISH': 'Poista',
		'DialogDataObjectManager.SAVE_UNPUBLISHED': 'Tallenna luonnos',
		'DialogDataObjectManager.SAVE_PUBLISHED': 'Tallenna muutokset',
		'DialogDataObjectManager.FIRSTSAVEMSG': 'Tallentaa ensimmäistä kertaa. Ole hyvä ja odota.',
		'DialogDataObjectManager.VALIDATIONERROR': 'Tietoja puuttuu',
		'DialogDataObjectManager.ACTIVATE': 'Aktivoida',
		'DialogDataObjectManager.DEACTIVATE': 'Passivoida',
		'DialogDataObjectManager.ACCEPT': 'Hyväksy',
		'DialogDataObjectManager.REJECT': 'Hylkää'		
	});
}