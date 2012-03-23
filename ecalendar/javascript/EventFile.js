function showLocaleDropdown(request, response) {
	AdvancedDropdownField_showWithCheckbox(jQuery('input[name=Locales]').attr('id'), request, response);
}

function selectLocaleDropdown(event, ui) {
	return AdvancedDropdownField_selectCheckbox(jQuery('input[name=Locales]').attr('id'), event, ui);
}

jQuery(function() {
	jQuery('input[name=Locales]').change(function() {
		jQuery('input[name^="Title"]').attr('disabled', 'disabled').parent().hide();

		var langArray = jQuery(this).val().split(',');

		for(var i=0; i<langArray.length;i++) {
			var locale = languageIDToLocaleMapping[langArray[i]];
			jQuery('input[name=Title_' + locale + ']').removeAttr('disabled').parent().show();
		}
	});
});

jQuery(function() {
	jQuery(document).bind('Uploadify_busy', function() {
		top.enableDialogButtons(false);
	});

	jQuery(document).bind('Uploadify_ready', function() {
		top.enableDialogButtons(true);
	});	
	
	jQuery(document).bind('Uploadify_complete', function() {
		jQuery('.button_wrapper').closest('.horizontal_tabs').hide();
	});
	
	jQuery(document).bind('Uploadify_cancel', function() {
		jQuery('.button_wrapper').closest('.horizontal_tabs').show();
		jQuery('.button_wrapper .object_wrapper > input + object').css('visibility', 'hidden').css('visibility', 'visible');
	});	
	
	jQuery(document).bind('Uploadify_delete', function() {
		jQuery('.button_wrapper').closest('.horizontal_tabs').show();
		jQuery('.button_wrapper .object_wrapper > input + object').css('visibility', 'hidden').css('visibility', 'visible');
	});		
	
	if (!jQuery('.no_files').length)
		jQuery('.button_wrapper').closest('.horizontal_tabs').hide();
});

function onBeforeSerialize() {
	var inputs = jQuery('.UploadifyField .inputs'); 
	if (!inputs.find('input[name=FileID]').length) 
		jQuery('<input type="hidden" name="FileID" value="0">').appendTo(inputs);
}
