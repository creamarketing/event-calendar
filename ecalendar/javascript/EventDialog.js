jQuery(function() {
	jQuery('input[name=Languages] + input').bind('onAdvancedDropdownClose', function() {
		if (jQuery('ul.ui-autocomplete.Languages-autocomplete').hasClass('prevent-close'))
			return false;		
		return true;
	});
	
	jQuery('input[name=Categories] + input').bind('onAdvancedDropdownClose', function() {
		if (jQuery('ul.ui-autocomplete.Categories-autocomplete').hasClass('prevent-close'))
			return false;		
		return true;
	});	
	
	jQuery('ul.ui-autocomplete.Languages-autocomplete').live('mouseover', function () {
		jQuery(this).addClass('prevent-close');
	});
	jQuery('ul.ui-autocomplete.Languages-autocomplete').live('mouseout', function () {
		jQuery(this).removeClass('prevent-close');
	});		
	
	jQuery('ul.ui-autocomplete.Categories-autocomplete').live('mouseover', function () {
		jQuery(this).addClass('prevent-close');
	});
	jQuery('ul.ui-autocomplete.Categories-autocomplete').live('mouseout', function () {
		jQuery(this).removeClass('prevent-close');
	});		
	
	jQuery('input[name=MunicipalID]').change(function() {
		// MunicipalIDText doesnt change here.. so trigger a bit later
		if (jQuery(this).val() != '')
			setTimeout("jQuery('input[name=GoogleMAP]').val(jQuery('input[name=MunicipalID]').next().val() + ', Finland'); jQuery('input.googleMapAddressSubmit').click();", 150);
	});
	
	jQuery("#TabSet").bind('tabsshow', function(event, ui) {
		if (ui.panel.id == 'TabSet_PreviewTab')
			updatePreviewTab();
	});		
	
	jQuery('select[name=PreviewLanguage]').change(function() {
		updatePreviewTab();
	});
	
	jQuery('input[name=PriceType]').change(function() {
		if (jQuery('input[name=PriceType]:checked').val() == 'NotFree') {
			jQuery('#PriceType').next().show();
			jQuery('textarea[name^="PriceText"]:visible').removeAttr('disabled');
		}
		else {
			jQuery('#PriceType').next().hide();
			jQuery('textarea[name^="PriceText"]:visible').attr('disabled', 'disabled');
		}
		
		top.SetIframeHeight();
	}).trigger('change');
	
	jQuery('input[name="NetTicket_PublishTo"]').change(function() {
		if (jQuery(this).is(':checked')) {
			jQuery('#tab-PublishingSubTabs_NetTicketTab').parent().show();
			jQuery('#PublishingSubTabs_NetTicketTab').show();
			
			updateEmptyFieldsFor_NetTicketGroup();
		}
		else {
			jQuery('#tab-PublishingSubTabs_NetTicketTab').parent().hide();
			jQuery('#PublishingSubTabs_NetTicketTab').hide();
		}
		
		top.SetIframeHeight();
	}).trigger('change');	
	
	jQuery('input[name="Vasabladet_PublishTo"]').change(function() {
		if (jQuery(this).is(':checked')) {
			jQuery('#tab-PublishingSubTabs_VasabladetTab').parent().show();
			jQuery('#PublishingSubTabs_VasabladetTab').show();
			
			updateEmptyFieldsFor_VasabladetGroup();
		}
		else {
			jQuery('#tab-PublishingSubTabs_VasabladetTab').parent().hide();
			jQuery('#PublishingSubTabs_VasabladetTab').hide();
		}
		
		top.SetIframeHeight();
	}).trigger('change');
	
	jQuery('select[name="Vasabladet_Category"]').change(function() {
		updateVasabladetSubCategories();
		if (jQuery(this).val() != '0') {
			jQuery(this).nextAll('select, label').show();
		}
		else 
			jQuery(this).nextAll().hide();
	}).trigger('change');
	
	jQuery('input[name="Vasabladet_AdditionalInfo"]').change(function() {
		if (jQuery(this).is(':checked')) {
			jQuery(this).parent().nextAll().show();
		}
		else {
			jQuery(this).parent().nextAll().hide();
		}
		
		top.SetIframeHeight();
	}).trigger('change');
	
	jQuery('input[name="Pohjalainen_PublishTo"]').change(function() {
		if (jQuery(this).is(':checked')) {
			jQuery('#tab-PublishingSubTabs_PohjalainenTab').parent().show();
			jQuery('#PublishingSubTabs_PohjalainenTab').show();
			
			updateEmptyFieldsFor_PohjalainenGroup();
		}
		else {
			jQuery('#tab-PublishingSubTabs_PohjalainenTab').parent().hide();
			jQuery('#PublishingSubTabs_PohjalainenTab').hide();
		}
		
		top.SetIframeHeight();
	}).trigger('change');	
	
	jQuery('input[name="Pohjalainen_HasText"]').change(function() {
		if (jQuery(this).is(':checked')) {
			jQuery('#PohjalainenLongDescriptionGroup').show();
		}
		else {
			jQuery('#PohjalainenLongDescriptionGroup').hide();
		}
		
		top.SetIframeHeight();
	}).trigger('change');
		
	// languages
	jQuery("input[name=Languages]").change(function () {
		jQuery('body').find("div.fieldgroup.translationGroup").contents().find("div.fieldgroupField").hide();

		jQuery("input[id*='Form_Title_']").attr('disabled', 'disabled');
		jQuery("input[id*='Form_EventTextShort_']").attr('disabled', 'disabled');
		jQuery("textarea[id*='Form_EventText_']").attr('disabled', 'disabled');
		jQuery("input[id*='Form_Place_']").attr('disabled', 'disabled');
		jQuery("textarea[id*='Form_PriceText_']").attr('disabled', 'disabled');

		var languages = jQuery(this).val();
		var language_array = languages.split(',');
		
		for(var i=0;i<language_array.length;i++) {
			var langLocale = languageMapping[language_array[i]];
			jQuery("input[id$='Form_Title_" + langLocale + "']").removeAttr('disabled').parent().show();
			jQuery("input[id$='Form_EventTextShort_" + langLocale + "']").removeAttr('disabled').parent().show();
			jQuery("textarea[id$='Form_EventText_" + langLocale + "']").removeAttr('disabled').parent().show();
			jQuery("input[id$='Form_Place_" + langLocale + "']").removeAttr('disabled').parent().show();
			jQuery("textarea[id$='Form_PriceText_" + langLocale + "']").removeAttr('disabled').parent().show();
		}
		
		top.SetIframeHeight();		
	}).trigger('change');
		
	// Filtering of valid associations
	jQuery('input[name=AssociationID]').change(function() {
		var users = AssociationsUsers[(parseInt(jQuery(this).val()) || '0')];
		if (typeof users != 'undefined') {
			jQuery('input[name=OrganizerID]+input+select > option').each(function() {
				if (jQuery.inArray(jQuery(this).val(), users) == -1 && jQuery(this).val())
					jQuery(this).attr('disabled', true);
				else
					jQuery(this).removeAttr('disabled');	
			});
			
			// Selected user not in list?
			if (jQuery.inArray(jQuery('input[name=OrganizerID]').val(), users) == -1) {
				var noUser = jQuery('input[name=OrganizerID]+input+select > option').first();
				jQuery('input[name=OrganizerID]').val(noUser.val());
				jQuery('input[name=OrganizerID]+input').val(noUser.text());
			}
		}
		else {
			jQuery('input[name=OrganizerID]+input+select > option').each(function() {
				jQuery(this).removeAttr('disabled');
			});
		}
	}).trigger('change');
	
	// Filtering of valid users
	jQuery('input[name=OrganizerID]').change(function() {
		var userID = jQuery(this).val();
		// Enable the associations our current user is member of
		jQuery('input[name=AssociationID]+input+select > option').each(function() {
			if (jQuery(this).val() && userID) {
				var users = AssociationsUsers[jQuery(this).val()];
				if (jQuery.inArray(userID, users) != -1)
					jQuery(this).removeAttr('disabled');
				else
					jQuery(this).attr('disabled', true);
			}
			else 
				jQuery(this).removeAttr('disabled');
		});
	}).trigger('change');
	
	// Some char limiting
	jQuery('input[name^="EventTextShort"]').attr('maxlength', '120');
	jQuery('textarea[name^="PriceText"]').attr('maxlength', '140');	
	jQuery('input[name=Vasabladet_ShortText]').attr('maxlength', '120');
	jQuery('input[name=Pohjalainen_ShortText]').attr('maxlength', '200');
		
	jQuery('input[name^="EventTextShort"], textarea[name^="PriceText"], input[name=Vasabladet_ShortText], input[name=Pohjalainen_ShortText]').each(function() {
		jQuery(this).prev('label').html(jQuery(this).prev('label').html() + '<span class="charCounter" id="' + jQuery(this).attr('id') + '_charCounter"><span>');
		jQuery(this).charlimit({
			limit: jQuery(this).attr('maxlength'),
			id_result: (jQuery(this).attr('id') + '_charCounter')
		});
	});	
});

