/**
 * Initialize an advanced autocomplete dropdown field.
 * @param String   filterID ID of the filter
 * @param function onSelect	function to call on item selection
 * @param function getSource optional function to call on source generation
 */
function AdvancedDropdownField(filterID, onSelect, getSource, extraClasses) {
	var autocompleteInput = jQuery('#'+filterID+'Text').autocomplete({
		minLength: 0,
		source: function(request, response) {
			if (getSource) {
				getSource(request, response, filterID);
			}
			else {
				var matcher = new RegExp("(^\|[ \n\r\t.,'\"\+!?-]+)" + jQuery.ui.autocomplete.escapeRegex(request.term), 'i');
				response(jQuery('#' + filterID + 'Select').children("option:enabled").map(function(){
					if (jQuery(this).val() == 0 || matcher.test(jQuery(this).text())) {
						var text = '';
						if (jQuery(this).hasClass('level1')) {
							text = '&nbsp;&nbsp;' + jQuery(this).text();
						}
						else {
							text = jQuery(this).text();
						}
						return {
							label: text,
							value: jQuery(this).text(),
							option: this
						};
					}
				}));
			}
		},
		select: function(event, ui) {
			itemSelected = true;
			var result = true;
			
			if (onSelect) {
				var retval = onSelect(event, ui, filterID);
				if (typeof retval == 'object') {
					itemSelected = retval.itemSelected;
					result = retval.result;
				}
				else {
					result = retval;
				}
			}
			else {
				jQuery('#' + filterID).val(jQuery(ui.item.option).val());
			}

			if (itemSelected) {
				jQuery('#' + filterID + 'Select').val(jQuery(ui.item.option).val());
				jQuery('#' + filterID).trigger('change');
			}
			
			return result;			
		},
		focus: function(event, ui) {
			return false;
		}
	});
	
	// Fixes issue with html inside dropdown
	autocompleteInput.data( "autocomplete" )._renderItem = function( ul, item ) {
			var extraClasses = '';
			if (item.extraClasses) {
				extraClasses = 'class="' + item.extraClasses + '"';
			}
			return jQuery( "<li></li>" )
				.data( "item.autocomplete", item )
				.append( "<a " + extraClasses + ">" + item.label + "</a>" )
				.appendTo( ul );
	};
	
	// Override the original close event to prevent closing
	var originalCloseMethod = autocompleteInput.data("autocomplete").close;
    autocompleteInput.data("autocomplete").close = function(event) {
        if (!dropdownVisible){
            //close requested by someone else, let it pass
            originalCloseMethod.apply( this, arguments );
        }
		else {
			if (!itemSelected) {
				jQuery('#'+filterID+'Text').val(prevFilterText);
			}
			dropdownVisible = false;
			var event = jQuery.Event('onAdvancedDropdownClose');
			jQuery('#'+filterID+'Text').trigger(event);
			if (event.result == false) {
				jQuery('#'+filterID+'Text').autocomplete('search', '');
				dropdownVisible = true;
				return false;
			}
			else {
				window.setTimeout("jQuery('#"+filterID+"Text').blur();", 10);
			}
		}
    };	
	
	var prevFilterText = jQuery('#'+filterID+'Text').val();
	var itemSelected = false;
	var dropdownVisible = false;
	jQuery('#'+filterID+'Text').click(function() {
		if (dropdownVisible) {
			jQuery('#'+filterID+'Text').autocomplete('close');
		}
		else {
			dropdownVisible = true;
			prevFilterText = jQuery('#'+filterID+'Text').val();
			itemSelected = false;
			document.getElementById(filterID + 'Text').select();
			jQuery('#'+filterID+'Text').autocomplete('search', '');
		}
	});
	
	if (typeof extraClasses !== 'undefined')
		jQuery('#'+filterID+'Text').autocomplete('widget').addClass(extraClasses);
}

function AdvancedDropdownFieldPosition(filterID, _my, _at) {
	jQuery('#'+filterID+'Text').autocomplete('option', 'position', { my: _my, at: _at });
}

