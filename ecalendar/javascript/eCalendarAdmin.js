// disable separator resizing, it messes with the calendar resizing!
DraggableSeparator.prototype.onmousedown = function() {};

// set tree-node selection to normal anchor behaviour (i.e. go to href location)
TreeNodeAPI.prototype.selectTreeNode = function() {
	if (this.getElementsByTagName('a')[0].href) {
		location.href = this.getElementsByTagName('a')[0].href;
	}
};

// disable tree-node context-menu
TreeNodeAPI.prototype.oncontextmenu = function(event){};


jQuery(document).ready(function() {
	//jQuery('div.dialogtabset').tabs();
	
	var $container = jQuery('#FirstPageLinks');		
	
	// Popup dialog
	var isModal = true;
	$container.find('a.popup-button').unbind('click').click(function(e) {
	
		// show jQuery dialog (in top document context, we might be inside an iframe here)
		if (jQuery(this).hasClass('wizard-mode')) {
			top.ShowWizardDialog($container.attr('id'), jQuery(this).attr('href'), jQuery(this).attr('title'), isModal)
		} else {
			top.ShowDialog($container.attr('id'), jQuery(this).attr('href'), jQuery(this).attr('title'), isModal);
		}
		// Important! Remember to stop event propagation and to return false, otherwise the default event will fire!
		e.stopPropagation();
		return false;
	});
});

// Keep a watch on unread messages
jQuery(function() {
	setInterval("updateMenuUnreadMessages();", 1000);
});

function updateMenuUnreadMessages() {
	var unreadMessage = jQuery('#IM_Controller #IM_Tabs a[href="#IM_Tab_Inbox"] .unread');
	var menuUnread = jQuery('.menu-unread-messages');
	
	if (unreadMessage.length && menuUnread.length) {
		menuUnread.html(unreadMessage.html());
	}
	
	if (menuUnread.length) {
		if (menuUnread.html().length)
			menuUnread.prev().addClass('has-unread');
		else
			menuUnread.prev().removeClass('has-unread');
	}
}

jQuery(document).bind('Form_EditEventsForm_Mine_Events_aftersave', function() {
	refresh(jQuery('#Form_EditEventsForm_Draft_Events'), jQuery('#Form_EditEventsForm_Draft_Events').attr('href'));
	refresh(jQuery('#Form_EditEventsForm_History_Mine_Events'), jQuery('#Form_EditEventsForm_History_Mine_Events').attr('href'));
});

jQuery(document).bind('Form_EditEventsForm_Draft_Events_aftersave', function() {
	refresh(jQuery('#Form_EditEventsForm_Mine_Events'), jQuery('#Form_EditEventsForm_Mine_Events').attr('href'));
	refresh(jQuery('#Form_EditEventsForm_History_Mine_Events'), jQuery('#Form_EditEventsForm_History_Mine_Events').attr('href'));	
});

jQuery(document).bind('Form_EditEventsForm_History_Mine_Events_aftersave', function() {
	refresh(jQuery('#Form_EditEventsForm_Mine_Events'), jQuery('#Form_EditEventsForm_Mine_Events').attr('href'));
	refresh(jQuery('#Form_EditEventsForm_Draft_Events'), jQuery('#Form_EditEventsForm_Draft_Events').attr('href'));		
});

jQuery(document).bind('Form_EditAssociationsForm_New_Associations_refresh', function() {
	refresh(jQuery('#Form_EditEventsForm_Unhandled_Events'), jQuery('#Form_EditEventsForm_Unhandled_Events').attr('href'));
});

jQuery(document).bind('Form_EditOrganizersForm_NotConfirmed_Organizers_refresh', function() {
	refresh(jQuery('#Form_EditAssociationsForm_New_Associations'), jQuery('#Form_EditAssociationsForm_New_Associations').attr('href'));
	refresh(jQuery('#Form_EditEventsForm_Unhandled_Events'), jQuery('#Form_EditEventsForm_Unhandled_Events').attr('href'));
});



