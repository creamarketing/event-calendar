jQuery(function() {
	jQuery('#Form_OrganizerRegistrationForm_RegisterNewAssociation').click(function() {
		if (jQuery(this).is(':checked') && jQuery('#Form_OrganizerRegistrationForm_AssociationID').val() == '') {
			jQuery('.association-details').show();
			jQuery('#Form_OrganizerRegistrationForm_AssociationID').parent().removeClass('error');
			jQuery('#Form_OrganizerRegistrationForm_AssociationID').siblings('.validationMessage').remove();
			
			initializeValidation();
		}
		else {
			jQuery(this).removeAttr('checked');
			jQuery('.association-details').hide();
			
			initializeValidation();
		}	
	});
	
	if (jQuery('#Form_OrganizerRegistrationForm_RegisterNewAssociation').is(':checked')) {
		jQuery('.association-details').show();
	}
	
	jQuery('#Form_OrganizerRegistrationForm_AssociationID').change(function() {
		if (jQuery(this).val() == '') {
			jQuery('#Form_OrganizerRegistrationForm_RegisterNewAssociation').removeAttr('disabled');
		}
		else {
			jQuery('#Form_OrganizerRegistrationForm_RegisterNewAssociation').attr('disabled', 'disabled');
			jQuery('#Form_OrganizerRegistrationForm_RegisterNewAssociation').removeAttr('checked');
			jQuery('.association-details').hide();
		}
	});
	
	// Validation
	ss.i18n.init();
	validationRules['input[name=FirstName]'] = ss.i18n._t('Validation.FIELD_REQUIRED', 'This field is required');
	validationRules['input[name=Surname]'] = ss.i18n._t('Validation.FIELD_REQUIRED', 'This field is required');
	validationRules['input[name=Email]'] = {
		'validationEvents': ['blur'],
		'regularExpressions': [
			{ 'expression': /^.+$/, errormessage: ss.i18n._t('Validation.FIELD_REQUIRED', 'This field is required') },
			{ 'expression': /^([a-zA-Z0-9_\.\-])+\@(([a-zA-Z0-9\-])+\.)+([a-zA-Z0-9]{2,4})+$/, errormessage: ss.i18n._t('Validation.EMAIL_INVALID', 'This is not a valid email address') }
		],
		'ajaxRequests': [
         { url: checkValidEmailURL + 'isEmailValid' }
		]
	}
	validationRules['input[name="Password[_Password]"]'] = {
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
						return { password: jQuery('input[name="Password[_Password]"]').val() }
				}
			}
		]
	};	
	validationRules['input[name="Password[_ConfirmPassword]"]'] = {
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
						return { password: jQuery('input[name="Password[_Password]"]').val(), verification: jQuery('input[name="Password[_ConfirmPassword]"]').val() }
				}
			}
		]
	};
	validationRules['input[name=ReadConditions]'] = {
		'regularExpressions': [],
		'jsFunctions': [
			{ 'function': 
				function (values) {
					if(values.checked == true)
						return {valid:true }
					else
					return {valid:false, message: ss.i18n._t('Validation.MUST_ACCEPT_TERMS', 'You must accept the terms of use')}
				}, 
				'values': function(){
						return { checked: jQuery('input[name=ReadConditions]').is(':checked') }
				}
			}
		]
	};
	validationRules['input[name=AssociationID]'] = {
		'regularExpressions': [],
		'validationEvents': ['change'],
		'jsFunctions': [
			{ 'function': 
				function (values) {
					if((values.checked == true && values.selectedFromList == '') || (values.checked == false && values.selectedFromList != ''))
						return {valid:true }
					else
					return {valid:false, message: ss.i18n._t('Validation.MUST_CREATEORSELECT', 'You must create or select an organizer from the list')}
				}, 
				'values': function(){
						return { checked: jQuery('input[name=RegisterNewAssociation]').is(':checked'), selectedFromList: jQuery('input[name=AssociationID]').val() }
				}
			}
		]
	};
	validationRules['input[name^="Organization[Name_"]:visible'] = {
		'regularExpressions': [],
		'jsFunctions': [
			{ 'function': 
				function (values) {
					var atleastOne = false;
				
					values.names.each(function() {
						if (jQuery(this).val().length)
							atleastOne = true;
					});
					
					if (atleastOne) {
						jQuery('#Form_OrganizerRegistrationForm_AssociationName').parent().removeClass('error');
						jQuery('#Form_OrganizerRegistrationForm_AssociationName').siblings('.validationMessage').remove();
						return {valid:true }
					}
					else {
						jQuery('#Form_OrganizerRegistrationForm_AssociationName').parent().addClass('error');
						if (!jQuery('#Form_OrganizerRegistrationForm_AssociationName').siblings('.validationMessage').length)
							jQuery('#Form_OrganizerRegistrationForm_AssociationName').parent().append('<span class="validationMessage">' + ss.i18n._t('Validation.ORGANIZER_ATLEASTONENAME', 'You must enter at least one name') + '</span>');
						return {valid:false}
					}
				}, 
				'values': function(){
						return { names: jQuery('input[name^="Organization[Name_"]:visible') }
				}
			}
		]
	};
	validationRules['#Form_OrganizerRegistrationForm_Organization-MunicipalIDText:visible'] = ss.i18n._t('Validation.FIELD_REQUIRED', 'This field is required');
	validationRules['input[name="Organization[Email]"]:visible'] = {
		'regularExpressions': [],
		'jsFunctions': [ 
			{ 'function': 
				function(values) {
					if (!jQuery('input[name="Organization[Email]"]').val().length)
						return { valid: true }
					
					var regExp = /^([a-zA-Z0-9_\.\-])+\@(([a-zA-Z0-9\-])+\.)+([a-zA-Z0-9]{2,4})+$/;
					if (regExp.test(jQuery('input[name="Organization[Email]"]').val()))
						return { valid: true }

					return { valid: false, message: ss.i18n._t('Validation.EMAIL_INVALID', 'This is not a valid email address') }
				},
				'values': function() {
					return {}
				}
			}
		]
	};
	validationRules['input[name="Organization[Homepage]"]:visible'] = {
		'regularExpressions': [],
		'jsFunctions': [ 
			{ 'function': 
				function(values) {
					if (!jQuery('input[name="Organization[Homepage]"]').val().length)
						return { valid: true }
					
					var currentURL = jQuery('input[name="Organization[Homepage]"]').val();
					if (currentURL.indexOf('http') == -1 && currentURL.indexOf('https') == -1) 
						jQuery('input[name="Organization[Homepage]"]').val('http://' + currentURL);
					
					var regExp = /^(https|http)?:\/\/[a-z0-9-_.]+\.[a-z]{2,4}/i;
					if (regExp.test(jQuery('input[name="Organization[Homepage]"]').val()))
						return { valid: true }

					return { valid: false, message: ss.i18n._t('Validation.HOMEPAGE_INVALID', 'This is not a valid homepage address') }
				},
				'values': function() {
					return {}
				}
			}
		]
	};	
	
	initializeValidation();
	
	var fieldsToMark = new Array();
	fieldsToMark.push(jQuery('input[name=FirstName]').parent().prev());		
	fieldsToMark.push(jQuery('input[name=Surname]').parent().prev());
	fieldsToMark.push(jQuery('#Registration input[name=Email]').parent().prev());
	fieldsToMark.push(jQuery('input[name="Password[_Password]"]').parent().prev());
	fieldsToMark.push(jQuery('input[name="Password[_ConfirmPassword]"]').parent().prev());
	fieldsToMark.push(jQuery('input[name=ReadConditions]').next());
	fieldsToMark.push(jQuery('#Form_OrganizerRegistrationForm_AssociationName'));
	fieldsToMark.push(jQuery('label[for="Form_OrganizerRegistrationForm_Organization-MunicipalID"]'));
	
	
	for (var i = 0; i < fieldsToMark.length; i++) {
		fieldsToMark[i].append('<em style="color: red; margin-left: 5px">*</em>');
	}
	
	jQuery('input[name="Organization[PostalCode]"]').numeric({ negative : false, decimal: false });
	
	jQuery('#Registration form').submit(function(event) {
		if (validateRegistrationForm())
			return true;
		return false;
	});
});

