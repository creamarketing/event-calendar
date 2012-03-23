jQuery(document).ready(function() {
	// Re-bind submit method for forms to not submit forms via normal submit events
	// (for example by pressing enter in a text field).
	// Forms will be submitted via ajax in popup dialogs.
	jQuery('form:first').unbind('submit').submit(function() {
		return false;
	});
	
	// open dialog-tabsets (if one is present)
	var tabSet = jQuery('div.dialogtabset');
	tabSet.tabs({
		show: function() {
			top.SetIframeHeight();
		}
	});
	
	// trigger the custom dialogLoaded event
	jQuery(document).trigger('dialogLoaded');
});

function saveForm(saveFunction) {
	// trigger the before save event, so that scripts included in the edited object can do stuff before saving
	// (by returning false and manually calling the passed save function when for example a save is confirmed)
	var event = jQuery.Event('beforeDialogDataObjectManagerSave');
	jQuery('form').trigger(event, saveFunction);
	if (event.result != false) {
		saveFunction.call();
	}
}

function onAfterClose(saved) {
	var event = jQuery.Event('afterDialogDataObjectManagerClose');
	jQuery('form').trigger(event, saved);
}