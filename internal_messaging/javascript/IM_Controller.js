var IM_Controller_AutoRefresh_Timer = null;

jQuery(function() {
	jQuery('#IM_Tabs').tabs({
		tabTemplate: "<li><a class='removable' href='#{href}'>#{label}</a> <span class='ui-icon ui-icon-close'>Remove Tab</span></li>",
		show: function(event, ui) {
			currentTabID = jQuery(ui.panel).attr('id');
			currentTabIndex = ui.index;
					
			jQuery('#IM_Inbox_Pagination').css('display', (currentTabID == 'IM_Tab_Inbox' ? 'block' : 'none'));
			jQuery('#IM_Sentbox_Pagination').css('display', (currentTabID == 'IM_Tab_Sentbox' ? 'block' : 'none'));
			jQuery('#IM_Trashbox_Pagination').css('display', (currentTabID == 'IM_Tab_Trashbox' ? 'block' : 'none'));
		}
	});
	jQuery('#IM_Controller_NewMessageButton').button({
		icons: {primary: "ui-icon-pencil"}
	});
	jQuery('#IM_Controller_NewMessageButton').click(function() {
		IM_Controller_NewMessage();
	});
	jQuery('button.action-send').livequery(function() {
		jQuery(this).button({
			icons: {primary: "ui-icon-mail-closed"}
		});
		jQuery(this).click(function() {
			var action = jQuery(this).siblings('input[name=SendLink]:first');
			var actionUrl = action.val();			
			var tabID = jQuery(this).parent().parent().parent().attr('id');
			var queryData = jQuery(this).parent().siblings('form[name=MessageSendForm]:first').formSerialize();
			var recipientID = jQuery(this).parent().siblings('form[name=MessageSendForm]:first').find('input[name=Recipient]');
			var subject = jQuery(this).parent().siblings('form[name=MessageSendForm]:first').find('input[name=Subject]');
			
			if (recipientID.val() == '' || !subject.val().length) {
				if (recipientID.val() == '')
					recipientID.next().effect("highlight", {}, 500);
				if (!subject.val().length)
					subject.effect("highlight", {}, 500);
				return;
			}
			
			// Already an action pending?
			if (action.parent().hasClass('action-pending'))
				return;
			else
				action.parent().addClass('action-pending');			
			
			jQuery.ajax({
				type: 'POST',
				dataType: 'json',
				data: queryData,
				url: actionUrl,
				success: function(data) {
					action.parent().removeClass('action-pending');
					
					jQuery('a.removable[href="#' + tabID + '"]').next().click();
					IM_Controller_RefreshMessageBoxes();					
				}
			});			
		});
	});
	jQuery('button.action-reply').livequery(function() {
		jQuery(this).button({
			icons: {primary: "ui-icon-pencil"}
		});
		jQuery(this).click(function() {
			var tabSet = jQuery('#IM_Tabs');
			var nextTabNr = tabSet.tabs('length'); // zero based index, so count equals next tab nr
			var action = jQuery(this).siblings('input#' + jQuery(this).attr('id') + 'Link');
			var actionUrl = action.val();

			// Already an action pending?
			if (action.parent().hasClass('action-pending'))
				return;
			else
				action.parent().addClass('action-pending');	

			jQuery.ajax({
				type: 'POST',
				dataType: 'json',
				url: actionUrl,
				success: function(data) {	
					action.parent().removeClass('action-pending');
					
					var nextTabID = "#IM_Tab_ReplyMessage" + nextTabNr;
					var subjectShort = data['TabTitle'];
					if (subjectShort.length > 10)
						subjectShort = subjectShort.substring(0, 10) + '...';
					
					tabSet.tabs("add", nextTabID, "<span class='ui-icon ui-icon-arrowreturnthick-1-w'></span><span class='title'>" + subjectShort + "</span>");	
					jQuery(nextTabID).html(data['Template']);
					tabSet.tabs("select", nextTabNr);
				}
			});			
		});
	});
	jQuery('button.action-restore').livequery(function() {
		jQuery(this).button({
			icons: {primary: "ui-icon-arrowreturnthick-1-w"}
		});		
		jQuery(this).click(function () {
			var action = jQuery(this).siblings('input#' + jQuery(this).attr('id') + 'Link');
			var actionUrl = action.val();
			var tabID = jQuery(this).parent().parent().parent().attr('id');
			
			// Already an action pending?
			if (action.parent().hasClass('action-pending'))
				return;
			else
				action.parent().addClass('action-pending');

			jQuery.ajax({
				type: 'POST',
				dataType: 'json',
				url: actionUrl,
				success: function(data) {
					action.parent().removeClass('action-pending');
					
					jQuery('a.removable[href="#' + tabID + '"]').next().click();
					IM_Controller_RefreshMessageBoxes();
				}
			});
			
		});		
	});
	jQuery('button.action-trash, button.action-delete').livequery(function() {
		jQuery(this).button({
			icons: {primary: "ui-icon-trash"}
		});
		jQuery(this).click(function () {
			var action = jQuery(this).siblings('input#' + jQuery(this).attr('id') + 'Link');
			var actionUrl = action.val();
			var tabID = jQuery(this).parent().parent().parent().attr('id');
			
			// Already an action pending?
			if (action.parent().hasClass('action-pending'))
				return;
			else
				action.parent().addClass('action-pending');			

			jQuery.ajax({
				type: 'POST',
				dataType: 'json',
				url: actionUrl,
				success: function(data) {
					action.parent().removeClass('action-pending');
					
					jQuery('a.removable[href="#' + tabID + '"]').next().click();
					IM_Controller_RefreshMessageBoxes();					
				}
			});
			
		});
	});		
	
	jQuery('#IM_Tabs span.ui-icon-close').live('click', function() {
			var index = jQuery("li", jQuery('#IM_Tabs')).index( jQuery( this ).parent() );
			jQuery('#IM_Tabs').tabs( "remove", index );
		});	
	
	jQuery('#IM_Inbox_Pagination a').live('click', function() {
		IM_Controller_RefreshMessageBox('inbox', jQuery(this).attr('href'));
		return false;
	});
	
	jQuery('#IM_Sentbox_Pagination a').live('click', function() {
		IM_Controller_RefreshMessageBox('sentbox', jQuery(this).attr('href'));
		return false;
	});	
	
	jQuery('#IM_Trashbox_Pagination a').live('click', function() {
		IM_Controller_RefreshMessageBox('trashbox', jQuery(this).attr('href'));
		return false;
	});		
	
	jQuery('#IM_Tabs .messages .message-status a').live('click', function() {
		IM_Controller_ToggleStatus(jQuery(this));		
		return false;
	});
	
	jQuery('#IM_Tabs .messages a.open-message').live('click', function() {
		IM_Controller_OpenMessage(jQuery(this));
		if (jQuery(this).parent().hasClass('unread'))
			IM_Controller_ToggleStatus(jQuery(this).prev().children('a:first'));
		return false;
	});	
	
	jQuery('#IM_Tabs .messages li.header a.sort-messages').live('click', function() {
		var url = jQuery(this).attr('href');
		if (url.search('inbox') != -1)
			IM_Controller_RefreshMessageBox('inbox', url);
		else if (url.search('sentbox') != -1)
			IM_Controller_RefreshMessageBox('sentbox', url);
		else if (url.search('trashbox') != -1)
			IM_Controller_RefreshMessageBox('trashbox', url);
		
		return false;
	});
	
	jQuery('#IM_Tabs .message-container .message-body a').livequery(function() {
		jQuery(this).attr('target', '_blank');
	});
	
	jQuery('#IM_Action_Refresh').click(function() {
		IM_Controller_RefreshMessageBoxes();
	});
	
	jQuery('.message-actions a.action-trashbox-deleteall').live('click', function() {
		var action = jQuery(this);
		var actionUrl = action.attr('href');
		
		// Already an action pending?
		if (action.parent().hasClass('action-pending'))
			return false;
		else
			action.parent().addClass('action-pending');

		jQuery.ajax({
			type: 'POST',
			dataType: 'json',
			url: actionUrl,
			success: function(data) {
				action.parent().removeClass('action-pending');
				for (var i = 0; i < data['DeletedMessages'].length; i++)
					IM_CloseOpenMessageByID(data['DeletedMessages'][i]);
				IM_Controller_RefreshMessageBoxes();
			}
		});		
		return false;		
	});
	
	jQuery('.message-actions a.action-trash, .message-actions a.action-restore').live('click', function() {
		var action = jQuery(this);
		var actionUrl = action.attr('href');
		
		// Already an action pending?
		if (action.parent().hasClass('action-pending'))
			return false;
		else
			action.parent().addClass('action-pending');

		jQuery.ajax({
			type: 'POST',
			dataType: 'json',
			url: actionUrl,
			success: function(data) {
				IM_CloseOpenMessageByID(data['ID']);
				action.closest('li').fadeOut(500, function() {
					IM_Controller_RefreshMessageBoxes();					
				});
			}
		});		
		return false;
	});
	
	jQuery('#SearchText').focus(function () {
		var searchText = ss.i18n._t('IM_Message.SEARCH', 'Search');
		if (jQuery(this).val() == searchText)
			jQuery(this).val('');
	}).blur(function () {
		var searchText = ss.i18n._t('IM_Message.SEARCH', 'Search');
		if (jQuery(this).val() == '')
			jQuery(this).val(searchText);
	}).keydown(function(e) {
		if (e.keyCode == 13) {
			IM_Controller_RefreshMessageBoxes();
			jQuery(this).blur();
			e.stopPropagation();
			return false;			
		}
	});
	
	jQuery('.top-controls-search-reset').click(function () {
		var e = jQuery.Event("keydown");
		e.keyCode = jQuery.ui.keyCode.ENTER;
		jQuery('#SearchText').attr('value','').focus().trigger(e);		
	});
	
	jQuery('input[name=Subject]').live('keyup', function() {
		var tab_panel = jQuery(this).closest('form').parent().parent();
		var tab = jQuery('#IM_Tabs a.removable[href="#' + tab_panel.attr('id') + '"]');
		var msg_title = jQuery(this).val();
		
		if (msg_title.length > 10)
			msg_title = msg_title.substring(0, 10) + '...';
				
		tab.find('.title').html(msg_title);
	});
	
	IM_Controller_RefreshMessageBoxes();
	
	IM_Controller_AutoRefresh_Timer = setInterval('IM_Controller_RefreshMessageBoxes()', 5*60*1000); // Every 5 minutes
});

