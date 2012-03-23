// function definitions
function RepeatTypeChanged(event, ui) {
	var typeID = jQuery(ui.item.option).val();	
	if (typeID == '') {
		jQuery('label[id$="RepeatEachLabel"]').html(ss.i18n._t('Event.REPEATAFTER_DAYS', 'days'));
		jQuery('ul[id$="RepeatDays"]').parent().hide();
	}
	else if (typeID == 'w') {
		jQuery('label[id$="RepeatEachLabel"]').html(ss.i18n._t('Event.REPEATAFTER_WEEKS', 'weeks'));
		jQuery('ul[id$="RepeatDays"]').parent().show();
	}
	else if (typeID == 'm') {
		jQuery('label[id$="RepeatEachLabel"]').html(ss.i18n._t('Event.REPEATAFTER_MONTHS', 'months'));
		jQuery('ul[id$="RepeatDays"]').parent().hide();
	}
	jQuery('input[name=RepeatType]').val(typeID);
	jQuery('input[name=RepeatTypeText]').val(ui.item.value);
	
	top.SetIframeHeight();
	UpdateSummary();
}

function RepeatEachChanged(event, ui){
	jQuery('input[name=RepeatEach]').val(jQuery(ui.item.option).val());
	jQuery('input[id$="RepeatEachText"]').val(ui.item.value);
	UpdateSummary();
}