// Hide/show some stuff when needed
jQuery(function() {
	setInterval(function() {
		// Draft events
		if (jQuery('#Form_EditEventsForm_Draft_Events .dataobject-list .data').length) 
			jQuery('#Form_EditEventsForm_Draft').parent().show();
		else
			jQuery('#Form_EditEventsForm_Draft').parent().hide();	
		
		// History events (mine)
		if (jQuery('#Form_EditEventsForm_History_Mine_Events .dataobject-list .data').length) 
			jQuery('#Form_EditEventsForm_History_Mine').parent().show();
		else
			jQuery('#Form_EditEventsForm_History_Mine').parent().hide();		
		
		// Events (others)
		if (jQuery('#Form_EditEventsForm_Others_Events .dataobject-list .data').length) 
			jQuery('#Form_EditEventsForm_Others').parent().show();
		else
			jQuery('#Form_EditEventsForm_Others').parent().hide();						
		
		// History events (others)
		if (jQuery('#Form_EditEventsForm_History_Others_Events .dataobject-list .data').length) 
			jQuery('#Form_EditEventsForm_History_Others').parent().show();
		else
			jQuery('#Form_EditEventsForm_History_Others').parent().hide();				
		
		// Unhandled users
		if (jQuery('#Form_EditOrganizersForm_NotConfirmed_Organizers .dataobject-list .data').length) 
			jQuery('#Form_EditOrganizersForm_NotConfirmed').parent().show();
		else
			jQuery('#Form_EditOrganizersForm_NotConfirmed').parent().hide();		
		
		// Unhandled associations
		if (jQuery('#Form_EditAssociationsForm_New_Associations .dataobject-list .data').length) 
			jQuery('#Form_EditAssociationsForm_New').parent().show();
		else
			jQuery('#Form_EditAssociationsForm_New').parent().hide();				
		
		// Unhandled events
		if (jQuery('#Form_EditEventsForm_Unhandled_Events .dataobject-list .data').length) 
			jQuery('#Form_EditEventsForm_Unhandled').parent().show();
		else
			jQuery('#Form_EditEventsForm_Unhandled').parent().hide();						
		
		// Unhandled permission requests
		if (jQuery('#Form_EditPermissionRequestsForm_PermissionRequests .dataobject-list .data').length) 
			jQuery('#Form_EditPermissionRequestsForm').parent().show();
		else
			jQuery('#Form_EditPermissionRequestsForm').parent().hide();						
		
		// Unhandled user invite requests
		if (jQuery('#Form_EditUserInviteRequestsForm_UserInviteRequests .dataobject-list .data').length) 
			jQuery('#Form_EditUserInviteRequestsForm').parent().show();
		else
			jQuery('#Form_EditUserInviteRequestsForm').parent().hide();						
		
	}, 500);
});

function ConfirmEventPublish(content, saveFunction) {
	var dialog = jQuery('#EventConfirmDialog');
	
	if (!dialog.length) {
		dialog = jQuery('<div id="EventConfirmDialog">' + content + '</div>');
		dialog.appendTo('body');
	}
	
	var buttonsText = {};
	buttonsText[ss.i18n._t('ConfirmDialog.YES', 'Yes')] = function () {
		jQuery(this).dialog('close');	
		saveFunction.call();
	}
	buttonsText[ss.i18n._t('ConfirmDialog.NO', 'No')] = function () {
		jQuery(this).dialog('close');
	}	
	
	dialog.dialog({
		title: ss.i18n._t('ConfirmDialog.TITLE', 'Are you sure?'),
		modal: true,
		buttons: buttonsText,
		width: 400,
		close: function() {
			jQuery(this).remove();
		}
	});
}

function PermissionRequestInfobox() {
	var dialog = jQuery('#PermissionRequestInfobox');
		
	var buttonsText = {};
	buttonsText['Ok'] = function () {
		jQuery(this).dialog('close');
	}	
	
	dialog.dialog({
		modal: true,
		buttons: buttonsText,
		width: 400,
		close: function() {
			jQuery(this).remove();
		}
	});
}

function UserInviteRequestInfobox() {
	var dialog = jQuery('#UserInviteRequestInfobox');
		
	var buttonsText = {};
	buttonsText['Ok'] = function () {
		jQuery(this).dialog('close');
	}	
	
	dialog.dialog({
		modal: true,
		buttons: buttonsText,
		width: 400,
		close: function() {
			jQuery(this).remove();
		}
	});
}

function NewAssociationCreatedInfobox() {
	var dialog = jQuery('#NewAssociationCreatedInfobox');
		
	var buttonsText = {};
	buttonsText['Ok'] = function () {
		jQuery(this).dialog('close');
	}	
	
	dialog.dialog({
		modal: true,
		buttons: buttonsText,
		width: 400,
		close: function() {
			jQuery(this).remove();
		}
	});
}

function ShowInviteDialog(invite_href, invite_title) {
	CloseLastDataObjectManager(false);
	ShowDialog('InviteUser', invite_href, invite_title, true, 400, 400);
}