function updateVasabladetSubCategories() {
	var categoryID = jQuery('select[name="Vasabladet_Category"]').val();
	
	if (typeof vasabladetSubCategories[categoryID] != 'undefined') {
		jQuery('select[name="Vasabladet_SubCategory"] option').remove();
		var firstKey = null;
		for (var key in vasabladetSubCategories[categoryID]) {
			if (parseInt(key)) {			
				var value = vasabladetSubCategories[categoryID][key];
				jQuery('select[name="Vasabladet_SubCategory"]').append('<option value="' + key + '">' + value + '</option>');
				if (firstKey == null)
					firstKey = key;
			}
		}
		jQuery('select[name="Vasabladet_SubCategory"]').val(firstKey).removeAttr('disabled');
		
		if (typeof vasabladetSubCategories[categoryID]['infoText'] != 'undefined') {
			jQuery('#Vasabladet_InfoText').html(vasabladetSubCategories[categoryID]['infoText']);
			jQuery('#Vasabladet_InfoText').show();
		}		
		else {
			jQuery('#Vasabladet_InfoText').html('');
			jQuery('#Vasabladet_InfoText').hide();
		}
	}
	else {
		jQuery('select[name="Vasabladet_SubCategory"] option').remove();
		jQuery('select[name="Vasabladet_SubCategory"]').append('<option value="0"></option>');
		jQuery('select[name="Vasabladet_SubCategory"]').val('0').attr('disabled', 'disabled');

		jQuery('#Vasabladet_InfoText').html('');
		jQuery('#Vasabladet_InfoText').hide();
	}
}