function IM_CloseOpenMessageByID(messageID) {
	var alreadyOpen = false;
	
	jQuery('#IM_Tabs .ui-tabs-panel input[name=MessageID]').each(function () {
		if (jQuery(this).val() == messageID) {
			alreadyOpen = true;
			openMessageTab = jQuery('#IM_Tabs a[href="#' + jQuery(this).parent().attr('id') + '"]');
		}
	});
	
	if (alreadyOpen) 
		openMessageTab.next().click();
}

function IM_Controller_RefreshMessageBoxes() {
	var url = jQuery('#IM_Controller_URL').val();
	
	if (jQuery('#IM_Tab_Inbox input[name=RefreshLink]').length)
		IM_Controller_RefreshMessageBox('inbox', jQuery('#IM_Tab_Inbox input[name=RefreshLink]').val());
	else
		IM_Controller_RefreshMessageBox('inbox', url + '/messagebox/inbox/refresh');

	if (jQuery('#IM_Tab_Sentbox input[name=RefreshLink]').length)
		IM_Controller_RefreshMessageBox('sentbox', jQuery('#IM_Tab_Sentbox input[name=RefreshLink]').val());
		
	else
		IM_Controller_RefreshMessageBox('sentbox', url + '/messagebox/sentbox/refresh');
	
	if (jQuery('#IM_Tab_Trashbox input[name=RefreshLink]').length)
		IM_Controller_RefreshMessageBox('trashbox', jQuery('#IM_Tab_Trashbox input[name=RefreshLink]').val());
	else
		IM_Controller_RefreshMessageBox('trashbox', url + '/messagebox/trashbox/refresh');
}

