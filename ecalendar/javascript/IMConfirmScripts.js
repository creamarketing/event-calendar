jQuery(function() {
	jQuery('#IM_Tabs .message-container .message-body a').livequery(function() {
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
		
		if (href.indexOf('AcceptMember') != -1) {
			jQuery(this).click(function(e) {
				if (!jQuery('#AcceptMember_Confirm_Dialog').length)
					jQuery('body').append('<div id="AcceptMember_Confirm_Dialog" title="' + ss.i18n._t('ConfirmDialog.TITLE', 'Are you sure?') + '"></div>');

				var confirmHref = jQuery(this).attr('href');
				var confirmText = '';

				confirmText = ss.i18n._t('AcceptMember.ACCEPT', 'Are you sure you want to accept this member and publish all related events?');
				
				jQuery('#AcceptMember_Confirm_Dialog').html('<p><span class="ui-icon ui-icon-alert" style="float:left; margin:0 7px 20px 0;"></span>' + confirmText + '</p>');
				
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
							if (!jQuery('#AcceptMember_Result_Dialog').length)
								jQuery('body').append('<div id="AcceptMember_Result_Dialog" title="' + ss.i18n._t('AcceptMember.TITLE', 'Accept member') + '">' + data + '</div>');							
							
							jQuery('#AcceptMember_Result_Dialog').dialog({
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
							
							jQuery('#AcceptMember_Confirm_Dialog').dialog('close');
						},
						error: function() {
							jQuery('#AcceptMember_Confirm_Dialog').dialog('close');
						}
					});
				}
				buttonSet[ss.i18n._t('ConfirmDialog.NO', 'NO')] = function() {
					jQuery(this).dialog('close');
				}
				
				jQuery('#AcceptMember_Confirm_Dialog').dialog({
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
		
		if (href.indexOf('HandleNewAssociation') != -1) {			
			jQuery(this).click(function(e) {
				if (!jQuery('#PermissionRequest_Confirm_Dialog').length)
					jQuery('body').append('<div id="HandleNewAssociation_Confirm_Dialog" title="' + ss.i18n._t('ConfirmDialog.TITLE', 'Are you sure?') + '"></div>');

				var confirmHref = jQuery(this).attr('href');
				var confirmText = '';
				if (confirmHref.indexOf('accept') != -1) 
					confirmText = ss.i18n._t('Association.CONFIRMACCEPT', 'Are you sure you want to accept this association?');
				else if (confirmHref.indexOf('reject') != -1)
					confirmText = ss.i18n._t('Association.CONFIRMREJECT', 'Are you sure you want to reject this association?');
				
				jQuery('#HandleNewAssociation_Confirm_Dialog').html('<p><span class="ui-icon ui-icon-alert" style="float:left; margin:0 7px 20px 0;"></span>' + confirmText + '</p>');
				
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
							if (!jQuery('#HandleNewAssociation_Result_Dialog').length)
								jQuery('body').append('<div id="HandleNewAssociation_Result_Dialog" title="' + ss.i18n._t('Association.HANDLENEWTITLE', 'New association request') + '">' + data + '</div>');							
							
							jQuery('#HandleNewAssociation_Result_Dialog').dialog({
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
							
							jQuery('#HandleNewAssociation_Confirm_Dialog').dialog('close');
						},
						error: function() {
							jQuery('#HandleNewAssociation_Confirm_Dialog').dialog('close');
						}
					});
				}
				buttonSet[ss.i18n._t('ConfirmDialog.NO', 'NO')] = function() {
					jQuery(this).dialog('close');
				}
				
				jQuery('#HandleNewAssociation_Confirm_Dialog').dialog({
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