function showLocaleDropdown(request, response) {
	AdvancedDropdownField_showWithCheckbox(jQuery('input[name=Locales]').attr('id'), request, response);
}

function selectLocaleDropdown(event, ui) {
	return AdvancedDropdownField_selectCheckbox(jQuery('input[name=Locales]').attr('id'), event, ui);
}

jQuery(function() {
	jQuery('input[name=Locales]').change(function() {
		jQuery('input[name^="Link"]').attr('disabled', 'disabled').parent().hide();
		jQuery('input[name^="Name"]').attr('disabled', 'disabled').parent().hide();

		var langArray = jQuery(this).val().split(',');

		for(var i=0; i<langArray.length;i++) {
			var locale = languageIDToLocaleMapping[langArray[i]];
			jQuery('input[name=Link_' + locale + ']').removeAttr('disabled').parent().show();
			jQuery('input[name=Name_' + locale + ']').removeAttr('disabled').parent().show();
		}
	});
});
