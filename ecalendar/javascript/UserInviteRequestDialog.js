jQuery(function() {	
	jQuery(document).bind('dialogLoaded', function() {
		var form = jQuery('form:first');
		form.bind('afterDialogDataObjectManagerClose', function(e, saved) {
			top.UserInviteRequestInfobox();
		});	
		
		setTimeout("top.SetIframeHeight(300);", 100);
	});
});
