jQuery(function() {	
	// Validation
	ss.i18n.init();
	lostpasswordValidationRules['input[name=Email]'] = {
		'regularExpressions': [
			{ 'expression': /^.+$/, errormessage: ss.i18n._t('Validation.FIELD_REQUIRED', 'This field is required') },
			{ 'expression': /^([a-zA-Z0-9_\.\-])+\@(([a-zA-Z0-9\-])+\.)+([a-zA-Z0-9]{2,4})+$/, errormessage: ss.i18n._t('Validation.EMAIL_INVALID', 'This is not a valid email address') }
		]
	}
	
	initializeLostpasswordValidation();
		
	jQuery('#CalendarLoginForm_LostPasswordForm').submit(function(event) {
		if (validateLostpasswordForm())
			return true;
		return false;
	});
});

var lostpasswordValidationRules = {};

function initializeLostpasswordValidation() {
	var form = jQuery('#CalendarLoginForm_LostPasswordForm');
	
	for (var key in lostpasswordValidationRules) {
		jQuery(key, form).valid8(lostpasswordValidationRules[key]);
	}
}

function validateLostpasswordForm() {
	var form = jQuery('#CalendarLoginForm_LostPasswordForm');
	
	var formOK = true;
	for (var key in lostpasswordValidationRules) {
		var element = jQuery(key, form);		
		if (!element.isValid()) {
			formOK = false;
		}
	}

	return formOK;
}