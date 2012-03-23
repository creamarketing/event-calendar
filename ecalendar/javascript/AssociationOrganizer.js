var validationRules = {};

jQuery(function() {
	ss.i18n.init();
	validationRules['input[name=Email]'] = {
		'validationEvents': ['blur'],
		'regularExpressions': [
			{ 'expression': /^.+$/, errormessage: ss.i18n._t('Validation.FIELD_REQUIRED', 'This field is required') },
			{ 'expression': /^([a-zA-Z0-9_\.\-])+\@(([a-zA-Z0-9\-])+\.)+([a-zA-Z0-9]{2,4})+$/, errormessage: ss.i18n._t('Validation.EMAIL_INVALID', 'This is not a valid email address') }
		],
		'ajaxRequests': [
         { url: top.eCalendarAdminHref + 'isEmailRegistered?id=' + (jQuery('input[name=Email]').closest('form').find('input[name="ctf[childID]"]').val() || 0) }
		]
	};
	
	var form = jQuery('form:first');
	for (var key in validationRules) 
		jQuery(key, form).valid8(validationRules[key]);
	
	jQuery('.invite-user-link').livequery(function() {
		jQuery(this).unbind('click').click(function(e) {
			top.ShowInviteDialog(jQuery(this).attr('href'), jQuery(this).attr('title'));
			e.stopPropagation();
			return false;
		});
	});
});

jQuery(document).bind('dialogLoaded', function() {
	// Load validation if we are editing.. otherwise we have to click save button twice
	if (jQuery('input[name="ctf[childID]"]').length) {
		for (var key in validationRules) {
			var element = jQuery(key, form);		
			element.isValid();
		}
	}

	var form = jQuery('form:first');
	form.bind('beforeDialogDataObjectManagerSave', function(e, saved) {
		var formOK = true;
		for (var key in validationRules) {
			var element = jQuery(key, form);		
			if (!element.isValid()) {
				formOK = false;
			}
		}
		
		return formOK;
	});		
});