jQuery(function() {
	jQuery('#DialogDataObjectManager_Popup_AddForm_newAssociation').click(function() {
		if (jQuery(this).is(':checked')) {
			jQuery("#tab-TabSet_GeneralTab").show();
			jQuery("#tab-TabSet_LogoTab").show();
			jQuery("#tab-TabSet_GroupTab").show();
			jQuery("#PermissionType").hide();
			jQuery("#DialogDataObjectManager_Popup_AddForm_help").hide();
			//jQuery('#TabSet').tabs('select', 1);			
		}
		else {
			jQuery(this).removeAttr('checked');
			jQuery("#tab-TabSet_GeneralTab").hide();
			jQuery("#tab-TabSet_LogoTab").hide();
			jQuery("#tab-TabSet_GroupTab").hide();
			jQuery("#PermissionType").show();
			jQuery("#DialogDataObjectManager_Popup_AddForm_help").show();
		}	
	});
	
	if (jQuery('#DialogDataObjectManager_Popup_AddForm_newAssociation').is(':checked')) {
		jQuery("#tab-TabSet_GeneralTab").show();
		jQuery("#tab-TabSet_LogoTab").show();
		jQuery("#tab-TabSet_GroupTab").show();
		jQuery("#PermissionType").hide();
		jQuery("#DialogDataObjectManager_Popup_AddForm_help").hide();
	} else {
		jQuery("#tab-TabSet_GeneralTab").hide();
		jQuery("#tab-TabSet_LogoTab").hide();
		jQuery("#tab-TabSet_GroupTab").hide();
		jQuery("#PermissionType").show();
		jQuery("#DialogDataObjectManager_Popup_AddForm_help").show();
	}
	
	jQuery(document).bind('dialogLoaded', function() {
		var form = jQuery('form:first');
		form.bind('afterDialogDataObjectManagerClose', function(e, saved) {
			if (form.find('input[name=newAssociation]').is(':checked'))
				top.NewAssociationCreatedInfobox();
			else
				top.PermissionRequestInfobox();
		});	
	});
});