function IM_Controller_OpenMessage(message) {
	var messageUrl = message.attr('href');
	
	jQuery.ajax({
		type: 'POST',
		dataType: 'json',
		url: messageUrl,
		success: function (data) {
			var tabSet = jQuery('#IM_Tabs');
			var nextTabNr = tabSet.tabs('length'); // zero based index, so count equals next tab nr			
			var nextTabID = "#IM_Tab_Message" + nextTabNr;
			var subjectShort = data['Subject'];
			var alreadyOpen = false;
			var openMessageTab = null;

			if (subjectShort.length > 10) {
				subjectShort = subjectShort.substring(0, 10) + '...';
			}

			jQuery('#IM_Tabs .ui-tabs-panel input[name=MessageID]').each(function () {
				if (jQuery(this).val() == data['ID']) {
					alreadyOpen = true;
					openMessageTab = jQuery('#IM_Tabs a[href="#' + jQuery(this).parent().attr('id') + '"]');
				}
			});
		
			if (alreadyOpen) {
				openMessageTab.click();
				return;
			}
				
			jQuery('#IM_Tabs').tabs( "add", nextTabID, "<span class='ui-icon ui-icon-mail-open'></span><span class='title'>" + subjectShort + "</span>");
			jQuery(nextTabID).html(data['Template']);
			tabSet.tabs("select", nextTabNr);			
		}
	});	
}