function updateEmptyFieldsFor_VasabladetGroup() {
	// Auto select municipality if possible
	if (jQuery('select[name="Vasabladet_Municipality"]').val() == '0') {
		var municipalityText = jQuery('input[name="MunicipalID"] + input').val();
		jQuery('select[name="Vasabladet_Municipality"] option').each(function(index) {
			if (jQuery(this).text() == municipalityText) {
				jQuery('select[name="Vasabladet_Municipality"]').val(jQuery(this).val());
				return false;
			}
		});
	}

	// Get the short description, sv_SE locale if possible
	if (!jQuery('input[name="Vasabladet_ShortText"]').val().length) {
		var shortText_sv_SE = jQuery('input[name="EventTextShort_sv_SE"]:not(:disabled)').val() || null;
		var firstShortText = jQuery('input[name^="EventTextShort_"]:not(:disabled):first').val() || '';

		if (shortText_sv_SE)
			jQuery('input[name="Vasabladet_ShortText"]').val(shortText_sv_SE);
		else 
			jQuery('input[name="Vasabladet_ShortText"]').val(firstShortText);
	}

	// Get the description, sv_SE locale if possible
	if (!jQuery('textarea[name="Vasabladet_Text"]').val().length) {
		var text_sv_SE = jQuery('textarea[name="EventText_sv_SE"]:not(:disabled)').val() || null;
		var firstText = jQuery('textarea[name^="EventText_"]:not(:disabled):first').val() || '';

		if (text_sv_SE)
			jQuery('textarea[name="Vasabladet_Text"]').val(text_sv_SE);
		else 
			jQuery('textarea[name="Vasabladet_Text"]').val(firstText);
	}

	// Get the organizer
	if (!jQuery('input[name="Vasabladet_Organizer"]').val().length)
		jQuery('input[name="Vasabladet_Organizer"]').val(jQuery('input[name=AssociationID] + input').val());

	// Get the event homepage
	if (!jQuery('input[name="Vasabladet_URL"]').val().length)
		jQuery('input[name="Vasabladet_URL"]').val(jQuery('input[name=Homepage]').val());			

	// Get the event address
	if (!jQuery('input[name="Vasabladet_Address"]').val().length) {
		var placeText_sv_SE = jQuery('input[name="Place_sv_SE"]:not(:disabled)').val() || null;
		var firstPlaceText = jQuery('input[name^="Place_"]:not(:disabled):first').val() || '';

		if (placeText_sv_SE)
			jQuery('input[name="Vasabladet_Address"]').val(placeText_sv_SE + ' - ' + jQuery('input[name=GoogleMAP]').val());
		else
			jQuery('input[name="Vasabladet_Address"]').val(firstPlaceText + ' - ' + jQuery('input[name=GoogleMAP]').val());				
	} 	
}

