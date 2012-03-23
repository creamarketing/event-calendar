function showCategoriesDropdown(request, response) {
	AdvancedDropdownField_showWithCheckbox(jQuery('input[name=Categories]').attr('id'), request, response);
}

function selectCategoriesDropdown(event, ui) {
	return AdvancedDropdownField_selectCheckbox(jQuery('input[name=Categories]').attr('id'), event, ui);
}

function showMunicipalitiesDropdown(request, response) {
	AdvancedDropdownField_showWithCheckbox(jQuery('input[name=Municipalities]').attr('id'), request, response);
}

function selectMunicipalitiesDropdown(event, ui) {
	return AdvancedDropdownField_selectCheckbox(jQuery('input[name=Municipalities]').attr('id'), event, ui);
}

jQuery(function() {
	jQuery('input[name=Categories] + input').bind('onAdvancedDropdownClose', function() {
		if (jQuery('ul.ui-autocomplete.Categories-autocomplete').hasClass('prevent-close'))
			return false;		
		return true;
	});	
	
	jQuery('ul.ui-autocomplete.Categories-autocomplete').live('mouseover', function () {
		jQuery(this).addClass('prevent-close');
	});
	jQuery('ul.ui-autocomplete.Categories-autocomplete').live('mouseout', function () {
		jQuery(this).removeClass('prevent-close');
	});		
	
	jQuery('input[name=Municipalities] + input').bind('onAdvancedDropdownClose', function() {
		if (jQuery('ul.ui-autocomplete.Municipalities-autocomplete').hasClass('prevent-close'))
			return false;		
		return true;
	});	
	
	jQuery('ul.ui-autocomplete.Municipalities-autocomplete').live('mouseover', function () {
		jQuery(this).addClass('prevent-close');
	});
	jQuery('ul.ui-autocomplete.Municipalities-autocomplete').live('mouseout', function () {
		jQuery(this).removeClass('prevent-close');
	});		
});

function ExtraReportButtons() {
	var buttons = {};
	buttons[jQuery('#Form_ReportForm_action_SavePlaintextFile').attr('title')] = function() {
		// create a download token from current timestamp
		var downloadToken = new Date().getTime();
		jQuery('#Form_ReportForm_DownloadToken').val(downloadToken);

		// set an interval function to check for the file download token cookie,
		// if it is set to our download token value it means the request has finnished
		var timeout = 60; // in seconds
		fileDownloadCheckTimer = window.setInterval(function () {
			var cookieValue = jQuery.cookie('fileDownloadToken');
			if (cookieValue == downloadToken) {
				DownloadFinished();
			}
			else if(--timeout == 0) {
				DownloadTimeout();
			}
		}, 1000);		
		
		StartFormAction('SavePlaintextFile');
		
		jQuery('#Form_ReportForm').submit();
	};
	return buttons;
}