function IM_Controller_ToggleStatus(message) {
	var spanChild = jQuery(message).find('span:first');
	var tabPanel = jQuery(message).closest('.ui-tabs-panel');
	var tabCounter = jQuery("#IM_Tabs a[href='#" + tabPanel.attr('id') + "'] span.unread");
	var messageUrl = message.attr('href');
		
	jQuery.ajax({
		type: 'POST',
		dataType: 'json',
		url: messageUrl,
		success: function (data) {
			if (spanChild.hasClass('message-unread')) {
				spanChild.removeClass('message-unread');
				spanChild.addClass('message-read');
				message.parent().parent().removeClass('unread');
			}
			else {
				spanChild.removeClass('message-read');
				spanChild.addClass('message-unread');			
				message.parent().parent().addClass('unread');
			}
			
			if (data['Unread'] > 0) {
				tabCounter.html(' (' + data['Unread'] + ')');
			}
			else {
				tabCounter.html('');
			}
			
			message.attr('href', data['Link']);
		}
	});
}

function IM_Controller_NewMessage() {
	var tabSet = jQuery('#IM_Tabs');
	var nextTabNr = tabSet.tabs('length'); // zero based index, so count equals next tab nr
	var newMessageURL = jQuery('#IM_Controller_URL').val() + '/newMessage';

	jQuery.ajax({
		type: 'POST',
		dataType: 'json',
		url: newMessageURL,
		success: function(data) {	
			var nextTabID = "#IM_Tab_NewMessage" + nextTabNr;
			tabSet.tabs("add", nextTabID, "<span class='ui-icon ui-icon-pencil'></span><span class='title'>" + data['TabTitle'] + "</span>");	
			jQuery(nextTabID).html(data['Template']);
			tabSet.tabs("select", nextTabNr);
		}
	});
}

function IM_Controller_RefreshMessageBox(boxname, boxurl) {
	jQuery('#IM_Controller_AjaxLoader').show();
	jQuery.ajax({
		type: 'POST',
		dataType: 'json',
		url: boxurl,
		data: {
			'searchText': jQuery('#SearchText').val()
		},
		success: function(data) {
			jQuery('#IM_Controller_AjaxLoader').hide();
			if (boxname == 'inbox') {
				jQuery('#IM_Tab_Inbox').html(data['Messages']);
				jQuery('#IM_Inbox_Pagination').html(data['Pagination']);
				
				if (data['Unread'] > 0) 
					jQuery('#IM_Tabs a[href="#IM_Tab_Inbox"] span.unread').html(' (' + data['Unread'] + ')');
				else
					jQuery('#IM_Tabs a[href="#IM_Tab_Inbox"] span.unread').html('');
			}
			else if (boxname == 'sentbox') {
				jQuery('#IM_Tab_Sentbox').html(data['Messages']);
				jQuery('#IM_Sentbox_Pagination').html(data['Pagination']);
				
				if (data['Unread'] > 0) 
					jQuery('#IM_Tabs a[href="#IM_Tab_Sentbox"] span.unread').html(' (' + data['Unread'] + ')');
				else
					jQuery('#IM_Tabs a[href="#IM_Tab_Sentbox"] span.unread').html('');				
			}
			else if (boxname == 'trashbox') {
				jQuery('#IM_Tab_Trashbox').html(data['Messages']);
				jQuery('#IM_Trashbox_Pagination').html(data['Pagination']);
				
				if (data['Unread'] > 0) 
					jQuery('#IM_Tabs a[href="#IM_Tab_Trashbox"] span.unread').html(' (' + data['Unread'] + ')');
				else
					jQuery('#IM_Tabs a[href="#IM_Tab_Trashbox"] span.unread').html('');								
			}
		},
		error: function(data) {
			jQuery('#IM_Controller_AjaxLoader').hide();
		}
	});
}

