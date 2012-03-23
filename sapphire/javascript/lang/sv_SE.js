if(typeof(ss) == 'undefined' || typeof(ss.i18n) == 'undefined') {
	if(typeof(console) != 'undefined') console.error('Class ss.i18n not defined');
} else {
	ss.i18n.addDictionary('sv_SE', {
		'VALIDATOR.FIELDREQUIRED': 'Vänligen fyll i "%s", fältet är obligatoriskt.',
		'HASMANYFILEFIELD.UPLOADING': 'Laddar up... %s',
		'TABLEFIELD.DELETECONFIRMMESSAGE': 'Är du säkert på att du vill ta bort denna post?',
		'LOADING': 'laddar...',
		'UNIQUEFIELD.SUGGESTED': "Ändrat värdet till '%s' : %s",
		'UNIQUEFIELD.ENTERNEWVALUE': 'Du måste ange ett nytt värde till detta fält',
		'UNIQUEFIELD.CANNOTLEAVEEMPTY': 'Detta fält kan inte lämnas tomt',
		'RESTRICTEDTEXTFIELD.CHARCANTBEUSED': "Tecknet '%s' kan inte användas i detta fält",
		'UPDATEURL.CONFIRM': 'Vill du att jag ändrar URL:en till:\n\n%s/\n\nVälj Ok för att ändra URL:en, välj Avbryt för att behålla:\n\n%s',
		'FILEIFRAMEFIELD.DELETEFILE': 'Radera fil',
		'FILEIFRAMEFIELD.UNATTACHFILE': 'Av-bifoga fil',
		'FILEIFRAMEFIELD.DELETEIMAGE': 'Radera bild',
		'FILEIFRAMEFIELD.CONFIRMDELETE': 'Är du säker på att du vill ta bort denna fil?'
	});
}
