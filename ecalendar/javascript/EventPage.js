jQuery(function() {
	jQuery('#EventSearch input[name=EventsFor]').change(function() {
		if (jQuery(this).val() == 'custom') {
			jQuery('#EventSearch form .dateRangeGroup').show();
		}
		else 
			jQuery('#EventSearch form .dateRangeGroup').hide();
	}).trigger('change');
	
	jQuery("#EventSearch form input[name=DateFrom]").datepicker({
		dateFormat: 'dd.mm.yy',
		onSelect: function(dateText, inst) {
			jQuery("#EventSearch form input[name=DateTo]").datepicker('option', 'minDate', dateText);
		}
	});
	jQuery("#EventSearch form input[name=DateTo]").datepicker({
		dateFormat: 'dd.mm.yy',
		onSelect: function(dateText, inst) {
			jQuery("#EventSearch form input[name=DateFrom]").datepicker('option', 'maxDate', dateText);
		}
	});
	jQuery("#EventSearch form input[name=DateTo]").change(function() {
		if (jQuery(this).val() == '')
			jQuery("#EventSearch form input[name=DateFrom]").datepicker('option', 'maxDate', null);
	});
	
	jQuery('#ReportEvent a').click(function() {		
		jQuery.ajax({
			url: jQuery(this).attr('href').replace('reportEvent', 'reportEvent_HTML'),
			dataType: 'html',
			type: 'GET',
			success: function(data) {
				var reportDialog = jQuery('<div id="ReportEventDialog"></div>');
				jQuery('body').append(reportDialog);
				
				var dialogButtons = {};
				dialogButtons[ss.i18n._t('ReportEvent.SUBMIT', 'Report')] = function() {
					jQuery(this).find('form').submit();
				};				
				dialogButtons[ss.i18n._t('ReportEvent.CLOSE', 'Close')] = function() {
					jQuery(this).dialog("close");
				};
				
				jQuery('#ReportEventDialog').html(data);
				
				jQuery('#ReportEventDialog').find('form').ajaxForm({
					success: function(responseText, statusText, xhr, $form) { 
						if (jQuery('<div>' + responseText + '</div>').find('input[name=Result]').val() == 'OK') {
							jQuery('.ui-dialog-buttonset button:first').hide();
							jQuery('#ReportEventDialog').html(responseText);
						}
						else {
							jQuery('#ReportEventDialog form textarea[name=Reason]').effect('highlight', {}, 1000);
						}
					}
				});
				
				reportDialog.dialog({
					title: ss.i18n._t('ReportEvent.TITLE', 'Report event as invalid'),
					buttons: dialogButtons,
					modal: true,
					width: "300px",
					close: function() {
						jQuery(this).remove();
					}
				});				
			},
			error: function() {
				alert('Error getting form');
			}
		});	
				
		return false;
	});
	
	jQuery('a.more-dates-tooltip').each(function() {
		var tooltipContent = jQuery(this).siblings('.more-dates-tooltip-data').html();
		jQuery(this).qtip({
			content: tooltipContent,
			position: { target: 'mouse', adjust: { x: 15 } },
			style: { name: 'light' }
		});
	});
	
	jQuery('.event-show-more-dates a').click(function() {
		jQuery(this).parent().hide();
		jQuery('.event-hide-more-dates').show();
		jQuery('.event-more-dates').show();
		return false;
	});
	
	jQuery('.event-hide-more-dates a').click(function() {
		jQuery(this).parent().hide();
		jQuery('.event-show-more-dates').show();
		jQuery('.event-more-dates').hide();
		return false;
	});	
});

function showCategoryDropdown(request, response) {
	AdvancedDropdownField_showWithCheckbox('Form_EventSearchForm_Categories', request, response);
}

function selectCategoryDropdown(event, ui) {
	return AdvancedDropdownField_selectCheckbox('Form_EventSearchForm_Categories', event, ui);
}

function showMunicipalityDropdown(request, response) {
	AdvancedDropdownField_showWithCheckbox('Form_EventSearchForm_Municipalities', request, response);
}

function selectMunicipalityDropdown(event, ui) {
	return AdvancedDropdownField_selectCheckbox('Form_EventSearchForm_Municipalities', event, ui);
}