function recipientDropdown_Show(request, response, id) {
	//var id = jQuery('input[name=Recipient]').attr('id');
	var firstSelectedItem = jQuery('#' + id + 'First');
	var matcher = new RegExp('\\b' + jQuery.ui.autocomplete.escapeRegex(request.term), 'i');
	response(jQuery('#' + id + 'Select').children('option:enabled').map(function(){
		if (jQuery(this).val() == 0 || matcher.test(jQuery(this).text())) {
			var checked = '';
			var first = ' class="recipient-group-item"';
			var selectedItems = jQuery('#' + id + 'Select option.selected');
			var multipleValues = jQuery(this).val().split(',');	
			
			for (var i = 0; i < selectedItems.length; i++) {
				if (selectedItems[i].value == jQuery(this).val() ||
					(multipleValues.length && selectedItems[i].value in multipleValues)) {
					checked = 'checked = "checked"';
					if (firstSelectedItem.length && selectedItems[i].value == firstSelectedItem.val())
						first = ' class="recipient-group-item first"';
					break;
				}
			}
			var text = jQuery(this).text();
			if (jQuery(this).val() != '' && !jQuery(this).hasClass('recipient-group')) {
				text = '<input' + first + ' type=\"checkbox\" ' + checked + ' />' + '<span' + first + '>' + jQuery(this).text() + '</span>';
			}
			else 
				text = '<span class="recipient-group">' + jQuery(this).text() + '</span>';
			return {
				label: text,
				value: jQuery(this).text(),
				option: this
			};
		}
	}));
}

function recipientDropdown_Select(event, ui, id) {
	//var id = jQuery('input[name=Recipient]').attr('id');
	var firstItem = jQuery('#' + id + "First");
	
	if (ui.item.option.value == '') {
		jQuery('#' + id + 'Select option.selected').removeClass('selected');
		jQuery('#' + id).val('');
		
		if (firstItem.length)
			firstItem.val('');
			
		return true;
	}
	
	if (jQuery(ui.item.option).hasClass('selected')) {
		jQuery(ui.item.option).removeClass('selected');
		
		if (firstItem.length && firstItem.val() == jQuery(ui.item.option).val())
			firstItem.val('');
	}
	else {
		jQuery(ui.item.option).addClass('selected');
		
		if (firstItem.length && firstItem.val() == '')
			firstItem.val(jQuery(ui.item.option).val());
		
		// Select items inside this group if it is a group
		if (jQuery(ui.item.option).hasClass('recipient-group') && !jQuery(ui.item.option).hasClass('mark-all') && !jQuery(ui.item.option).hasClass('unmark-all')) {
			var group = jQuery(ui.item.option);
			group.nextAll('option').each(function() {
				if (jQuery(this).hasClass('recipient-group')) {
					return false;
				}
				
				jQuery(this).toggleClass('selected');
			});
			
			group.removeClass('selected');
		}
		// Mark all?
		else if (jQuery(ui.item.option).hasClass('mark-all')) {
			var group = jQuery(ui.item.option);
			group.nextAll('option').each(function() {
				if (jQuery(this).hasClass('recipient-group')) {
					jQuery(this).removeClass('selected');
					return true;
				}
				
				jQuery(this).addClass('selected');
			});
			
			group.removeClass('selected');
		}		
		// Unmark all?
		else if (jQuery(ui.item.option).hasClass('unmark-all')) {
			var group = jQuery(ui.item.option);
			group.nextAll('option').each(function() {
				if (jQuery(this).hasClass('recipient-group')) {
					jQuery(this).removeClass('selected');
					return true;
				}
				
				jQuery(this).removeClass('selected');
			});
			
			group.removeClass('selected');
		}		
		
	}
	
	var selectedItems = jQuery('#' + id + 'Select option.selected');
	var selection = '';
	var selectionText = '';
	for (var i = 0; i < selectedItems.length; i++) {
		if (firstItem.length && firstItem.val() == '')
			firstItem.val(selectedItems[i].value);
		
		if (selection != '') {
			selection += ',';
		}
		selection += selectedItems[i].value;
		
		if (selectionText != '') {
			selectionText += ', ';
		}
		selectionText += selectedItems[i].innerHTML;
	}
	jQuery('#' + id).val(selection);
	jQuery('#' + id + 'Text').val(selectionText);
	
	return false;
}