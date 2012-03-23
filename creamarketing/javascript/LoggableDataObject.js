jQuery(document).ready(function() {
	jQuery('table.LogTable tr.LogItem').live('click', function() {
		// find relevant class and ids from the table row id (which is of type OBJECTCLASS-OBJECTID-LOGITEMID
		var idParts = jQuery(this).attr('id').split('-');
		if (idParts.length >= 3) {
			var objectClass = idParts[0];
			var objectID = idParts[1];
			var logItemID = idParts[2];
			var locale = jQuery('meta[http-equiv=Content-language]').attr("content");
			ShowDetailsDialog(objectID, objectClass, logItemID, locale);
		}
	});
	ss.i18n.init();
});

function ShowDetailsDialog(objectID, objectClass, logItemID, locale) {
	var loadingText = ss.i18n._t('LoggableDataObject.DETAILSLOADING', 'Laddar detaljer...');
	var ajaxLoader = '<div id="DetailsDialogAjaxLoader"><h2>' + loadingText + '</h2><img src="dataobject_manager/images/ajax-loader-white.gif" alt="Loading in progress..." /></div>';
	// add iframe container div containing the iframe to the body
	jQuery('body').append('<div id="DetailsDialog" style="display:none;">' + ajaxLoader + '</div>');
	
	var buttonsOptions = {};
	buttonsOptions[ss.i18n._t('LoggableDataObject.CLOSE', 'Close')] = function() {
		jQuery(this).dialog('close');
	};
	
	// find parent dialog (if one exists) and move it upwards and left
	var nrOfDialogs = jQuery('.ui-dialog').length;
	var left = 200 - (nrOfDialogs-1)*50;
	var parentDialog = jQuery('.ui-dialog').last();
	if (parentDialog.html()) {
		jQuery(parentDialog).animate({
			left: '-=' + left,
			top: '-=50'
		},
		800);
	}
	
	var baseHref = jQuery('base').attr('href');
	
	jQuery('#DetailsDialog').dialog({
		title: ss.i18n._t('ResourceBookingForm.LOGDETAILSTITLE', 'Detaljer för loggrad'),
		width: 500,
		resizable: false,
		modal: true,
		buttons: buttonsOptions,
		open: function() {
			jQuery(".ui-button").attr("disabled", true).addClass('ui-state-disabled');
			
			jQuery.ajax({
				type: "GET",
				url: baseHref + 'LoggableDataObject_Controller/LogItemDetails',
				data: 'ObjectID='+objectID+'&ObjectClass='+objectClass+'&LogItemID='+logItemID+'&Locale='+locale,
				success: function(data, textStatus, XMLHttpRequest) {
					jQuery('#DetailsDialog').html(data);
					jQuery(".ui-button").attr("disabled", false).removeClass('ui-state-disabled');
				},
				error: function(XMLHttpRequest, textStatus, errorThrown) {
					jQuery('#DetailsDialog').html(XMLHttpRequest.responseText);
					jQuery(".ui-button").attr("disabled", false).removeClass('ui-state-disabled');
				}
			});
		},
		close: function() {
			// move the parent dialog back
			if (parentDialog.html()) {
				jQuery(parentDialog).animate({
					left: '+=' + left,
					top: '+=50'
				},
				800);
			}
			// remove the dialog from the DOM, so that we do not leave a lot of unecessary data in the DOM tree
			jQuery(this).remove();
		}
	});
}