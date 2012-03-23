jQuery(function() {
	// Validation
	ss.i18n.init();
	changepasswordValidationRules['input[name="NewPassword[_Password]"]'] = {
		'regularExpressions': [
			{ 'expression': /^.+$/, errormessage: ss.i18n._t('Validation.FIELD_REQUIRED', 'This field is required') },
		],
		'jsFunctions': [
			{ 'function': 
				function (values) {
					if (values.password.length < 6) 
						return {valid:false, message: ss.i18n._t('Validation.PASSWORD_TOOSHORT', 'Password is too short (minimum length 6)') }
					if (/\d/.test(values.password) == false) 
						return {valid:false, message: ss.i18n._t('Validation.PASSWORD_NEEDSDIGITS', 'Password must contain at least one digit') }
				
					return {valid:true}
				}, 
				'values': function(){
						return { password: jQuery('input[name="NewPassword[_Password]"]').val() }
				}
			}
		]
	};	
	changepasswordValidationRules['input[name="NewPassword[_ConfirmPassword]"]'] = {
		'regularExpressions': [ { 'expression': /^.+$/, errormessage: ss.i18n._t('Validation.FIELD_REQUIRED', 'This field is required') } ],
		'jsFunctions': [
			{ 'function': 
				function (values) {
					if(values.password == values.verification)
						return {valid:true}
					else
					return {valid:false, message: ss.i18n._t('Validation.PASSWORD_MISSMATCH', 'Passwords do not match')}
				}, 
				'values': function(){
						return { password: jQuery('input[name="NewPassword[_Password]"]').val(), verification: jQuery('input[name="NewPassword[_ConfirmPassword]"]').val() }
				}
			}
		]
	};
	
	initializeChangepasswordValidation();
	
	jQuery('#CalendarPasswordForm_ChangePasswordForm').submit(function(event) {
		if (validateChangepasswordForm())
			return true;
		return false;
	});
});

var changepasswordValidationRules = {};

function initializeChangepasswordValidation() {
	var form = jQuery('#CalendarPasswordForm_ChangePasswordForm');
	
	for (var key in changepasswordValidationRules) {
		jQuery(key, form).valid8(changepasswordValidationRules[key]);
	}
}

function validateChangepasswordForm() {
	var form = jQuery('#CalendarPasswordForm_ChangePasswordForm');
	
	var formOK = true;
	for (var key in changepasswordValidationRules) {
		var element = jQuery(key, form);		
		if (!element.isValid()) {
			formOK = false;
		}
	}

	return formOK;
}