function updateEmptyFieldsFor_PohjalainenGroup() {
	// Auto select municipality if possible
	if (jQuery('select[name="Pohjalainen_MunicipalityZIP"]').val() == '') {
		var municipalityText = jQuery('input[name="MunicipalID"] + input').val();
		jQuery('select[name="Pohjalainen_MunicipalityZIP"] option').each(function(index) {
			if (jQuery(this).text() == municipalityText) {
				jQuery('select[name="Pohjalainen_MunicipalityZIP"]').val(jQuery(this).val());
				return false;
			}
		});
	}

	// Get the title, fi_FI locale if possible
	if (!jQuery('input[name="Pohjalainen_Title"]').val().length) {
		var title_fi_FI = jQuery('input[name="Title_fi_FI"]:not(:disabled)').val() || null;
		var firstTitleText = jQuery('input[name^="Title_"]:not(:disabled):first').val() || '';

		if (title_fi_FI)
			jQuery('input[name="Pohjalainen_Title"]').val(title_fi_FI);
		else 
			jQuery('input[name="Pohjalainen_Title"]').val(firstTitleText);
	}

	// Get the short description, fi_FI locale if possible
	if (!jQuery('input[name="Pohjalainen_ShortText"]').val().length) {
		var shortText_fi_FI = jQuery('input[name="EventTextShort_fi_FI"]:not(:disabled)').val() || null;
		var firstShortText = jQuery('input[name^="EventTextShort_"]:not(:disabled):first').val() || '';

		if (shortText_fi_FI)
			jQuery('input[name="Pohjalainen_ShortText"]').val(shortText_fi_FI);
		else 
			jQuery('input[name="Pohjalainen_ShortText"]').val(firstShortText);
	}

	// Get the description, fi_FI locale if possible
	if (!jQuery('textarea[name="Pohjalainen_Text"]').val().length) {
		var text_fi_FI = jQuery('textarea[name="EventText_fi_FI"]:not(:disabled)').val() || null;
		var firstText = jQuery('textarea[name^="EventText_"]:not(:disabled):first').val() || '';

		if (text_fi_FI)
			jQuery('textarea[name="Pohjalainen_Text"]').val(text_fi_FI);
		else 
			jQuery('textarea[name="Pohjalainen_Text"]').val(firstText);
	}	
	
	// Get the event homepage
	if (!jQuery('input[name="Pohjalainen_URL"]').val().length)
		jQuery('input[name="Pohjalainen_URL"]').val(jQuery('input[name=Homepage]').val());				

	// Get the event address
	if (!jQuery('input[name="Pohjalainen_Address"]').val().length) 
		jQuery('input[name="Pohjalainen_Address"]').val(jQuery('input[name=GoogleMAP]').val());

	// Get the event place
	if (!jQuery('input[name="Pohjalainen_Place"]').val().length) {
		var placeText_fi_FI = jQuery('input[name="Place_fi_FI"]:not(:disabled)').val() || null;
		var firstPlaceText = jQuery('input[name^="Place_"]:not(:disabled):first').val() || '';

		if (placeText_fi_FI)
			jQuery('input[name="Pohjalainen_Place"]').val(placeText_fi_FI);
		else
			jQuery('input[name="Pohjalainen_Place"]').val(firstPlaceText);
	} 
}