function UpdateSummary() {
	var each = '';
	var repeat = jQuery('input[name=RepeatEach]').val();
	if (repeat == '') {
		each = ss.i18n._t('Event.REPEATEVERY', 'every') + ' ';
	}
	else if (repeat == 2) {
		each = ss.i18n._t('Event.REPEATEVERYSECOND', 'every second') + ' ';
	}
	else {
		each = ' ' + ss.i18n._t('Event.REPEATEVERYEVERY', 'every') + ' ' + repeat + '' + ss.i18n._t('Event.REPEATEVERYTH', ':th ');
	}
	
	var typeID = jQuery('input[name=RepeatType]').val();
	if (typeID == '') {
		each += ss.i18n._t('Event.DAY', 'day') + ', ';
	}
	else if (typeID == 'w') {
		each += ss.i18n._t('Event.WEEK', 'week') + ', ';
		jQuery('ul[id$="RepeatDays"] input').each(function() {
			if (jQuery(this).attr('checked')) {
				each += jQuery(this).siblings('label').html() + ', ';
			}
		});
	}
	else if (typeID == 'm') {
		each += ss.i18n._t('Event.MONTH', 'month') + ', ';
	}
	
	var stop = '';
	if (jQuery('input[id$="RepeatStop_0"]').attr('checked')) {
		stop = '' + jQuery('input[name=RepeatTimes]').val() + ' ' + ss.i18n._t('Event.TIMES', 'times') ;
	}
	else {
		stop = ss.i18n._t('Event.REPEATUNTIL', 'until') + ' ' + jQuery('input[name=RepeatEndDay]').val();
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
	
	jQuery('ul[id$="RepeatDays"] input').attr('checked', false);
	jQuery('input[name=RepeatDays[' + day + ']]').attr('checked', true);
	UpdateSummary();
	jQuery('#CheckResult').html('');
}

jQuery(document).bind('dialogLoaded', function() {
	// reorder controls
	jQuery('ul[id$="RepeatDays"]').parent().hide();
	
	jQuery('label[id$="RepeatEachLabel"]').parent().addClass('inner').appendTo(jQuery('input[name=RepeatEach]').parent());
	
	jQuery('input[name=RepeatTimes]').parent().addClass('inner').appendTo(jQuery('input[id$="_RepeatStop_0"]').parent());
	jQuery('label[id$="RepeatTimesLabel"]').parent().addClass('inner').appendTo(jQuery('input[id$="_RepeatStop_0"]').parent());
	
	jQuery('#RepeatEndDay').appendTo(jQuery('input[id$="_RepeatStop_1"]').parent());
	
	// set handlers
	jQuery('input[name=Repeat]').change(function() {
		if (jQuery('input[name=Repeat]').attr('checked')) {
			jQuery('button[name=AddSingleDate]').hide();
			jQuery('#RepeatGroup').show(0, function() {top.SetIframeHeight();});
			UpdateSummary();
		}
		else {
			jQuery('button[name=AddSingleDate]').show();
			jQuery('#RepeatGroup').hide(0, function() {top.SetIframeHeight();});
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
	
	jQuery('input[name=RepeatStop]').change(function() {
		if (jQuery('input[name=RepeatStop]:checked').val() == '0') {
			jQuery('input[name=RepeatTimes]').removeAttr('disabled');
			jQuery('input[name=RepeatEndDay]').attr('disabled', 'disabled');
		}
		else if (jQuery('input[name=RepeatStop]:checked').val() == '1') {
			jQuery('input[name=RepeatTimes]').attr('disabled', 'disabled');
			jQuery('input[name=RepeatEndDay]').removeAttr('disabled');
		}
		else {
			jQuery('input[name=RepeatTimes]').attr('disabled', 'disabled');
			jQuery('input[name=RepeatEndDay]').attr('disabled', 'disabled');
		}
	}).trigger('change');
	
	if (jQuery('label[for$="RepeatStop_0"]').html() == ' ' || jQuery('label[for$="RepeatStop_0"]').html() == '') {
		jQuery('label[for$="RepeatStop_0"]').hide();
		jQuery('input[name=RepeatTimes]').css('margin-left', '0px');
	}
});




var calendarEvents = new Array();

jQuery(function() {
	var locale = ss.i18n.getLocale();
	
	jQuery('#DateSelector').datepicker({
		dateFormat: 'dd.mm.yy',
		showWeek: true,
		firstDay: 1,
		onSelect: function(dataText, inst) {
			jQuery('#CurrentSelectedDate').html(dataText);
			jQuery('input[name=RepeatEndDay]').datepicker('option', 'minDate', dataText);
			UpdateSummary();
		},
		beforeShowDay: function (date) {
		  for (i = 0; i < calendarEvents.length; i++) {
			  if (date.getMonth() == calendarEvents[i][0] - 1
			  && date.getDate() == calendarEvents[i][1]
			  && date.getFullYear() == calendarEvents[i][2]) {
			  //[disable/enable, class for styling appearance, tool tip]
			  return [true,"marked-date",''];
			  }
		   }
		   return [true, ""];//enable all other days
		}	
	});
	
	setTimeout("jQuery('#DateSelector').datepicker('refresh'); ", 100); // Fixes locale settings..
		
	jQuery('button[name=AddSingleDate]').click(function () {
		var picker = jQuery('#DateSelector');
		var dateStartString = picker.datepicker().val() + ' ' + jQuery('select[name=TimeStart[Hour]]').val() + ':' + jQuery('select[name=TimeStart[Minute]]').val();
		var dateEndString = picker.datepicker().val() + ' ' + jQuery('select[name=TimeEnd[Hour]]').val() + ':' + jQuery('select[name=TimeEnd[Minute]]').val();

		var startDate = Date.parseExact(dateStartString, 'dd.MM.yyyy HH:mm');
		var endDate = Date.parseExact(dateEndString, 'dd.MM.yyyy HH:mm');
		
		AddDateToList(startDate, endDate);
		SortDatesList();
		
		top.SetIframeHeight();
	});
	
	jQuery('button[name=AddMultipleDates]').click(function() {
		var picker = jQuery('#DateSelector');
		var dateStartString = picker.datepicker().val() + ' ' + jQuery('select[name=TimeStart[Hour]]').val() + ':' + jQuery('select[name=TimeStart[Minute]]').val();
		var dateEndString = picker.datepicker().val() + ' ' + jQuery('select[name=TimeEnd[Hour]]').val() + ':' + jQuery('select[name=TimeEnd[Minute]]').val();		
		
		var startDate = Date.parseExact(dateStartString, 'dd.MM.yyyy HH:mm');
		var endDate = Date.parseExact(dateEndString, 'dd.MM.yyyy HH:mm');
		
		var repeatType = jQuery('input[name=RepeatType]').val();
		var repeatEach = jQuery('input[name=RepeatEach]').val();
		var repeatTimes = jQuery('input[name=RepeatTimes]').val();
		var repeatUntil = jQuery('input[name=RepeatEndDay]').val();
		var repeatUntilDate = Date.parseExact(repeatUntil, 'dd.MM.yyyy');
		var repeatStop = (jQuery('input[name=RepeatStop]:checked').val() == '1' ? true : false);
		var repeatDays = new Array();
		if (jQuery('input[name=RepeatDays[1]]').is(':checked'))
			repeatDays.push(1);
		if (jQuery('input[name=RepeatDays[2]]').is(':checked'))
			repeatDays.push(2);
		if (jQuery('input[name=RepeatDays[3]]').is(':checked'))
			repeatDays.push(3);
		if (jQuery('input[name=RepeatDays[4]]').is(':checked'))
			repeatDays.push(4);
		if (jQuery('input[name=RepeatDays[5]]').is(':checked'))
			repeatDays.push(5);
		if (jQuery('input[name=RepeatDays[6]]').is(':checked'))
			repeatDays.push(6);
		if (jQuery('input[name=RepeatDays[7]]').is(':checked'))
			repeatDays.push(0);
		
		if (repeatEach == '')
			repeatEach = 1;
		else
			repeatEach = parseInt(repeatEach);
		
		if (repeatTimes == '')
			repeatTimes = 1;
		else
			repeatTimes = parseInt(repeatTimes);		
				
		// Add current date
		AddDateToList(startDate, endDate);
		
		if (repeatType == '') { // Daily
			var currentDate = startDate.clone();
			var currentEndDate = (endDate !== null ? endDate.clone() : null);
			
			if (repeatStop && repeatUntilDate !== null) {
				while(currentDate < repeatUntilDate) {
					currentDate.add(repeatEach).days();
					if (currentEndDate !== null)
						currentEndDate.add(repeatEach).days();
				
					AddDateToList(currentDate, currentEndDate);					
				}
			}
			else {
				for (var i = 0; i < repeatTimes; i++) {			
					currentDate.add(repeatEach).days();
					if (currentEndDate !== null)
						currentEndDate.add(repeatEach).days();

					AddDateToList(currentDate, currentEndDate);
				}
			}
		}
		else if (repeatType == 'm') { // Monthly
			var currentDate = startDate.clone();
			var currentEndDate = (endDate !== null ? endDate.clone() : null);
			
			if (repeatStop && repeatUntilDate !== null) {
				while(currentDate < repeatUntilDate) {
					currentDate.add(repeatEach).months();
					if (currentEndDate !== null)
						currentEndDate.add(repeatEach).months();
				
					AddDateToList(currentDate, currentEndDate);					
				}
			}
			else {
				for (var i = 0; i < repeatTimes; i++) {			
					currentDate.add(repeatEach).months();
					if (currentEndDate !== null)
						currentEndDate.add(repeatEach).months();

					AddDateToList(currentDate, currentEndDate);
				}
			}
		}
		else if (repeatType == 'w') { // Weekly 
			var currentDate = startDate.clone();
			var currentEndDate = (endDate !== null ? endDate.clone() : null);			

			if (repeatStop && repeatUntilDate !== null) {
				while(currentDate < repeatUntilDate) {
					for (var key in repeatDays) {
						var clonedDate = currentDate.clone().moveToDayOfWeek(repeatDays[key]);
						var clonedEndDate = null;
						if (currentEndDate !== null)
							clonedEndDate = currentEndDate.clone().moveToDayOfWeek(repeatDays[key]);

						if (clonedDate <= repeatUntilDate)
							AddDateToList(clonedDate, clonedEndDate);
					}
					currentDate.add(repeatEach*7).days();
					if (currentEndDate !== null)
						currentEndDate.add(repeatEach*7).days();					
				}
			}
			else {
				for (var i = 0; i < repeatTimes; i++) {	
					for (var key in repeatDays) {
						var clonedDate = currentDate.clone().moveToDayOfWeek(repeatDays[key]);
						var clonedEndDate = null;
						if (currentEndDate !== null)
							clonedEndDate = currentEndDate.clone().moveToDayOfWeek(repeatDays[key]);

						AddDateToList(clonedDate, clonedEndDate);
					}
					currentDate.add(repeatEach*7).days();
					if (currentEndDate !== null)
						currentEndDate.add(repeatEach*7).days();
				}				
			}
		}
		
		SortDatesList();
		top.SetIframeHeight();
		
		jQuery('input[name=Repeat]').click();
		jQuery('input[name=Repeat]').trigger('change');
	});
	
	jQuery('.date-trash').live('click', function() {
		jQuery(this).closest('li').remove();
		SortDatesList();
		
		top.SetIframeHeight();
	});
	
	jQuery('.date-edit').live('click', function() {
		var timeSpan = jQuery(this).siblings('.date-text').find('.time-span');
			
		if (timeSpan.hasClass('editing')) {
			timeSpan.next().find('time-edit-ok').trigger('click');
			return;
		}
		
		// Cancel other times that are currently in edit-mode
		jQuery('.time-edit-cancel').trigger('click');		
		
		var startTime = jQuery(this).siblings('input[name=DateStartValue]').val().split(' ');
		var endTime = jQuery(this).siblings('input[name=DateEndValue]').val().split(' ');

		var editableMinute = '<select name="EditMinute">';
		var editableHour = '<select name="EditHour">';

		for (var i=0;i<24;i++) {
			var prettyHour = i;
			if (prettyHour < 10)
				prettyHour = '0' + prettyHour;			
			
			editableHour += '<option value="' + prettyHour + '">' + prettyHour + '</option>';
		}

		for (var i=0;i<60;i+=5) {
			var prettyMinute = i;
			if (prettyMinute < 10)
				prettyMinute = '0' + prettyMinute;
			
			editableMinute += '<option value="' + prettyMinute + '">' + prettyMinute + '</option>';
		}
		
		editableMinute += '</select>';
		editableHour += '</select>';

		var editTimeStart = '<span class="edit-start">' + editableHour + ' : ' + editableMinute + '</span>';
		var editTimeEnd = '<span class="edit-end">' + editableHour + ' : ' + editableMinute + '</span>';
		var editSpan = '<span class="time-editing">' + editTimeStart + ' - ' + editTimeEnd + '<span class="edit-actions"><span class="time-edit-ok ui-icon ui-icon-circle-check"></span><span class="time-edit-cancel ui-icon ui-icon-circle-close"></span></span></span>';
		
		timeSpan.addClass('editing');
		jQuery(editSpan).insertAfter(timeSpan);
		
		timeSpan.next().find('.edit-end select').prepend('<option value="" selected="selected"></option>');
		
		if (startTime.length == 2) {
			var time = startTime[1].split(':');
			timeSpan.next().find('.edit-start select[name=EditHour]').val(time[0]);
			timeSpan.next().find('.edit-start select[name=EditMinute]').val(time[1]);
		}
		if (endTime.length == 2) {
			var time = endTime[1].split(':');
			timeSpan.next().find('.edit-end select[name=EditHour]').val(time[0]);
			timeSpan.next().find('.edit-end select[name=EditMinute]').val(time[1]);
		}	
	});	

	jQuery('.time-edit-ok').live('click', function() {
		var timeEdit = jQuery(this).closest('.time-editing');
		var prettyTimespan = timeEdit.prev();
		
		var startTime = timeEdit.find('.edit-start select[name=EditHour]').val() + ':' + timeEdit.find('.edit-start select[name=EditMinute]').val();
		var endTime = timeEdit.find('.edit-end select[name=EditHour]').val() + ':' + timeEdit.find('.edit-end select[name=EditMinute]').val();
		
		var startInputDate = timeEdit.closest('li').find('input[name=DateStartValue]');
		var endInputDate = timeEdit.closest('li').find('input[name=DateEndValue]');

		var startDate = startInputDate.val().split(' ');
		var endDate = endInputDate.val().split(' ');
		   
		var newStartDate = null;
		var newEndDate = null;

		if (startTime.length > 1)
			newStartDate = Date.parseExact(startDate[0] + ' ' + startTime, 'yyyy-MM-dd HH:mm');
		if (endTime.length > 1)
			newEndDate = Date.parseExact(startDate[0] + ' ' + endTime, 'yyyy-MM-dd HH:mm');

		jQuery(this).closest('li').remove();
		
		AddDateToList(newStartDate, newEndDate);
		SortDatesList();		
	});

	jQuery('.time-edit-cancel').live('click', function() {
		var timeEdit = jQuery(this).closest('.time-editing');
		timeEdit.prev().removeClass('editing');
		timeEdit.remove();
	});
	
	jQuery('select[name=EditHour]').live('change', function() {
		if (jQuery(this).next('select').val() == '')
			jQuery(this).next('select').val('00');		
	});
	
	jQuery('select[name=TimeEnd[Hour]]').change(function () {
		if (jQuery(this).next('select').val() == '')
			jQuery(this).next('select').val('00');
	});
	
	jQuery("input[name=RepeatTimes]").numeric({ negative : false, decimal: false });
	
	jQuery('.selected-dates-list .clear-dates').click(function() {
		jQuery('.date-list li').remove();
		SortDatesList();
		
		top.SetIframeHeight();
	});
});

function AddDateToList(startDate, endDate, id) {
	var dateID = 0;
	if (typeof id !== 'undefined')
		dateID = id;
	
	var template = '<li><input type="hidden" name="DateID" value="$DateID"/><input type="hidden" name="DateStartValue" value="$DateStartValue"/><input type="hidden" name="DateEndValue" value="$DateEndValue"/><span class="date-text">$PrettyDate</span><span class="date-trash ui-icon ui-icon-trash"></span><span class="date-edit ui-icon ui-icon-pencil"></span></li>';
	var prettyDate = startDate.toString('dddd d.M.yyyy') + ' ' + ss.i18n._t('Event.TIME_SHORT', 'at') + ' <span class="time-span"><span class="time-start">' + startDate.toString('HH:mm') + '</span>';
	if (endDate !== null)
		prettyDate += '<span class="time-separator"> - <span class="time-end">' + endDate.toString('HH:mm') + '</span></span></span>';
	else
		prettyDate += '</span>';
	
	// Capitalize first letter
	prettyDate = prettyDate.slice(0,1).toUpperCase() + prettyDate.slice(1);
	
	template = template.replace('$DateStartValue', startDate.toString('yyyy-MM-dd HH:mm'));
	if (endDate !== null)
		template = template.replace('$DateEndValue', endDate.toString('yyyy-MM-dd HH:mm'));
	else 
		template = template.replace('$DateEndValue', '');

	template = template.replace('$PrettyDate', prettyDate);
	template = template.replace('$DateID', dateID);

	var mylist = jQuery('.date-list');

	// Check if the date is already in the list
	var alreadyInserted = false;
	jQuery('.date-list li input[name=DateStartValue]').each(function () {
		var myValue = jQuery(this).val();

		if (myValue == startDate.toString('yyyy-MM-dd HH:mm'))
			alreadyInserted = true;
	});

	if (!alreadyInserted)
		jQuery(template).appendTo(mylist);
	
	jQuery('span[id$=EventDatesValidationMessage]').remove();
}

function SortDatesList() {
	// Cancel other times that are currently in edit-mode
	jQuery('.time-edit-cancel').trigger('click');
		
	var mylist = jQuery('.date-list');

	var listitems = mylist.children('li').get();
	listitems.sort(function(a, b) {
	   var compA = jQuery(a).find('input[name=DateStartValue]').val();
	   var compB = jQuery(b).find('input[name=DateStartValue]').val();
	   return (compA < compB) ? -1 : (compA > compB) ? 1 : 0;
	})
	
	jQuery.each(listitems, function(idx, itm) {mylist.append(itm);});		

	// Update datepicker events
	var datesValue = '';
	calendarEvents = new Array();
	listitems = mylist.children('li');
	listitems.each(function() {
		var dateStart = Date.parseExact(jQuery(this).find('input[name=DateStartValue]').val(), 'yyyy-MM-dd HH:mm');
		var dateEnd = Date.parseExact(jQuery(this).find('input[name=DateEndValue]').val(), 'yyyy-MM-dd HH:mm');
		var dateID = jQuery(this).find('input[name=DateID]').val();
		
		if (dateEnd === null)
			dateEnd = dateStart.clone();
			
		calendarEvents.push(new Array(dateStart.toString('MM'), dateStart.toString('dd'), dateStart.toString('yyyy')));
		
		datesValue += dateStart.toString('yyyy-MM-dd HH:mm') + '-' + dateEnd.toString('HH:mm') + ' ' + dateID + ',';
	});
	
	datesValue = datesValue.substring(0, datesValue.length-1);
	jQuery('input[name=EventDates]').val(datesValue);
	
	jQuery('#DateSelector').datepicker('refresh');
	
	if (!jQuery('.date-list li').length) 
		jQuery('.selected-dates-list .clear-dates').hide();
	else
		jQuery('.selected-dates-list .clear-dates').show();
}