function ShowAddOrEditDialog(id, link, title, isAdd, contextWindow) {
	if (!contextWindow) {
		contextWindow = window;
	}
	var content = document.createElement('div');
	var loadingText = ss.i18n._t('DialogDataObjectManager.LOADING', 'Loading');
	var ajaxLoader = '<div id="DialogAjaxLoader"><h2>' + loadingText + '...</h2><img src="dataobject_manager/images/ajax-loader-white.gif" alt="Loading in progress..." /></div>';
	content.innerHTML = ajaxLoader;
	
	var parentDialog = jQuery('.ui-dialog').last();
	if (parentDialog.html()) {
		jQuery(parentDialog).animate({
			left: '-=200',
			top: '-=50'
		},
		800);
	}
	
	var saveText = ss.i18n._t('DialogDataObjectManager.SAVE', 'Save');
	var closeText = ss.i18n._t('DialogDataObjectManager.CLOSE', 'Close');
	var buttonOptions = {};
	buttonOptions[saveText] = function() {
		jQuery('.ui-button').attr('disabled',true).addClass('ui-state-disabled');
		SaveAddOrEditDialog(jQuery(content).find('form'), function() {
			var errorMessage = jQuery(content).parent().find('#ErrorMessage');
			errorMessage.html('');
			errorMessage.hide();
			jQuery(content).find('form').ajaxSubmit({
				dataType: 'json',
				success: function(responseData, statusText, xhr, form) {
					try {
						var newId = responseData['ID'];
						var name = responseData['Name'];
						var error = responseData['Error'];
						if (error) {
							errorMessage.html(error);
							errorMessage.show(500);
						}
						else {
							if (!isAdd || (isAdd && !jQuery('#' + id + 'Text').attr('disabled'))) {
								contextWindow.jQuery('#' + id).val(newId);
								contextWindow.jQuery('#' + id + 'Text').val(name);
							}
							if (contextWindow.jQuery('#' + id + 'Select option[value="' + newId + '"]').html()) {
								contextWindow.jQuery('#' + id + 'Select option[value="' + newId + '"]').html(name);
							}
							else {
								contextWindow.jQuery('#' + id + 'Select').append('<option value="' + newId + '">' + name + '</option>');
							}
							if (isAdd) {
								contextWindow.jQuery(contextWindow.document).trigger('AddDialogClosed', [id, newId, name]);
							}
							else {
								contextWindow.jQuery(contextWindow.document).trigger('EditDialogClosed', [id, newId, name]);
							}
							jQuery(content).dialog('close');
						}
					}
					catch (e) {
						errorMessage.html('Error in returned data!');
						errorMessage.show(500);
					}
					jQuery('.ui-button').attr('disabled',false).removeClass('ui-state-disabled');
				},
				error: function(XMLHttpRequest, textStatus, errorThrown) {
					errorMessage.html(XMLHttpRequest.responseText);
					errorMessage.show(500);
					jQuery('.ui-button').attr('disabled',false).removeClass('ui-state-disabled');
				}
			});
		});
	};
	buttonOptions[closeText] = function(){
		jQuery(this).dialog("close");
	};
	
	jQuery(content).addClass('right');
 	jQuery(content).dialog({
		title: title,
		modal: true,
		buttons: buttonOptions,
		width: 650,
		height: 600,
		open: function() {
			jQuery('.ui-button').attr('disabled',true).addClass('ui-state-disabled');
			jQuery(this).parent().find('.ui-dialog-buttonpane').append('<div id="Output" style="float:left;"><div id="StatusMessage" class="Message" style="display:none;"></div><div id="ErrorMessage" class="Message" style="display:none;"></div></div>');
		},
		close: function() {
			// move the parent dialog back
			if (parentDialog.html()) {
				jQuery(parentDialog).animate({
					left: '+=200',
					top: '+=50'
				},
				800);
			}
			// remove the dialog from the DOM, so that we do not leave a lot of unecessary data in the DOM tree
			jQuery(this).remove();
		}
	});
	
	jQuery.ajax({
		async: false,
		url: link,
		dataType: 'html',
		success: function(data){
			jQuery(content).html('<div class="DialogDataObjectManager_ItemRequest_Popup">' + data + '</div>');
			// open tabs, if present
			jQuery(content).find('div.dialogtabset').tabs();
			
			if (isAdd) {
				contextWindow.jQuery(contextWindow.document).trigger('AddDialogLoaded', [id, content]);
			}
			else {
				contextWindow.jQuery(contextWindow.document).trigger('EditDialogLoaded', [id, content]);
			}
			jQuery('.ui-button').attr('disabled',false).removeClass('ui-state-disabled');
		},
		error: function() {
			alert('error');
		}
	});
}

function SaveAddOrEditDialog(form, saveFunction) {
	var event = jQuery.Event('beforeDialogDataObjectManagerSave');
	jQuery(form).trigger(event, saveFunction);
	if (event.result != false) {
		saveFunction.call();
	}
}