
function showEventLanguages(request, response) {
	AdvancedDropdownField_showWithCheckbox('DialogDataObjectManager_Popup_DetailForm_Languages', request, response);	
}

function selectEventLanguage(event, ui) {
	return AdvancedDropdownField_selectCheckbox('DialogDataObjectManager_Popup_DetailForm_Languages', event, ui);
}

function showEventMunicipals(request, response) {
	AdvancedDropdownField_showWithCheckbox('DialogDataObjectManager_Popup_DetailForm_Municipals', request, response);
}

function selectEventMunicipal(event, ui) {
	return AdvancedDropdownField_selectCheckbox('DialogDataObjectManager_Popup_DetailForm_Municipals', event, ui);
}

function showEventCategory(request, response) {
	AdvancedDropdownField_showWithCheckbox('DialogDataObjectManager_Popup_DetailForm_Categories', request, response);
}

function selectEventCategory(event, ui) {
	return AdvancedDropdownField_selectCheckbox('DialogDataObjectManager_Popup_DetailForm_Categories', event, ui);
}