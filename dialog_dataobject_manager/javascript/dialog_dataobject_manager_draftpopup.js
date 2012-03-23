var preventSaveOnSelect = false;

jQuery(document).ready(function() {
	// Re-bind submit method for forms to not submit forms via normal submit events
	// (for example by pressing enter in a text field).
	// Forms will be submitted via ajax in popup dialogs.
	jQuery('form').unbind('submit').submit(function() {
		return false;
	});
	
	// open dialog-tabsets (if one is present)
	var tabSet = jQuery('div.dialogtabset');
	tabSet.tabs({
		show: function(event, ui) {
			top.SetIframeHeight();
			//top.AutosaveDraft(false, ui.index);
		},
		select: function(event, ui) {
			if (preventSaveOnSelect == false)
				top.AutosaveDraft(true, ui.index);			
		}
	});
	
	// trigger the custom dialogLoaded event
	jQuery(document).trigger('dialogLoaded');
});

function GotoTab(tabIndex, preventSave) {
	var tabSet = jQuery('div.dialogtabset').first();
	preventSaveOnSelect = preventSave;
	tabSet.tabs('select', tabIndex);
	preventSaveOnSelect = false;
}

function GetCurrentTabIndex() {
	var tabSet = jQuery('div.dialogtabset').first();
	return tabSet.tabs('option', 'selected');
}

function saveForm(saveFunction) {
	// trigger the before save event, so that scripts included in the edited object can do stuff before saving
	// (by returning false and manually calling the passed save function when for example a save is confirmed)
	var event = jQuery.Event('beforeDialogDataObjectManagerSave');
	jQuery('form').trigger(event, saveFunction);
	if (event.result != false) {
		saveFunction.call();
	}
}

function publishForm(saveFunction) {
	// trigger the before save event, so that scripts included in the edited object can do stuff before saving
	// (by returning false and manually calling the passed save function when for example a save is confirmed)
	var event = jQuery.Event('beforeDialogDataObjectManagerPublish');
	jQuery('form').trigger(event, saveFunction);
	if (event.result != false) {
		saveFunction.call();
	}
}

function unpublishForm(saveFunction) {
	// trigger the before save event, so that scripts included in the edited object can do stuff before saving
	// (by returning false and manually calling the passed save function when for example a save is confirmed)
	var event = jQuery.Event('beforeDialogDataObjectManagerUnpublish');
	jQuery('form').trigger(event, saveFunction);
	if (event.result != false) {
		saveFunction.call();
	}
}

function onAfterClose(saved) {
	var event = jQuery.Event('afterDialogDataObjectManagerClose');
	jQuery('form').trigger(event, saved);
}