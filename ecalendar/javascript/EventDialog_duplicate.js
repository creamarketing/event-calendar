
function showEventLanguages(request, response) {
	AdvancedDropdownField_showWithCheckbox('DialogDataObjectManager_Popup_DuplicateForm_Languages', request, response);
}

function selectEventLanguage(event, ui) {
	return AdvancedDropdownField_selectCheckbox('DialogDataObjectManager_Popup_DuplicateForm_Languages', event, ui);
}

function showEventMunicipals(request, response) {
	AdvancedDropdownField_showWithCheckbox('DialogDataObjectManager_Popup_DuplicateForm_Municipals', request, response);
}

function selectEventMunicipal(event, ui) {
	return AdvancedDropdownField_selectCheckbox('DialogDataObjectManager_Popup_DuplicateForm_Municipals', event, ui);
}

function showEventCategory(request, response) {
	AdvancedDropdownField_showWithCheckbox('DialogDataObjectManager_Popup_DuplicateForm_Categories', request, response);
}

function selectEventCategory(event, ui) {
	return AdvancedDropdownField_selectCheckbox('DialogDataObjectManager_Popup_DuplicateForm_Categories', event, ui);
}

jQuery(function() {
	jQuery("#DialogDataObjectManager_Popup_DuplicateForm #TabSet").bind('tabsselect', function(event, ui) {
		jQuery('.time-edit-cancel').click();
	});	
});

// Auto check when adding images, links, files
/*jQuery(function() {
	jQuery(document).bind('DialogDataObjectManager_Popup_DuplicateForm_EventLinks_refresh', function() {
		setTimeout("jQuery('input[type=\"checkbox\"][name=\"EventLinks[]\"]:not(:checked)').attr('checked', 'checked').each(function() { jQuery(this).triggerHandler('click'); }); ", 100);
	});
	jQuery(document).bind('DialogDataObjectManager_Popup_DuplicateForm_EventImages_refresh', function() {
		setTimeout("jQuery('input[type=\"checkbox\"][name=\"EventImages[]\"]:not(:checked)').attr('checked', 'checked').each(function() { jQuery(this).triggerHandler('click'); }); ", 100);
	});	
	jQuery(document).bind('DialogDataObjectManager_Popup_DuplicateForm_EventFiles_refresh', function() {
		setTimeout("jQuery('input[type=\"checkbox\"][name=\"EventFiles[]\"]:not(:checked)').attr('checked', 'checked').each(function() { jQuery(this).triggerHandler('click'); }); ", 100);
	});		
});*/