function updateEmptyFieldsFor_NetTicketGroup() {
	
}

function updatePreviewTab() {
	
	jQuery('#PreviewLoader').show(); 
	jQuery('#PreviewIFrame').hide();	
	
	var formData = jQuery("#PreviewIFrame").closest('form').formSerialize();
	
	jQuery.ajax({
		url: 'admin/ecalendar/eventPreview',
		data: formData,
		dataType: 'html',
		type: 'POST',
		success: function(data) {
			var iframeContent = jQuery('#PreviewIFrame')[0].contentDocument;
			if (!iframeContent) 
				iframeContent = jQuery('#PreviewIFrame')[0].contentWindow.document;
			iframeContent.open();
			iframeContent.write(data);
			iframeContent.close();
			setTimeout("jQuery('#PreviewIFrame').attr('height', jQuery('#PreviewIFrame').contents().find('body').height() + 100); top.SetIframeHeight();", 100);
			setTimeout("jQuery('#PreviewIFrame').attr('height', jQuery('#PreviewIFrame').contents().find('body').height() + 100); top.SetIframeHeight();", 1000); // Slow loading of images?
			setTimeout("jQuery('#PreviewIFrame').attr('height', jQuery('#PreviewIFrame').contents().find('body').height() + 100); top.SetIframeHeight();", 5000); // Slow loading of images?

			jQuery('#PreviewLoader').hide(); 
			jQuery('#PreviewIFrame').show();
		},
		error: function() {
			var iframeContent = jQuery('#PreviewIFrame')[0].contentDocument;
			if (!iframeContent) 
				iframeContent = jQuery('#PreviewIFrame')[0].contentWindow.document;
			iframeContent.open();
			iframeContent.write('Error');
			iframeContent.close();

			setTimeout("jQuery('#PreviewIFrame').attr('height', jQuery('#PreviewIFrame').contents().find('html').height() + 100); top.SetIframeHeight();", 100);
			jQuery('#PreviewLoader').hide(); 
			jQuery('#PreviewIFrame').show();
		}
	});	
}

jQuery(document).bind('dialogLoaded', function() {
	var form = jQuery("#PreviewIFrame").closest('form');
	form.bind('beforeDialogDataObjectManagerPublish', function(e, saveFunction) {
		
		var content = form.find('input[name=InfoConfirmPublish]').val();
		
		var associationID = parseInt(form.find('input[name=AssociationID]').val()) || '0';
		if (associationID != '0' && AssociationsPublishable[associationID] == '0')
			content += '<br/><br/>' + form.find('input[name=AssociationPublishDirectlyText]').val();
		
		if (form.find('input[name=UserCanPublishDirectly]').val() != '1') 
			content += '<br/><br/>' + form.find('input[name=UserCanPublishDirectlyText]').val();
		
		top.ConfirmEventPublish('<p><span class="ui-icon ui-icon-alert" style="float:left; margin:0 7px 0px 0;"></span>' + content + '</p>', saveFunction);
		return false;
	});
	
	form.bind('beforeDialogDataObjectManagerUnpublish', function(e, saveFunction) {
		
		var content = form.find('input[name=InfoConfirmUnpublish]').val();

		top.ConfirmEventPublish('<p><span class="ui-icon ui-icon-alert" style="float:left; margin:0 7px 0px 0;"></span>' + content + '</p>', saveFunction);
		return false;
	});	
});	