var validationRules = {};

function initializeValidation() {
	var form = jQuery('#Registration');
	
	for (var key in validationRules) {
		jQuery(key, form).valid8(validationRules[key]);
	}
}

function validateRegistrationForm() {
	var form = jQuery('#Registration');
	
	var formOK = true;
	for (var key in validationRules) {
		var element = jQuery(key, form);		
		if (!element.isValid()) {
			formOK = false;
		}
	}

	return formOK;
}

// show or hide the correct member action form
function SetAction(){
	if (jQuery('#LoginAction').attr('checked')) {
		jQuery('#Registration').hide();
		jQuery('#Login').show();
		jQuery.cookie('eventcalendar-login-registration', 'login');
	}
	else {
		jQuery('#Login').hide();
		jQuery('#Registration').show();
		jQuery.cookie('eventcalendar-login-registration', 'registration');			
	}
}

function SwitchToLogin() {
	jQuery('#Registration').removeAttr('checked');
	jQuery('#LoginAction').attr('checked', 'checked');
	jQuery.cookie('eventcalendar-login-registration', 'login');
	SetAction();
}

jQuery(function() {
	var lor = jQuery.cookie('eventcalendar-login-registration');

	if (lor === null || lor == 'login') {
		jQuery('#Registration').hide();
		jQuery('#Login').show();			
		jQuery('#LoginAction').attr('checked', 'checked')
	}
	else {
		jQuery('#Login').hide();
		jQuery('#Registration').show();
		jQuery('#RegisterAction').attr('checked', 'checked')
	}
});