jQuery(function() {
	jQuery('#Form_EditPermissionRequestsForm_PermissionRequests .data a.accept-reject-link').livequery(function() {
		var href = jQuery(this).attr('href');
		
		if (href.indexOf('PermissionRequest') != -1) {			
			jQuery(this).click(function(e) {
				if (!jQuery('#PermissionRequest_Confirm_Dialog').length)
					jQuery('body').append('<div id="PermissionRequest_Confirm_Dialog" title="' + ss.i18n._t('ConfirmDialog.TITLE', 'Are you sure?') + '"></div>');

				var confirmHref = jQuery(this).attr('href');
				var confirmText = '';
				if (confirmHref.indexOf('accept') != -1) 
					confirmText = ss.i18n._t('PermissionRequest.ACCEPT', 'Are you sure you want to accept this permission request?');
				else if (confirmHref.indexOf('reject') != -1)
					confirmText = ss.i18n._t('PermissionRequest.REJECT', 'Are you sure you want to reject this permission request?');
				
				jQuery('#PermissionRequest_Confirm_Dialog').html('<p><span class="ui-icon ui-icon-alert" style="float:left; margin:0 7px 20px 0;"></span>' + confirmText + '</p>');
				
				var buttonSet = {};
				buttonSet[ss.i18n._t('ConfirmDialog.YES', 'Yes')] = function() {
					jQuery.ajax({
						type: 'GET',
						dataType: 'text',
						data: {
							'confirmDialog': '1'
						},
						url: confirmHref,
						success: function(data) {
							if (!jQuery('#PermissionRequest_Result_Dialog').length)
								jQuery('body').append('<div id="PermissionRequest_Result_Dialog" title="' + ss.i18n._t('PermissionRequest.TITLE', 'Permission request') + '">' + data + '</div>');							
							
							jQuery('#PermissionRequest_Result_Dialog').dialog({
								modal: true,
								resizable: false,
								height: 140,
								buttons: {
									'Ok': function() {
										jQuery(this).dialog('close');
									}
								},
								close: function() {
									jQuery(this).remove();
								}
							});							
							
							refresh(jQuery('#Form_EditPermissionRequestsForm_PermissionRequests'), jQuery('#Form_EditPermissionRequestsForm_PermissionRequests').attr('href'));		
							jQuery('#PermissionRequest_Confirm_Dialog').dialog('close');
						},
						error: function() {
							jQuery('#PermissionRequest_Confirm_Dialog').dialog('close');
						}
					});
				}
				buttonSet[ss.i18n._t('ConfirmDialog.NO', 'NO')] = function() {
					jQuery(this).dialog('close');
				}
				
				jQuery('#PermissionRequest_Confirm_Dialog').dialog({
					modal: true,
					resizable: false,
					height: 140,
					buttons: buttonSet,
					close: function() {
						jQuery(this).remove();
					}
				});
				return false;
			});
		}
	});
	
	jQuery('#Form_EditUserInviteRequestsForm_UserInviteRequests .data a.accept-reject-link').livequery(function() {
		var href = jQuery(this).attr('href');
		
		if (href.indexOf('UserInviteRequest') != -1) {			
			jQuery(this).click(function(e) {
				if (!jQuery('#UserInviteRequest_Confirm_Dialog').length)
					jQuery('body').append('<div id="UserInviteRequest_Confirm_Dialog" title="' + ss.i18n._t('ConfirmDialog.TITLE', 'Are you sure?') + '"></div>');

				var confirmHref = jQuery(this).attr('href');
				var confirmText = '';
				if (confirmHref.indexOf('accept') != -1) 
					confirmText = ss.i18n._t('UserInviteRequest.ACCEPT', 'Are you sure you want to accept this invitation?');
				else if (confirmHref.indexOf('reject') != -1)
					confirmText = ss.i18n._t('UserInviteRequest.REJECT', 'Are you sure you want to reject this invitation?');
				
				jQuery('#UserInviteRequest_Confirm_Dialog').html('<p><span class="ui-icon ui-icon-alert" style="float:left; margin:0 7px 20px 0;"></span>' + confirmText + '</p>');
				
				var buttonSet = {};
				buttonSet[ss.i18n._t('ConfirmDialog.YES', 'Yes')] = function() {
					jQuery.ajax({
						type: 'GET',
						dataType: 'text',
						data: {
							'confirmDialog': '1'
						},
						url: confirmHref,
						success: function(data) {
							if (!jQuery('#UserInviteRequest_Result_Dialog').length)
								jQuery('body').append('<div id="UserInviteRequest_Result_Dialog" title="' + ss.i18n._t('UserInviteRequest.TITLE', 'Invitation') + '">' + data + '</div>');
							
							jQuery('#UserInviteRequest_Result_Dialog').dialog({
								modal: true,
								resizable: false,
								height: 140,
								buttons: {
									'Ok': function() {
										jQuery(this).dialog('close');
									}
								},
								close: function() {
									jQuery(this).remove();
								}
							});							
							
							refresh(jQuery('#Form_EditUserInviteRequestsForm_UserInviteRequests'), jQuery('#Form_EditUserInviteRequestsForm_UserInviteRequests').attr('href'));		
							jQuery('#UserInviteRequest_Confirm_Dialog').dialog('close');
						},
						error: function() {
							jQuery('#UserInviteRequest_Confirm_Dialog').dialog('close');
						}
					});
				}
				buttonSet[ss.i18n._t('ConfirmDialog.NO', 'NO')] = function() {
					jQuery(this).dialog('close');
				}
				
				jQuery('#UserInviteRequest_Confirm_Dialog').dialog({
					modal: true,
					resizable: false,
					height: 140,
					buttons: buttonSet,
					close: function() {
						jQuery(this).remove();
					}
				});
				return false;
			});
		}
	});	
});