// function definitions
function RepeatTypeChanged(event, ui) {
	var typeID = jQuery(ui.item.option).val();
	if (typeID == '') {
		jQuery('#DialogDataObjectManager_Popup_AddForm_RepeatEachLabel').html(ss.i18n._t('Event.REPEATAFTER_DAYS', 'days'));
		jQuery('#DialogDataObjectManager_Popup_AddForm_RepeatDays').parent().hide();
	}
	else if (typeID == 'w') {
		jQuery('#DialogDataObjectManager_Popup_AddForm_RepeatEachLabel').html(ss.i18n._t('Event.REPEATAFTER_WEEKS', 'weeks'));
		jQuery('#DialogDataObjectManager_Popup_AddForm_RepeatDays').parent().show();
	}
	else if (typeID == 'm') {
		jQuery('#DialogDataObjectManager_Popup_AddForm_RepeatEachLabel').html(ss.i18n._t('Event.REPEATAFTER_MONTHS', 'months'));
		jQuery('#DialogDataObjectManager_Popup_AddForm_RepeatDays').parent().hide();
	}
	jQuery('#DialogDataObjectManager_Popup_AddForm_RepeatType').val(typeID);
	jQuery('#DialogDataObjectManager_Popup_AddForm_RepeatTypeText').val(ui.item.value);
	
	top.SetIframeHeight();
	UpdateSummary();
}

function RepeatEachChanged(event, ui){
	jQuery('#DialogDataObjectManager_Popup_AddForm_RepeatEach').val(jQuery(ui.item.option).val());
	jQuery('#DialogDataObjectManager_Popup_AddForm_RepeatEachText').val(ui.item.value);
	UpdateSummary();
}

function UpdateSummary() {
	var each = '';
	var repeat = jQuery('#DialogDataObjectManager_Popup_AddForm_RepeatEach').val();
	if (repeat == '') {
		each = ss.i18n._t('Event.REPEATEVERY', 'every') + ' ';
	}
	else if (repeat == 2) {
		each = ss.i18n._t('Event.REPEATEVERYSECOND', 'every second') + ' ';
	}
	else {
		each = ' ' + ss.i18n._t('Event.REPEATEVERYEVERY', 'every') + ' ' + repeat + '' + ss.i18n._t('Event.REPEATEVERYTH', ':th ');
	}
	
	var typeID = jQuery('#DialogDataObjectManager_Popup_AddForm_RepeatType').val();
	if (typeID == '') {
		each += ss.i18n._t('Event.DAY', 'day') + ', ';
	}
	else if (typeID == 'w') {
		each += ss.i18n._t('Event.WEEK', 'week') + ', ';
		jQuery('#DialogDataObjectManager_Popup_AddForm_RepeatDays input').each(function() {
			if (jQuery(this).attr('checked')) {
				each += jQuery(this).siblings('label').html() + ', ';
			}
		});
	}
	else if (typeID == 'm') {
		each += ss.i18n._t('Event.MONTH', 'month') + ', ';
	}
	
	var stop = '';
	if (jQuery('#DialogDataObjectManager_Popup_AddForm_RepeatStop_0').attr('checked')) {
		stop = '' + jQuery('#DialogDataObjectManager_Popup_AddForm_RepeatTimes').val() + ' ' + ss.i18n._t('Event.TIMES', 'times') ;
	}
	else {
		stop = ss.i18n._t('Event.REPEATUNTIL', 'until') + ' ' + jQuery('#DialogDataObjectManager_Popup_AddForm_RepeatEndDay').val();
	}
	
	var start = ss.i18n._t('Event.REPEATFROM', 'from') + ' ' + jQuery('#CurrentSelectedDate').html() + ', ';
	
	jQuery('#RepeatSummary').html(each + start + stop);
}

function SetRepeatOptions(start) {
	var startDate = new Date(start.getTime()); 
	var day = startDate.getDay();
	// back-end treats Sunday as day 7, but javascript Date treats Sunday as day 0
	if (day == 0) {
		day = 7;
	}
	
	jQuery('#DialogDataObjectManager_Popup_AddForm_RepeatDays input').attr('checked', false);
	jQuery('#DialogDataObjectManager_Popup_AddForm_RepeatDays_' + day).attr('checked', true);
	UpdateSummary();
	jQuery('#CheckResult').html('');
}

jQuery(document).bind('dialogLoaded', function() {
	// reorder controls
	jQuery('#DialogDataObjectManager_Popup_AddForm_RepeatDays').parent().hide();
	
	jQuery('#DialogDataObjectManager_Popup_AddForm_RepeatEachLabel').parent().addClass('inner').appendTo(jQuery('#DialogDataObjectManager_Popup_AddForm_RepeatEach').parent());
	
	jQuery('#DialogDataObjectManager_Popup_AddForm_RepeatTimes').parent().addClass('inner').appendTo(jQuery('#DialogDataObjectManager_Popup_AddForm_RepeatStop_0').parent());
	jQuery('#DialogDataObjectManager_Popup_AddForm_RepeatTimesLabel').parent().addClass('inner').appendTo(jQuery('#DialogDataObjectManager_Popup_AddForm_RepeatStop_0').parent());
	
	jQuery('#RepeatEndDay').appendTo(jQuery('#DialogDataObjectManager_Popup_AddForm_RepeatStop_1').parent());
	
	// set handlers
	jQuery('#DialogDataObjectManager_Popup_AddForm_Repeat').change(function() {
		if (jQuery('#DialogDataObjectManager_Popup_AddForm_Repeat').attr('checked')) {
			jQuery('button[name=AddSingleDate]').hide();
			jQuery('#RepeatGroup').show(500, function() { top.SetIframeHeight(); });
			UpdateSummary();
		}
		else {
			jQuery('button[name=AddSingleDate]').show();
			jQuery('#RepeatGroup').hide(500, function() { top.SetIframeHeight(); });
			jQuery('#RepeatSummary').html('');
		}
	});
	
	jQuery('#RepeatGroup input.radio').change(function() {
		UpdateSummary();
	});
	
	jQuery('#RepeatGroup input.text').change(function() {
		UpdateSummary();
	});
	
	jQuery('#RepeatGroup input.checkbox').change(function() {
		UpdateSummary();
	});
});

