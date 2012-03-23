(function($) {

$.fn.DataObjectManager = function() {
	this.each(function() {
		$.fn.DataObjectManager.init(this);
	});
};

$.fn.DataObjectManager.init = function(obj) {
		var $container = $(obj);
		var container_id = '#'+$container.attr('id');
		var nested = $('.DataObjectManager').hasClass('isNested');
		
		// Popup dialog
		var isModal = false;
		// For Nested DOMs
		if(nested) {
			// TODO: add configuration support for using non-modal nested dialogs
			isModal = true;
		}
		// For normal DOMs
		else {
			isModal = true;
		}
		
		// popup links (add and edit)
  		$container.find('a.popup-button').unbind('click').click(function(e) {
			if ($(this).attr('href')) {
				// show jQuery dialog (in top document context, we might be inside an iframe here)
				if ($(this).hasClass('wizard-mode'))
					top.ShowWizardDialog($container.attr('id'), $(this).attr('href'), $(this).attr('title'), isModal);
				else if ($(this).hasClass('draft-mode'))
					top.ShowDraftDialog($container.attr('id'), $(this).attr('href'), $(this).attr('title'), isModal);
				else if ($(this).hasClass('status-mode'))
					top.ShowStatusDialog($container.attr('id'), $(this).attr('href'), $(this).attr('title'), isModal);
				else
					top.ShowDialog($container.attr('id'), $(this).attr('href'), $(this).attr('title'), isModal);
			}
			// Important! Remember to stop event propagation and to return false, otherwise the default event will fire!
  			e.stopPropagation();
  			return false;
  		});
		
		// Delete
    	$deletes = $container.find('a.delete-link');
		$deletes.unbind('click').click(function(e) {
	  		$('.delete_dialog').remove();
	  		params = $('#SecurityID') ? {'forceajax' : '1', 'SecurityID' : $('#SecurityID').attr('value')} : {'forceajax' : '1'};
	    	$target = $(this);
	
			var deleteText = ss.i18n._t('DialogDataObjectManager.DELETE', 'Delete?');
			if($(this).attr('rel') == "confirm") {
				$div = $('<div class="delete_dialog">'
				           +deleteText
				           +' <a class="yes" href="javascript:void(0)"><img src="dataobject_manager/images/accept.png" alt="yes" /></a> '
				           +' <a class="no" href="javascript:void(0)"><img src="dataobject_manager/images/cancel.png" alt="no"/></a> '
				       +'</div>'
				).click(function(e) {return false;e.stopPropagation()});
				
				$(this).parents('div:first').append($div);
				height = $(this).parents('li').height();
				$(this).parents('li').css({
				'height' : height+'px',
				'overflow' : 'visible'
				});
				$div.fadeIn("slow");
				$div.find('.yes').click(function(e) {
				$.post($target.attr('href'),params,function() {$($target).parents('li:first').fadeOut();$(".ajax-loader").hide();});		  
					e.stopPropagation();
				return false;
				});
				$div.find('.no').click(function(e) {
					$(this).parent().remove().parents('li').css({
						'height' : 'auto',
						'overflow' : 'hidden'
					});
					e.stopPropagation();
					return false;
				});
			}
			else {
	  			$.post($target.attr('href'),params,function() {$($target).parents('li:first').fadeOut();$(".ajax-loader").hide();});
	      	}
			return false;
		});
		
		// Refresh
		
		$container.find('a.refresh-button').unbind('click').click(function(e) {
			$t = $(this);
			$.post($t.attr('href'),{},function() {
				refresh($container, $container.attr('href'));
			});
			return false;
		});
				

		// Pagination
		$container.find('.Pagination a').unbind('click').click(function() {
			refresh($container, $(this).attr('href'));
			return false;
		});
		
		// View
		if($container.hasClass('FileDataObjectManager') && !$container.hasClass('ImageDataObjectManager')) {
			$container.find('a.viewbutton').unbind('click').click(function() {
				refresh($container, $(this).attr('href'));
				return false;
			});
		}
		
		

		// Sortable
		$container.find('.sort-control input').unbind('click').click(function(e) {
			refresh($container, $(this).attr('value'));
			$(this).attr('disabled', true);
			e.stopPropagation();
		});
		$container.find("ul[class^='sortable-']").sortable({
			update : function(e) {
				$list = $(this);
				do_class = $.trim($list.attr('class').replace('sortable-','').replace('ui-sortable','').replace('clickSelects',''));
				type = $container.hasClass('ManyMany') ? $container.find('input[name=controllerID]').val() : '';
				$.post('DataObjectManager_Controller/dosort/'+do_class+'/'+type, $list.sortable("serialize"));
				e.stopPropagation();
			},
			items : 'li:not(.head)',
			containment : 'document',
			tolerance : 'intersect',
			handle : ($('.list-holder').hasClass('grid') ? '.handle' : null)
		});
		
		// Click function for the LI
		if ($container.hasClass('RelationDataObjectManager')) {
			// toggle the checkbox mark for relation DOMs
			$container.find('ul:not(.ui-sortable) li.data').unbind('click').click(function(e){
				if ($(this).parent().hasClass('clickSelects')) {
					$(this).find('input').click().change();
				}
				else {
					$(this).find('a.popup-button:first').click();
				}				
				e.stopPropagation();
			}).css({
				'cursor': 'pointer'
			});
		}
		else {
			// click the first popup-button for normal DOMs (edit or show, depending on permissions)
			$container.find('ul:not(.ui-sortable) li.data').unbind('click').click(function(e){
				$(this).find('a.popup-button:first').click();
				e.stopPropagation();
			}).css({
				'cursor': 'pointer'
			});
		}
		
		// Prevent click propagation on links with noClickPropagation class
		$container.find('ul:not(.ui-sortable) li.data .col a.noClickPropagation').unbind('click').click(function(e) {
		  e.stopPropagation();
		});			
		
		// Column sort
		if(!$container.hasClass('ImageDataObjectManager')) {
			$container.find('li.head a').unbind('click').click(function() {
				refresh($container, $(this).attr('href'));
				return false;
			});
		}
		
		// Filter
		$container.find('.dataobjectmanager-filter select').unbind('change').change(function(e) {
			refresh($container, $(this).attr('value'));
		});

		// Page size
		$container.find('.per-page-control select').unbind('change').change(function(e) {
			refresh($container, $(this).attr('value'));
		});

		
		// Refresh filter
		$container.find('.dataobjectmanager-filter .refresh').unbind('click').click(function(e) {
			refresh($container, $container.attr('href'));
			e.stopPropagation();
			return false;
		})
	
		// Search
		//var request = false;
		$container.find('#srch_fld').focus(function() {
			var i18nSearchString = ss.i18n._t('DialogDataObjectManager.SEARCH', 'Search');
			if($(this).attr('value') == i18nSearchString) $(this).attr('value','').css({'color' : '#333'});
		}).unbind('blur').blur(function() {
			var i18nSearchString = ss.i18n._t('DialogDataObjectManager.SEARCH', 'Search');			
			if($(this).attr('value') == '') $(this).attr('value',i18nSearchString).css({'color' : '#666'});
		}).unbind('keyup').keyup(function(e) {
        
        if ((e.keyCode == 9) || (e.keyCode == 13) || // tab, enter 
           (e.keyCode == 16) || (e.keyCode == 17) || // shift, ctl 
           (e.keyCode >= 18 && e.keyCode <= 20) || // alt, pause/break, caps lock
           (e.keyCode == 27) || // esc 
           (e.keyCode >= 33 && e.keyCode <= 35) || // page up, page down, end 
           (e.keyCode >= 36 && e.keyCode <= 38) || // home, left, up 
            (e.keyCode == 40) || // down 
           (e.keyCode >= 36 && e.keyCode <= 40) || // home, left, up, right, down
           (e.keyCode >= 44 && e.keyCode <= 45) || // print screen, insert 
           (e.keyCode == 229) // Korean XP fires 2 keyup events, the key and 229 
        ) return; 
				// Search on enter key press instead, auto-searching after 500ms after keypress can be confusing
				/*
				if(request) window.clearTimeout(request);
				$input = $(this);
				request = window.setTimeout(function() {
					url = $(container_id).attr('href').replace(/\[search\]=(.)*?&/, '[search]='+$input.attr('value')+'&');
          refresh($container, url, '#srch_fld'); 
					
				},500)*/
			e.stopPropagation();
		}).unbind('keydown').keydown(function(e) {
			// stop event propagation on enter key, we do not want this field to submit any form
			if (e.keyCode == 13) {
				$input = $(this);
				$searchField_select = $container.find('#SearchFieldnameSelect');
				url = $(container_id).attr('href').replace(/\[search\]=(.)*?&/, '[search]='+$input.attr('value')+'&');
				url = url.replace(/\[search_fieldname\]=(.)*?&/, '[search_fieldname]='+$searchField_select.val()+'&');
				refresh($container, url, '#srch_fld'); 				
				e.stopPropagation();
				return false;
			}
		});
		
		$container.find('#srch_clear').unbind('click').click(function() {
			//$container.find('#srch_fld').attr('value','').keyup();
			// Refresh after searchfield was cleared
			var e = jQuery.Event("keydown");
			e.keyCode = jQuery.ui.keyCode.ENTER;
			$container.find('#srch_fld').attr('value','').focus().trigger(e);
		});
		

    $container.find('a.tooltip').tooltip({
		  delay: 500,
		  showURL: false,
		  track: true,
		  bodyHandler: function() {
			  return $(this).parents('li').find('span.tooltip-info').html();
		  }
    });
    
    
    // Add the slider to the ImageDataObjectManager
    if($container.hasClass('ImageDataObjectManager')) {
			var MIN_IMG_SIZE = 25
			var MAX_IMG_SIZE = 300;
			var START_IMG_SIZE = 100;
			var new_image_size;
			$('.size-control').slider({
				
				// Stupid thing doesn't work. Have to force it with CSS
				startValue : (START_IMG_SIZE - MIN_IMG_SIZE) / ((MAX_IMG_SIZE - MIN_IMG_SIZE) / 100),
				slide : function(e, ui) {
					new_image_size = MIN_IMG_SIZE + (ui.value * ((MAX_IMG_SIZE - MIN_IMG_SIZE)/100));
					$('.grid li img.image').css({'width': new_image_size+'px'});
					$('.grid li').css({'width': new_image_size+'px', 'height' : new_image_size +'px'});
				},
				
				stop : function(e, ui) {
					new_image_size = MIN_IMG_SIZE + (ui.value * ((MAX_IMG_SIZE - MIN_IMG_SIZE)/100));				
					url = $(container_id).attr('href').replace(/\[imagesize\]=(.)*/, '[imagesize]='+Math.floor(new_image_size));
					refresh($container, url);
				}
			});
			
			$('.ui-slider-handle').css({'left' : $('#size-control-wrap').attr('class').replace('position','')+'px'});    
    
    }  
    // RelationDataObjectManager
    
    if($container.hasClass('RelationDataObjectManager')) {
			var $checkedList = $(container_id+'_CheckedList');
			$container.find('.actions input, .file-label input').unbind('change').change(function(e){
				if($(this).attr('type') == "radio") {
					$(this).parents('li').siblings('li').removeClass('selected');
					$(this).parents('li').toggleClass('selected');
					$checkedList.attr('value', ","+$(this).val()+",");
				}
				else {
					if ($container.hasClass('ManyMany')) {
						$(this).parents('li').toggleClass('selected');
					}
					else {
						if ($(this).attr('checked')) {
							$(this).parents('li').addClass('selected');
						}
						else {
							$(this).parents('li').removeClass('selected');
						}
					}
					val = ($(this).attr('checked')) ? $checkedList.val() + $(this).val()+"," : $checkedList.val().replace(","+$(this).val()+",",",");
					$checkedList.attr('value', val);
				}
			}).unbind('click').click(function(e) {
				if ($.browser.msie) {
					// stupid ie doesn't fire the change event on clicks before the input is blurred!
					this.blur();
				}
				e.stopPropagation();
			});
	
			$container.find('.actions input, .file-label input').each(function(i,e) {
				if($checkedList.val().indexOf(","+$(e).val()+",") != -1)
					$(e).attr('checked',true).parents('li').addClass('selected');
				else
					$(e).attr('checked',false).parents('li').removeClass('selected');
					
			});	
			
			$container.find('a[rel=clear]').unbind('click').click(function(e) {
			 $container.find('.actions input, .file-label input').each(function(i,e) {
			   $(e).attr('checked', false).parents('li').removeClass('selected');
			   $checkedList.attr('value','');
			 });
			});
			
  		$container.find('.only-related-control input').unbind('click').click(function(e) {
  			refresh($container, $(this).attr('value'));
  			$(this).attr('disabled', true);
  			e.stopPropagation();
  		});
				
    }
		
    // Columns. God forbid there are more than 10.
    cols = $('.list #dataobject-list li.head .fields-wrap .col').length;
    if(cols > 10) {
    	$('.list #dataobject-list li .fields-wrap .col').css({'width' : ((Math.floor(100/cols)) - 0.1) + '%'});
    }
    
    
  $(".ajax-loader").hide();  
    
};

$.fn.DataObjectManager.getPageHeight = function() {
    var windowHeight
    if (self.innerHeight) {	// all except Explorer
      windowHeight = self.innerHeight;
    } else if (document.documentElement && document.documentElement.clientHeight) { // Explorer 6 Strict Mode
      windowHeight = document.documentElement.clientHeight;
    } else if (document.body) { // other Explorers
      windowHeight = document.body.clientHeight;
    }	
    return windowHeight;
};

$.fn.DataObjectManager.getPageScroll = function() {
    var xScroll, yScroll;
    if (self.pageYOffset) {
      yScroll = self.pageYOffset;
      xScroll = self.pageXOffset;
    } else if (document.documentElement && document.documentElement.scrollTop) {	 // Explorer 6 Strict
      yScroll = document.documentElement.scrollTop;
      xScroll = document.documentElement.scrollLeft;
    } else if (document.body) {// all other Explorers
      yScroll = document.body.scrollTop;
      xScroll = document.body.scrollLeft;	
    }
    return new Array(xScroll,yScroll) 
};

$('.DataObjectManager').ajaxSend(function(e,r,s){  
// stupid hack for the cache killer script.
if(s.url.indexOf('EditorToolbar') == -1)
 $(".ajax-loader").show();  
});  
   
$('.DataObjectManager').ajaxStop(function(e,r,s){  
  $(".ajax-loader").hide();  
}); 
$('.DataObjectManager').livequery(function(){
   $(this).DataObjectManager();                           

});

})(jQuery);

/*
 * Show a jQuery dialog
 * The dialog will contain an iframe with the specified href.
 * Always call this function on the topmost document (i.e. via top.ShowDialog()) so that
 * the dialogs will all be in the topmost document body.
 */
function ShowDialog (id, href, dialogTitle, isModal, customWidth, customHeight) {
	var minWidth = 700;
	var minHeight = 600;
	
	if (typeof customWidth != 'undefined')
		minWidth = customWidth;
	if (typeof customHeight != 'undefined')
		minHeight = customHeight;

	// add ajax loader to dialog, to be shown until iframe is fully loaded
	var loadingText = ss.i18n._t('DialogDataObjectManager.LOADING', 'Loading');
	var ajaxLoader = '<div id="DialogAjaxLoader"><h2>' + loadingText + '...</h2><img src="dataobject_manager/images/ajax-loader-white.gif" alt="' + loadingText + '..." /></div>';
	// add iframe container div containing the iframe to the body
	jQuery('body').append('<div id="iframecontainer_'+id+'" class="iframe_wrap" style="display:none;"><iframe id="iframe_'+id+'" src="'+href+'" frameborder="0" width="' + (minWidth-40) + '" height="1"></iframe>'+ajaxLoader+'</div>');
	var domDialog = jQuery('#iframecontainer_'+id);
	
	var iframe = jQuery('#iframe_'+id);
	// set iframe height to the body height (+ some margin space) when iframe is fully loaded.
	iframe.load(function() {
        var iframe_height = Math.max(jQuery(this).contents().find('body').height() + 36, minHeight-100);
        jQuery(this).attr('height', iframe_height);
		
		// also remove dialog ajax loader, and enable dialog buttons
		top.RemoveDialogAjaxLoader();
		jQuery(".ui-button").attr("disabled",false).removeClass('ui-state-disabled');
    });
	
	var saveText = ss.i18n._t('DialogDataObjectManager.SAVE', 'Save');
	var closeText = ss.i18n._t('DialogDataObjectManager.CLOSE', 'Close');
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
	
	// options for form ajax submission
	var options = {
		dataType: 'json',
		beforeSerialize: function() {
			if (typeof iframe[0].contentWindow.tinyMCE !== 'undefined')
				iframe[0].contentWindow.tinyMCE.triggerSave();		
			if (typeof iframe[0].contentWindow.onBeforeSerialize == 'function')
				iframe[0].contentWindow.onBeforeSerialize();
		},		
		success: function(responseText, statusText, xhr, form) {
			// hide ajax loader in dialog button-pane
			domDialog.parent().find('#AjaxLoader').hide();
			
			var closePopup = false;
			
			// NEW!
			// Is result bad or good?
			try {
				var jsonData = responseText;

				if (jsonData['code'] == 'good') {
					iframe.contents().find('span[class="message required"]').remove();
					
					var statusMessage = domDialog.parent().find('#StatusMessage');
					statusMessage.html(jsonData['message']);
					statusMessage.show(500);									
				} 
				else {
					var statusMessage = domDialog.parent().find('#ErrorMessage');
					var error = '';
					
					//iframe.contents().find('span[class="message required"]').remove();
					if (jsonData.length > 0) {
						var firstMessage = '';
						
						for (var i = 0; i < jsonData.length; i++) {
							var field = iframe.contents().find('input[name="' + jsonData[i].fieldName + '"]');
							//field.parent().append('<span class="message required">' + jsonData[i].message + '</span>');
							if (!firstMessage.length)
								firstMessage = jsonData[i].message;
						}
						
						if (!firstMessage.length)
							statusMessage.html(ss.i18n._t('DialogDataObjectManager.VALIDATIONERROR', 'Data missing'));
						else
							statusMessage.html(firstMessage);
						statusMessage.show(500);
					}
					else {
						statusMessage.html(jsonData['message']);
						statusMessage.show(500);
					}
				}
				
				if (jsonData['closePopup'] == true) {
					closePopup = true;
					//domDialog.parent().fadeOut(2000, function() { domDialog.dialog('close') });
					//domDialog.dialog('close');
				}
			} 
			catch (e) {
				// Invalid JSON, show as a 'good' response, makes this improvement backward compatible
				var statusMessage = domDialog.parent().find('#StatusMessage');
				statusMessage.html(responseText);
				statusMessage.show(500);
			}
			
			// show status message (in dialog button-pane)
			//var statusMessage = domDialog.parent().find('#ErrorMessage');
			//statusMessage.html(responseText);
			//statusMessage.show(500);
			// refresh content in parent dataobjectmanager
			if (parentDialog.html()) {
				// here we need to refresh the parent dataobjectmanager in the iframe context
				// (iframe javascript functions are accessible via the contentWindow property on the iframe object)
				if (parentDialog.find('iframe').length > 0) {
					var parentDm = parentDialog.find('iframe').contents().find('#' + id);
					parentDialog.find('iframe')[0].contentWindow.refresh(parentDm, parentDm.attr('href'), null, false, closePopup);
				}
				else {
					var parentDm = jQuery('#' + id);
					refresh(parentDm, parentDm.attr('href'), null, false, closePopup);
				}
			}
			else {
				var parentDm = jQuery('#' + id);
				refresh(parentDm, parentDm.attr('href'), null, false, closePopup);
			}
			
			// Refresh dataobjectmanagers inside our own iframe, if we have modified relations during write
			if (iframe.contents().find('.DataObjectManager.RequestHandler').length) {
				iframe.contents().find('.DataObjectManager.RequestHandler').each(function() {
					iframe[0].contentWindow.refresh(jQuery(this), jQuery(this).attr('href'));
				});
			}
			
			// enable dialog buttons
			jQuery(".ui-button").attr("disabled",false).removeClass('ui-state-disabled');
			
			// Close on success?
			if (closePopup == true) {
				if (typeof iframe[0].contentWindow.onAfterClose == 'function')
					iframe[0].contentWindow.onAfterClose(true);				
				domDialog.dialog('close');
			}
		},
		error: function(responseText, statusText, xhr, form) {
			// hide ajax loader in dialog button-pane
			domDialog.parent().find('#AjaxLoader').hide();
			// show error message (in dialog button-pane)
			var errorMessage = domDialog.parent().find('#ErrorMessage');
			errorMessage.html(responseText);
			errorMessage.show(500);
			// enable dialog buttons
			jQuery(".ui-button").attr("disabled",false).removeClass('ui-state-disabled');
		}
	};
	
	var buttonOptions = {};
	buttonOptions[saveText] = function() {
		// let the popup decide if a save is to be performed, by triggering a beforeSave event inside the iframe,
		// which scripts can bind to and cancel the save (and do the save later by calling the passed save function)
		iframe[0].contentWindow.saveForm(function() {
			// disable dialog buttons
			jQuery(".ui-button").attr("disabled","disabled").addClass('ui-state-disabled');
			// hide status and error messages
			domDialog.parent().find('.Message').hide();
			// show ajax loader
			domDialog.parent().find('#AjaxLoader').show();
			// submit form via ajax

			// Uploadify iframe
			if (domDialog.find('iframe').contents().find('#DialogImageDataObjectManager_Popup_UploadifyForm').length || 
				domDialog.find('iframe').contents().find('#DialogImageDataObjectManager_Popup_EditUploadedForm').length) {
				var uploadifyForm = domDialog.find('iframe').contents().find('#DialogImageDataObjectManager_Popup_UploadifyForm');
				var editForm = domDialog.find('iframe').contents().find('#DialogImageDataObjectManager_Popup_EditUploadedForm');
				if (uploadifyForm.find('input[name=action_saveUploadifyForm]').length) {
					domDialog.parent().find('#AjaxLoader').hide();
					uploadifyForm.find('input[name=action_saveUploadifyForm]').click();
				}
				else if (editForm.find('input[name=action_saveEditUploadedForm]').length) {
					domDialog.parent().find('#AjaxLoader').hide();	
					editForm.find('input[name=action_saveEditUploadedForm]').click();
				}
			}
			// Normal iframe
			else {
				domDialog.find('iframe').contents().find('form:first').ajaxSubmit(options);
			}
		});
	};
	buttonOptions[closeText] = function(){
		jQuery(this).dialog("close");
	};
	
	// show jQuery dialog
	domDialog.dialog({
		modal: isModal,
		title: dialogTitle,
		width: Math.min(minWidth, jQuery(window).width()-6),
		height: Math.min(minHeight, jQuery(window).height()-6),
		show: 'fade',
		buttons: buttonOptions,
		create: function() {
			// disable dialog buttons (will be enabled when iframe content is fully loaded)
			jQuery(".ui-button").attr("disabled","disabled").addClass('ui-state-disabled');
			// add ajax loader and output messages to dialog button-pane
			var loadingText = ss.i18n._t('DialogDataObjectManager.LOADING', 'Loading');
			jQuery(this).parent().find('.ui-dialog-buttonpane').append('<div id="Output" class="' + id + '" style="float:left;"><div id="AjaxLoader" style="display:none;"><img src="dataobject_manager/images/ajax-loader-white.gif" alt="' + loadingText + '..." /></div><div id="StatusMessage" class="Message" style="display:none;"></div><div id="ErrorMessage" class="Message" style="display:none;"></div></div>');
		},
		close: function(event, ui){
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

/*
 * Show a jQuery dialog (wizard mode)
 * The dialog will contain an iframe with the specified href.
 * Always call this function on the topmost document (i.e. via top.ShowWizardDialog()) so that
 * the dialogs will all be in the topmost document body.
 */
function ShowWizardDialog (id, href, dialogTitle, isModal) {
	// add ajax loader to dialog, to be shown until iframe is fully loaded
	var loadingText = ss.i18n._t('DialogDataObjectManager.LOADING', 'Loading');
	var ajaxLoader = '<div id="DialogAjaxLoader"><h2>' + loadingText + '...</h2><img src="dataobject_manager/images/ajax-loader-white.gif" alt="' + loadingText + '..." /></div>';
	// add iframe container div containing the iframe to the body
	jQuery('body').append('<div id="iframecontainer_'+id+'" class="iframe_wrap" style="display:none;"><iframe id="iframe_'+id+'" src="'+href+'" frameborder="0" width="660" height="1"></iframe>'+ajaxLoader+'</div>');
	var domDialog = jQuery('#iframecontainer_'+id);
	
	var iframe = jQuery('#iframe_'+id);
	// set iframe height to the body height (+ some margin space) when iframe is fully loaded.
	iframe.load(function() {
        var iframe_height = Math.max(jQuery(this).contents().find('body').height() + 36, 500);
        jQuery(this).attr('height', iframe_height);
		
		// also remove dialog ajax loader, and enable dialog buttons
		top.RemoveDialogAjaxLoader();
		jQuery(".ui-button").attr("disabled",false).removeClass('ui-state-disabled');
    });
	
	var continueText = ss.i18n._t('DialogDataObjectManager.CONTINUE', 'Continue');
	var backText = ss.i18n._t('DialogDataObjectManager.BACK', 'Back');
	var saveText = ss.i18n._t('DialogDataObjectManager.SAVE', 'Save');
	var closeText = ss.i18n._t('DialogDataObjectManager.CLOSE', 'Close');
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
	
	// options for form ajax submission
	var options = {
		dataType: 'json',
		beforeSerialize: function() {
			if (typeof iframe[0].contentWindow.tinyMCE !== 'undefined')
				iframe[0].contentWindow.tinyMCE.triggerSave();		
			if (typeof iframe[0].contentWindow.onBeforeSerialize == 'function')
				iframe[0].contentWindow.onBeforeSerialize();
		},			
		success: function(responseText, statusText, xhr, form) {
			// hide ajax loader in dialog button-pane
			domDialog.parent().find('#AjaxLoader').hide();
			
			var closePopup = false;
			
			// NEW!
			// Is result bad or good?
			try {
				var jsonData = responseText;

				if (jsonData['code'] == 'good') {
					var statusMessage = domDialog.parent().find('#StatusMessage');
					statusMessage.html(jsonData['message']);
					statusMessage.show(500);									
				} 
				else {
					var statusMessage = domDialog.parent().find('#ErrorMessage');
					var error = '';
					
					//iframe.contents().find('span[class="message required"]').remove();
					if (jsonData.length > 0) {
						var firstMessage = '';
						
						for (var i = 0; i < jsonData.length; i++) {
							var field = iframe.contents().find('input[name="' + jsonData[i].fieldName + '"]');
							//field.parent().append('<span class="message required">' + jsonData[i].message + '</span>');
							if (!firstMessage.length)
								firstMessage = jsonData[i].message;
						}
						
						if (!firstMessage.length)
							statusMessage.html(ss.i18n._t('DialogDataObjectManager.VALIDATIONERROR', 'Data missing'));
						else
							statusMessage.html(firstMessage);
						statusMessage.show(500);
					}
					else {
						statusMessage.html(jsonData['message']);
						statusMessage.show(500);
					}
				}
				
				if (jsonData['closePopup'] == true) {
					closePopup = true;
					//domDialog.parent().fadeOut(2000, function() { domDialog.dialog('close') });
					//domDialog.dialog('close');
				}
			} 
			catch (e) {
				// Invalid JSON, show as a 'good' response, makes this improvement backward compatible
				var statusMessage = domDialog.parent().find('#StatusMessage');
				statusMessage.html(responseText);
				statusMessage.show(500);
			}
			
			// show status message (in dialog button-pane)
			//var statusMessage = domDialog.parent().find('#ErrorMessage');
			//statusMessage.html(responseText);
			//statusMessage.show(500);
			// refresh content in parent dataobjectmanager
			if (parentDialog.html()) {
				// here we need to refresh the parent dataobjectmanager in the iframe context
				// (iframe javascript functions are accessible via the contentWindow property on the iframe object)
				if (parentDialog.find('iframe').length > 0) {
					var parentDm = parentDialog.find('iframe').contents().find('#' + id);
					parentDialog.find('iframe')[0].contentWindow.refresh(parentDm, parentDm.attr('href'));
				}
				else {
					var parentDm = jQuery('#' + id);
					refresh(parentDm, parentDm.attr('href'));
				}
			}
			else {
				var parentDm = jQuery('#' + id);
				refresh(parentDm, parentDm.attr('href'));
			}
			
			// Refresh dataobjectmanagers inside our own iframe, if we have modified relations during write
			if (iframe.contents().find('.DataObjectManager.RequestHandler').length) {
				iframe.contents().find('.DataObjectManager.RequestHandler').each(function() {
					iframe[0].contentWindow.refresh(jQuery(this), jQuery(this).attr('href'));
				});
			}
			
			// enable dialog buttons
			jQuery(".ui-button").attr("disabled",false).removeClass('ui-state-disabled');
			
			// Close on success?
			if (closePopup == true) {
				if (typeof iframe[0].contentWindow.onAfterClose == 'function')
					iframe[0].contentWindow.onAfterClose(true);								
				domDialog.dialog('close');
			}
		},
		error: function(responseText, statusText, xhr, form) {
			// hide ajax loader in dialog button-pane
			domDialog.parent().find('#AjaxLoader').hide();
			// show error message (in dialog button-pane)
			var errorMessage = domDialog.parent().find('#ErrorMessage');
			errorMessage.html(responseText);
			errorMessage.show(500);
			// enable dialog buttons
			jQuery(".ui-button").attr("disabled",false).removeClass('ui-state-disabled');
		}
	};
	
	var buttonOptions = {};
	buttonOptions[continueText] = function() {
		jQuery(this).find('iframe')[0].contentWindow.gotoNextTab();
	}
	buttonOptions[saveText] = function() {
		if (!jQuery(this).find('iframe')[0].contentWindow.isLastTabValid())
			return;
		
		// let the popup decide if a save is to be performed, by triggering a beforeSave event inside the iframe,
		// which scripts can bind to and cancel the save (and do the save later by calling the passed save function)
		iframe[0].contentWindow.saveForm(function() {
			// disable dialog buttons
			jQuery(".ui-button").attr("disabled","disabled").addClass('ui-state-disabled');
			// hide status and error messages
			domDialog.parent().find('.Message').hide();
			// show ajax loader
			domDialog.parent().find('#AjaxLoader').show();
			// submit form via ajax
			domDialog.find('iframe').contents().find('form').ajaxSubmit(options);
		});
	};
	buttonOptions[backText] = function() {
		jQuery(this).find('iframe')[0].contentWindow.gotoPrevTab();
	}		
	buttonOptions[closeText] = function(){
		jQuery(this).dialog("close");
	};
	
	// show jQuery dialog
	domDialog.dialog({
		modal: isModal,
		title: dialogTitle,
		width: Math.min(700, jQuery(window).width()-6),
		height: Math.min(600, jQuery(window).height()-6),
		show: 'fade',
		buttons: buttonOptions,
		create: function() {
			// disable dialog buttons (will be enabled when iframe content is fully loaded)
			jQuery(".ui-button").attr("disabled","disabled").addClass('ui-state-disabled');
			TabChangedTo('first');
			// add ajax loader and output messages to dialog button-pane
			var loadingText = ss.i18n._t('DialogDataObjectManager.LOADING', 'Loading');
			jQuery(this).parent().find('.ui-dialog-buttonpane').append('<div id="Output" style="float:left;"><div id="AjaxLoader" style="display:none;"><img src="dataobject_manager/images/ajax-loader-white.gif" alt="' + loadingText + '..." /></div><div id="StatusMessage" class="Message" style="display:none;"></div><div id="ErrorMessage" class="Message" style="display:none;"></div></div>');
		},
		close: function(event, ui){
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

// Function for setting the iframe height on the last jQuery dialog shown
function SetIframeHeight(customHeight) {
	var dialog = jQuery('.ui-dialog').last();
	var iframe = dialog.find('iframe');
	var iframe_height = Math.max(iframe.contents().find('body').height() + 36, 500);
	if (typeof customHeight != 'undefined')
		iframe_height = customHeight;
    iframe.attr('height', iframe_height);
}

function CloseLastDataObjectManager(doRefresh) {
	var dialog = jQuery('.ui-dialog').last();
	var parentDialog = dialog.siblings('.ui-dialog').last();
	var iframe = dialog.find('iframe');

	if (typeof doRefresh != 'undefined' && doRefresh == true) {

		if (parentDialog.html()) {
			// here we need to refresh the parent dataobjectmanager in the iframe context
			// (iframe javascript functions are accessible via the contentWindow property on the iframe object)
			if (parentDialog.find('iframe').length > 0) {
				parentDialog.find('iframe').contents().find('.DataObjectManager').each(function() {
					var parentDm = jQuery(this);
					parentDialog.find('iframe')[0].contentWindow.refresh(parentDm, parentDm.attr('href'));
				});
			}
			else {
				jQuery('.DataObjectManager').each(function () {
					var parentDm = jQuery(this);
					refresh(parentDm, parentDm.attr('href'));
				});
			}

			// enable dialog buttons
			jQuery(".ui-button", parentDialog).attr("disabled",false).removeClass('ui-state-disabled');		
		}
		else {
			jQuery('.DataObjectManager').each(function () {
				var parentDm = jQuery(this);
				refresh(parentDm, parentDm.attr('href'));
			});
		}
	}

	iframe.parent().dialog('close');
}

function RemoveDialogAjaxLoader() {
	jQuery('#DialogAjaxLoader').remove();
}

function GetPreviousDialog() {
	var previousDialog = jQuery('.ui-dialog:last').prevAll('.ui-dialog:first');
	return previousDialog;
}

function RemoveSaveButton() {
	var dialog = jQuery('.ui-dialog').last();
	if (jQuery(".ui-dialog-buttonset .ui-button", dialog).length == 2)
		jQuery(".ui-dialog-buttonset .ui-button:first", dialog).hide();
}

function TabChangedTo(position) {
	var dialog = jQuery('.ui-dialog').last();
	if (position == "initial") {
		jQuery(".ui-dialog-buttonset .ui-button", dialog).each(function(index) {
			if (index == 3)
				jQuery(this).show();
			else 
				jQuery(this).hide();
		});		
	}
	else if (position == 'first') {
		jQuery(".ui-dialog-buttonset .ui-button", dialog).each(function(index) {
			if (index == 0)
				jQuery(this).show();
			else if (index == 3)
				jQuery(this).show();
			else 
				jQuery(this).hide();
		});
	}
	else if (position == 'last') {
		jQuery(".ui-dialog-buttonset .ui-button", dialog).each(function(index) {
			if (index == 1)
				jQuery(this).show();
			else if (index == 2)
				jQuery(this).show();
			else 
				jQuery(this).hide();
		});
	}
	else {
		jQuery(".ui-dialog-buttonset .ui-button", dialog).each(function(index) {
			if (index == 0)
				jQuery(this).show();
			else if (index == 2)
				jQuery(this).show();
			else 
				jQuery(this).hide();
		});
	}
}

function refresh($div, link, focus, afterSave, refreshCheckedList)
{
	 // Kind of a hack. Pass the list of ids to the next refresh
	 var listValue = ($div.hasClass('RelationDataObjectManager')) ? jQuery('#'+$div.attr('id')+'_CheckedList').val() : false;
	 var $container = jQuery('#'+$div.attr('id')); 	
	 if ($container.find('div.dataobject-list').length == 0) {		
		 return false;
	 }
	 
	 var loadingText = ss.i18n._t('DialogDataObjectManager.LOADING', 'Loading');
	 $container.find('div.dataobject-list').block({message: '<h2 style="padding-top: 5px; padding-bottom: 5px; background-color: transparent;"><img style="vertical-align: middle; margin-right: 20px" src="dataobject_manager/images/ajax-loader-white.gif" alt="' + loadingText + '..." />' + loadingText + '...</h2>',
													css: {'background-color': '#fff'}});
	 	 
	 jQuery.ajax({
	   type: "GET",
	   url: link,
	   success: function(html){
	   		if(!$div.next().length && !$div.prev().length)
	   			$div.parent().html(html);
	   		else
				$div.replaceWith(html);
        	
			if(listValue) {
				if (typeof refreshCheckedList == 'undefined' || (typeof refreshCheckedList != 'undefined' && refreshCheckedList == false)) 
					jQuery('#'+$div.attr('id')+'_CheckedList').val(listValue);
			}
			var $container = jQuery('#'+$div.attr('id')); 
			$container.DataObjectManager();
			if (typeof focus == 'string') { 
				$container.find(focus).focus(); 
			}
			$container.find('div.dataobject-list').unblock();			
			$container.find('li.data').effect('highlight');
			top.SetIframeHeight();
			jQuery(document).trigger($div.attr('id') + '_refresh');
			
			if (typeof afterSave != 'undefined' && afterSave == true) 
				jQuery(document).trigger($div.attr('id') + '_aftersave');
		}
	 });
}

/*
 * Show a jQuery dialog (draft mode)
 * The dialog will contain an iframe with the specified href.
 * Always call this function on the topmost document (i.e. via top.ShowDraftDialog()) so that
 * the dialogs will all be in the topmost document body.
 */
 var draftSaveFunction = draftSaveFunction || null; // Will be defined when creating the dialog
 var draftAutosavedOnTabIndex = draftAutosavedOnTabIndex || 0;
 var draftIsSaving = draftIsSaving || false;
 var lastSaveSuccess = lastSaveSuccess || false;
 var lastDraftStatus = lastDraftStatus || '';
 
function ShowDraftDialog (id, href, dialogTitle, isModal) {
	// add ajax loader to dialog, to be shown until iframe is fully loaded
	var loadingText = ss.i18n._t('DialogDataObjectManager.LOADING', 'Loading');
	var ajaxLoader = '<div id="DialogAjaxLoader"><h2>' + loadingText + '...</h2><img src="dataobject_manager/images/ajax-loader-white.gif" alt="' + loadingText + '..." /></div>';
	// add iframe container div containing the iframe to the body
	jQuery('body').append('<div id="iframecontainer_'+id+'" class="iframe_wrap" style="display:none;"><iframe id="iframe_'+id+'" src="'+href+'" frameborder="0" width="700" height="1"></iframe>'+ajaxLoader+'</div>');
	var domDialog = jQuery('#iframecontainer_'+id);
	
	var iframe = jQuery('#iframe_'+id);
	// set iframe height to the body height (+ some margin space) when iframe is fully loaded.
	iframe.load(function() {
        var iframe_height = Math.max(jQuery(this).contents().find('body').height() + 36, 500);
        jQuery(this).attr('height', iframe_height);
		
		// also remove dialog ajax loader, and enable dialog buttons
		top.RemoveDialogAjaxLoader();
		jQuery(".ui-button").attr("disabled",false).removeClass('ui-state-disabled');
    });
	
	var saveText = ss.i18n._t('DialogDataObjectManager.SAVE', 'Save');
	var publishText = ss.i18n._t('DialogDataObjectManager.PUBLISH', 'Publish');
	var unpublishText = ss.i18n._t('DialogDataObjectManager.UNPUBLISH', 'Unpublish');
	var closeText = ss.i18n._t('DialogDataObjectManager.CLOSE', 'Close');
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
	
	// options for form ajax submission
	var options = {
		dataType: 'json',
		beforeSerialize: function() {
			if (typeof iframe[0].contentWindow.tinyMCE !== 'undefined')
				iframe[0].contentWindow.tinyMCE.triggerSave();		
			if (typeof iframe[0].contentWindow.onBeforeSerialize == 'function')
				iframe[0].contentWindow.onBeforeSerialize();
		},			
		success: function(responseText, statusText, xhr, form) {
			// hide ajax loader in dialog button-pane
			domDialog.parent().find('#AjaxLoader').hide();
			
			var closePopup = false;
			var enabledOnLoaded = false;
			var refreshOnLoaded = false;
			var refreshAfterSave = true;
			
			// NEW!
			// Is result bad or good?
			try {
				var jsonData = responseText;

				if (jsonData['code'] == 'good') {
					var statusMessage = domDialog.parent().find('#StatusMessage');
					
					if (iframe.attr('src').indexOf('add?SecurityID') != -1) { // First save when adding
						var newSrc = iframe.attr('src').replace('add?SecurityID', 'item/' + jsonData['id'] + '/edit?SecurityID');
						
						iframe.attr('src', newSrc);						
						
						enabledOnLoaded = true;
						refreshOnLoaded = true;
						
						iframe.load(function() {
							domDialog.dialog('option', 'title', jsonData['dialog_title']);
							domDialog.parent().find('#FirstSaveMessage').hide();
							domDialog.unblock();
							domDialog.find('iframe').show();
							statusMessage.html(jsonData['message_saved']);
							statusMessage.show(500);

							iframe[0].contentWindow.GotoTab(draftAutosavedOnTabIndex, true);
						});
					}
					else if (iframe.attr('src').indexOf('duplicate?SecurityID') != -1) { // First save when duplicating
						var newSrc = iframe.attr('src').replace(/item\/(\d+)\/duplicate\?SecurityID/, 'item/' + jsonData['id'] + '/edit?SecurityID');
						
						iframe.attr('src', newSrc);						
						
						enabledOnLoaded = true;
						refreshOnLoaded = true;
						
						iframe.load(function() {
							domDialog.dialog('option', 'title', jsonData['dialog_title']);
							domDialog.parent().find('#FirstSaveMessage').hide();							
							domDialog.unblock();
							domDialog.find('iframe').show();
							statusMessage.html(jsonData['message_saved']);
							statusMessage.show(500);
							
							iframe[0].contentWindow.GotoTab(draftAutosavedOnTabIndex, true);
						});
					}					
					else {
						if (!statusMessage.hasClass('silent')) {
							statusMessage.html(jsonData['message']);
							statusMessage.show(500);
						}
						else
							refreshAfterSave = false;
					}
				} 
				else {
					var statusMessage = domDialog.parent().find('#ErrorMessage');
					var error = '';
					
					//iframe.contents().find('span[class="message required"]').remove();
					if (jsonData.length > 0) {
						var firstMessage = '';
						
						for (var i = 0; i < jsonData.length; i++) {
							var field = iframe.contents().find('input[name="' + jsonData[i].fieldName + '"]');
							//field.parent().append('<span class="message required">' + jsonData[i].message + '</span>');
							if (!firstMessage.length)
								firstMessage = jsonData[i].message;
						}
						
						if (!statusMessage.hasClass('silent')) {
							if (!firstMessage.length)
								statusMessage.html(ss.i18n._t('DialogDataObjectManager.VALIDATIONERROR', 'Data missing'));
							else
								statusMessage.html(firstMessage);
							statusMessage.show(500);
						}
					}
					else {
						if (!statusMessage.hasClass('silent')) {
							statusMessage.html(jsonData['message']);
							statusMessage.show(500);
						}
					}					
					
					domDialog.parent().find('#FirstSaveMessage').hide();							
					domDialog.unblock();
					domDialog.find('iframe').show();
									
					iframe.contents().find('input[name=Status][type=radio]').removeAttr('checked');
					iframe.contents().find('input[name=Status][type=radio][value=' + lastDraftStatus + ']').click();
				}
				
				if (jsonData['closePopup'] == true) {
					closePopup = true;
					//domDialog.parent().fadeOut(2000, function() { domDialog.dialog('close') });
					//domDialog.dialog('close');
				}
			} 
			catch (e) {
				// Invalid JSON, show as a 'good' response, makes this improvement backward compatible
				var statusMessage = domDialog.parent().find('#StatusMessage');
				domDialog.parent().find('#FirstSaveMessage').hide();							
				domDialog.unblock();
				domDialog.find('iframe').show();
				if (!statusMessage.hasClass('silent')) {
					statusMessage.html(responseText);
					statusMessage.show(500);
				}
			}
			
			// show status message (in dialog button-pane)
			//var statusMessage = domDialog.parent().find('#ErrorMessage');
			//statusMessage.html(responseText);
			//statusMessage.show(500);
			// refresh content in parent dataobjectmanager
			var afterLoadFunction = function() {
				if (parentDialog.html()) {
					// here we need to refresh the parent dataobjectmanager in the iframe context
					// (iframe javascript functions are accessible via the contentWindow property on the iframe object)
					if (parentDialog.find('iframe').length > 0) {
						var parentDm = parentDialog.find('iframe').contents().find('#' + id);
						parentDialog.find('iframe')[0].contentWindow.refresh(parentDm, parentDm.attr('href'), null, true);
					}
					else {
						var parentDm = jQuery('#' + id);
						refresh(parentDm, parentDm.attr('href'), null, true);
					}
				}
				else {
					var parentDm = jQuery('#' + id);
					refresh(parentDm, parentDm.attr('href'), null, true);
				}

				// Refresh dataobjectmanagers inside our own iframe, if we have modified relations during write
				if (iframe.contents().find('.DataObjectManager.RequestHandler').length) {
					iframe.contents().find('.DataObjectManager.RequestHandler').each(function() {
						iframe[0].contentWindow.refresh(jQuery(this), jQuery(this).attr('href'), null, true);
					});
				}
			};
			
			if (refreshAfterSave) {
				if (refreshOnLoaded)
					iframe.load(afterLoadFunction);
				else
					afterLoadFunction();					
			}
			
			// enable dialog buttons
			if (enabledOnLoaded) {
				iframe.load(function() { 
					jQuery(".ui-button").attr("disabled",false).removeClass('ui-state-disabled'); 
					draftIsSaving = false; 
					lastSaveSuccess = true; 
										
					var currentStatus = iframe.contents().find('input[name=Status]:checked').val();
					var canPublishDirectly = iframe.contents().find('input[name=UserCanPublishDirectly]').val();
					
					var associationID = parseInt(iframe.contents().find('input[name=AssociationID]').val()) || '0';
					var associationPublishable = false;
					if (associationID != '0' && iframe[0].contentWindow.AssociationsPublishable[associationID] == '1')
						associationPublishable = true;
										
					if ((currentStatus == 'Preliminary' && (canPublishDirectly == '0' || !associationPublishable)) || currentStatus == 'Accepted') {
						setVisibleDraftButtons('[unpublish],[save]', domDialog.parent());
					}
					else
						setVisibleDraftButtons('[publish],[save]', domDialog.parent());
				});
			}
			else {
				jQuery(".ui-button").attr("disabled",false).removeClass('ui-state-disabled');
				draftIsSaving = false;
				lastSaveSuccess = true;
				
				var currentStatus = iframe.contents().find('input[name=Status]:checked').val();								
				var canPublishDirectly = iframe.contents().find('input[name=UserCanPublishDirectly]').val();
				
				var associationID = parseInt(iframe.contents().find('input[name=AssociationID]').val()) || '0';
				var associationPublishable = false;
				if (associationID != '0' && iframe[0].contentWindow.AssociationsPublishable[associationID] == '1')
					associationPublishable = true;
				
				if ((currentStatus == 'Preliminary' && (canPublishDirectly == '0' || !associationPublishable)) || currentStatus == 'Accepted') {
					setVisibleDraftButtons('[unpublish],[save]', domDialog.parent());
				}
				else {
					setVisibleDraftButtons('[publish],[save]', domDialog.parent());
				}
			}
			
			// Close on success?
			if (closePopup == true) {
				if (typeof iframe[0].contentWindow.onAfterClose == 'function')
					iframe[0].contentWindow.onAfterClose(true);						
				domDialog.dialog('close');	
			}
		},
		error: function(responseText, statusText, xhr, form) {
			// hide ajax loader in dialog button-pane
			domDialog.parent().find('#AjaxLoader').hide();
			domDialog.parent().find('#FirstSaveMessage').hide();							
			domDialog.unblock();
			domDialog.find('iframe').show();
			// show error message (in dialog button-pane)
			var errorMessage = domDialog.parent().find('#ErrorMessage');
			errorMessage.html(responseText);
			errorMessage.show(500);
			// enable dialog buttons
			jQuery(".ui-button").attr("disabled",false).removeClass('ui-state-disabled');
			
			draftIsSaving = false;
			lastSaveSuccess = false;
			
			iframe.contents().find('input[name=Status][type=radio]').removeAttr('checked');
			iframe.contents().find('input[name=Status][type=radio][value=' + lastDraftStatus + ']').click();		
		}
	};
	
	draftSaveFunction = function(silent, updateLastStatus) {
		if (draftIsSaving == false) {
			var currentStatus = iframe.contents().find('input[name=Status]:checked').val();
			if ((currentStatus == 'Accepted' || currentStatus == 'Preliminary') && silent == true) // Dont autosave a published event
				return;
			
			if (updateLastStatus == true)
				lastDraftStatus = currentStatus;
			
			// disable dialog buttons
			jQuery(".ui-button").attr("disabled","disabled").addClass('ui-state-disabled');
			// hide status and error messages
			domDialog.parent().find('.Message').hide();
			if (silent == true)
				domDialog.parent().find('.Message').addClass('silent');
			else
				domDialog.parent().find('.Message').removeClass('silent');

			// special on add/duplicate first save
			if (iframe.attr('src').indexOf('add?SecurityID') != -1 || iframe.attr('src').indexOf('duplicate?SecurityID') != -1) {
				domDialog.parent().find('#AjaxLoader').hide();
				domDialog.find('iframe').hide();
				domDialog.block({message: ''});
				domDialog.parent().find('#FirstSaveMessage').show();
				domDialog.parent().find('#FirstSaveMessage').html(ss.i18n._t('DialogDataObjectManager.FIRSTSAVEMSG', 'Saving for the first time, please wait.'));
			}
			else
				domDialog.parent().find('#AjaxLoader').show();			
		
			// submit form via ajax
			domDialog.find('iframe').contents().find('form').ajaxSubmit(options);		
			draftIsSaving = true;
		}
	};
	
	var buttonOptions = {};
	buttonOptions[publishText] = function() {
		// let the popup decide if a save is to be performed, by triggering a beforeSave event inside the iframe,
		// which scripts can bind to and cancel the save (and do the save later by calling the passed save function)
		iframe[0].contentWindow.publishForm(function() {		
			lastDraftStatus = iframe.contents().find('input[name=Status]:checked').val();
		
			iframe.contents().find('input[name=Status][type=radio]').removeAttr('checked');
			iframe.contents().find('input[name=Status][type=radio][value=Preliminary]').click();
			iframe.contents().find('input[name=Status][type=radio][value=Accepted]').click();
		
			draftSaveFunction(false, false);
		});
	}
	buttonOptions[unpublishText] = function() {
		// let the popup decide if a save is to be performed, by triggering a beforeSave event inside the iframe,
		// which scripts can bind to and cancel the save (and do the save later by calling the passed save function)
		iframe[0].contentWindow.unpublishForm(function() {		
			lastDraftStatus = iframe.contents().find('input[name=Status]:checked').val();
		
			iframe.contents().find('input[name=Status][type=radio]').removeAttr('checked');
			iframe.contents().find('input[name=Status][type=radio][value=Cancelled]').click();
		
			draftSaveFunction(false, false);
		});
	}	
	buttonOptions[saveText] = function() {
		// let the popup decide if a save is to be performed, by triggering a beforeSave event inside the iframe,
		// which scripts can bind to and cancel the save (and do the save later by calling the passed save function)
		iframe[0].contentWindow.saveForm(function() {
			draftSaveFunction(false, true);
		});
	};
	buttonOptions[closeText] = function(){
		jQuery(this).dialog("close");
	};
	
	var autoSaveTimer = null;
	
	// show jQuery dialog
	domDialog.dialog({
		modal: isModal,
		title: dialogTitle,
		width: Math.min(740, jQuery(window).width()-6),
		height: Math.min(600, jQuery(window).height()-6),
		show: 'fade',
		buttons: buttonOptions,
		create: function() {
			// disable dialog buttons (will be enabled when iframe content is fully loaded)
			jQuery(".ui-button").attr("disabled","disabled").addClass('ui-state-disabled');

			setVisibleDraftButtons('[save]');
			autoSaveTimer = setInterval(function() { AutosaveDraft(true, iframe[0].contentWindow.GetCurrentTabIndex()); }, 1000*60*5); // 5 minutes

			// add ajax loader and output messages to dialog button-pane
			var loadingText = ss.i18n._t('DialogDataObjectManager.LOADING', 'Loading');
			jQuery(this).parent().find('.ui-dialog-buttonpane').append('<div id="Output" style="float:left; width: 400px;"><div id="AjaxLoader" style="display:none;"><img src="dataobject_manager/images/ajax-loader-white.gif" alt="' + loadingText + '..." /></div><div id="StatusMessage" class="Message" style="display:none;"></div><div id="FirstSaveMessage" class="Message" style="display:none;"></div><div id="ErrorMessage" class="Message" style="display:none;"></div></div>');
		},
		close: function(event, ui){
			if (autoSaveTimer != null)
				clearInterval(autoSaveTimer);
			
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

function AutosaveDraft(save, tabIndex) {
	if (typeof draftSaveFunction == 'function' && save == true) {
		draftAutosavedOnTabIndex = tabIndex;
		draftSaveFunction(true, true);
	}
}

function setVisibleDraftButtons(buttons, specificDialog) {
	
	var dialog = jQuery('.ui-dialog').last();
	
	if (typeof specificDialog !== 'undefined')
		dialog = specificDialog;
		
	jQuery(".ui-dialog-buttonset .ui-button", dialog).each(function(index) {
		if (index == 0) {
			if (buttons.indexOf('[publish]') != -1)
				jQuery(this).show();
			else
				jQuery(this).hide();
		}
			
		if (index == 1) {
			if (buttons.indexOf('[unpublish]') != -1)
				jQuery(this).show();
			else
				jQuery(this).hide();
		}
		
		if (index == 2) {
			if (buttons.indexOf('[save]') != -1) {
				jQuery(this).show();
				if (buttons.indexOf('[publish]') != -1)
					jQuery('.ui-button-text', this).html(ss.i18n._t('DialogDataObjectManager.SAVE_UNPUBLISHED', 'Save as draft'));
				else if (buttons.indexOf('[unpublish]') != -1)
					jQuery('.ui-button-text', this).html(ss.i18n._t('DialogDataObjectManager.SAVE_PUBLISHED', 'Save changes'));
			}
			else
				jQuery(this).hide();
		}		
	});
}

function enableDialogButtons(enabled, specificDialog) {
	var dialog = jQuery('.ui-dialog').last();
	
	if (typeof specificDialog !== 'undefined')
		dialog = specificDialog;
	
	if (enabled == true)
		jQuery(".ui-button", dialog).attr("disabled",false).removeClass('ui-state-disabled');
	else
		jQuery(".ui-button", dialog).attr("disabled","disabled").addClass('ui-state-disabled');
}

/*
 * Show a jQuery dialog
 * The dialog will contain an iframe with the specified href.
 * Always call this function on the topmost document (i.e. via top.ShowStatusDialog()) so that
 * the dialogs will all be in the topmost document body.
 */

var lastStatus = lastStatus || '';

function ShowStatusDialog (id, href, dialogTitle, isModal) {
	// add ajax loader to dialog, to be shown until iframe is fully loaded
	var loadingText = ss.i18n._t('DialogDataObjectManager.LOADING', 'Loading');
	var ajaxLoader = '<div id="DialogAjaxLoader"><h2>' + loadingText + '...</h2><img src="dataobject_manager/images/ajax-loader-white.gif" alt="' + loadingText + '..." /></div>';
	// add iframe container div containing the iframe to the body
	jQuery('body').append('<div id="iframecontainer_'+id+'" class="iframe_wrap" style="display:none;"><iframe id="iframe_'+id+'" src="'+href+'" frameborder="0" width="660" height="1"></iframe>'+ajaxLoader+'</div>');
	var domDialog = jQuery('#iframecontainer_'+id);
	
	var iframe = jQuery('#iframe_'+id);
	// set iframe height to the body height (+ some margin space) when iframe is fully loaded.
	iframe.load(function() {
        var iframe_height = Math.max(jQuery(this).contents().find('body').height() + 36, 500);
        jQuery(this).attr('height', iframe_height);
		
		// also remove dialog ajax loader, and enable dialog buttons
		top.RemoveDialogAjaxLoader();
		jQuery(".ui-button").attr("disabled",false).removeClass('ui-state-disabled');
    });
	
	var saveText = ss.i18n._t('DialogDataObjectManager.SAVE', 'Save');
	var acceptText = ss.i18n._t('DialogDataObjectManager.ACCEPT', 'Accept');
	var activateText = ss.i18n._t('DialogDataObjectManager.ACTIVATE', 'Activate');
	var deactivateText = ss.i18n._t('DialogDataObjectManager.DEACTIVATE', 'Deactivate');
	var rejectText = ss.i18n._t('DialogDataObjectManager.REJECT', 'Reject');
	var closeText = ss.i18n._t('DialogDataObjectManager.CLOSE', 'Close');
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
	
	// options for form ajax submission
	var options = {
		dataType: 'json',
		beforeSerialize: function() {
			if (typeof iframe[0].contentWindow.tinyMCE !== 'undefined')
				iframe[0].contentWindow.tinyMCE.triggerSave();		
			if (typeof iframe[0].contentWindow.onBeforeSerialize == 'function')
				iframe[0].contentWindow.onBeforeSerialize();
		},		
		success: function(responseText, statusText, xhr, form) {
			// hide ajax loader in dialog button-pane
			domDialog.parent().find('#AjaxLoader').hide();
			
			var closePopup = false;
			
			// NEW!
			// Is result bad or good?
			try {
				var jsonData = responseText;

				if (jsonData['code'] == 'good') {
					iframe.contents().find('span[class="message required"]').remove();
					
					var statusMessage = domDialog.parent().find('#StatusMessage');
					statusMessage.html(jsonData['message']);
					statusMessage.show(500);									
				} 
				else {
					var statusMessage = domDialog.parent().find('#ErrorMessage');
					var error = '';
					
					//iframe.contents().find('span[class="message required"]').remove();
					if (jsonData.length > 0) {
						var firstMessage = '';
						
						for (var i = 0; i < jsonData.length; i++) {
							var field = iframe.contents().find('input[name="' + jsonData[i].fieldName + '"]');
							//field.parent().append('<span class="message required">' + jsonData[i].message + '</span>');
							if (!firstMessage.length)
								firstMessage = jsonData[i].message;
						}
						
						if (!firstMessage.length)
							statusMessage.html(ss.i18n._t('DialogDataObjectManager.VALIDATIONERROR', 'Data missing'));
						else
							statusMessage.html(firstMessage);
						statusMessage.show(500);
					}
					else {
						statusMessage.html(jsonData['message']);
						statusMessage.show(500);
					}
					
					iframe.contents().find('input[name=Status][type=radio]').removeAttr('checked');
					iframe.contents().find('input[name=Status][type=radio][value=' + lastStatus + ']').click();					
				}
				
				if (jsonData['closePopup'] == true) {
					closePopup = true;
					//domDialog.parent().fadeOut(2000, function() { domDialog.dialog('close') });
					//domDialog.dialog('close');
				}
			} 
			catch (e) {
				// Invalid JSON, show as a 'good' response, makes this improvement backward compatible
				var statusMessage = domDialog.parent().find('#StatusMessage');
				statusMessage.html(responseText);
				statusMessage.show(500);
			}
			
			// show status message (in dialog button-pane)
			//var statusMessage = domDialog.parent().find('#ErrorMessage');
			//statusMessage.html(responseText);
			//statusMessage.show(500);
			// refresh content in parent dataobjectmanager
			if (parentDialog.html()) {
				// here we need to refresh the parent dataobjectmanager in the iframe context
				// (iframe javascript functions are accessible via the contentWindow property on the iframe object)
				if (parentDialog.find('iframe').length > 0) {
					var parentDm = parentDialog.find('iframe').contents().find('#' + id);
					parentDialog.find('iframe')[0].contentWindow.refresh(parentDm, parentDm.attr('href'), null, false, closePopup);
				}
				else {
					var parentDm = jQuery('#' + id);
					refresh(parentDm, parentDm.attr('href'), null, false, closePopup);
				}
			}
			else {
				var parentDm = jQuery('#' + id);
				refresh(parentDm, parentDm.attr('href'), null, false, closePopup);
			}
			
			// Refresh dataobjectmanagers inside our own iframe, if we have modified relations during write
			if (iframe.contents().find('.DataObjectManager.RequestHandler').length) {
				iframe.contents().find('.DataObjectManager.RequestHandler').each(function() {
					iframe[0].contentWindow.refresh(jQuery(this), jQuery(this).attr('href'));
				});
			}
			
			// enable dialog buttons
			jQuery(".ui-button").attr("disabled",false).removeClass('ui-state-disabled');
			
			var currentStatus = iframe.contents().find('input[name=Status]:checked').val();
			if (currentStatus == 'Active') {
				setVisibleStatusButtons('[deactivate],[save]', domDialog.parent());
			}
			else if (currentStatus == 'Passive') {
				setVisibleStatusButtons('[activate],[save]', domDialog.parent());
			}			
			else if (currentStatus == 'New') {
				setVisibleStatusButtons('[accept],[reject],[save]', domDialog.parent());
			}						
			
			// Close on success?
			if (closePopup == true) {
				if (typeof iframe[0].contentWindow.onAfterClose == 'function')
					iframe[0].contentWindow.onAfterClose(true);						
				domDialog.dialog('close');	
			}
		},
		error: function(responseText, statusText, xhr, form) {
			// hide ajax loader in dialog button-pane
			domDialog.parent().find('#AjaxLoader').hide();
			// show error message (in dialog button-pane)
			var errorMessage = domDialog.parent().find('#ErrorMessage');
			errorMessage.html(responseText);
			errorMessage.show(500);
			// enable dialog buttons
			jQuery(".ui-button").attr("disabled",false).removeClass('ui-state-disabled');
			
			iframe.contents().find('input[name=Status][type=radio]').removeAttr('checked');
			iframe.contents().find('input[name=Status][type=radio][value=' + lastStatus + ']').click();								
		}
	};
	
	var saveFunction = function() {
		// disable dialog buttons
		jQuery(".ui-button").attr("disabled","disabled").addClass('ui-state-disabled');
		// hide status and error messages
		domDialog.parent().find('.Message').hide();
		// show ajax loader
		domDialog.parent().find('#AjaxLoader').show();
		// submit form via ajax

		// Uploadify iframe
		if (domDialog.find('iframe').contents().find('#DialogImageDataObjectManager_Popup_UploadifyForm').length || 
			domDialog.find('iframe').contents().find('#DialogImageDataObjectManager_Popup_EditUploadedForm').length) {
			var uploadifyForm = domDialog.find('iframe').contents().find('#DialogImageDataObjectManager_Popup_UploadifyForm');
			var editForm = domDialog.find('iframe').contents().find('#DialogImageDataObjectManager_Popup_EditUploadedForm');
			if (uploadifyForm.find('input[name=action_saveUploadifyForm]').length) {
				domDialog.parent().find('#AjaxLoader').hide();
				uploadifyForm.find('input[name=action_saveUploadifyForm]').click();
			}
			else if (editForm.find('input[name=action_saveEditUploadedForm]').length) {
				domDialog.parent().find('#AjaxLoader').hide();	
				editForm.find('input[name=action_saveEditUploadedForm]').click();
			}
		}
		// Normal iframe
		else {
			domDialog.find('iframe').contents().find('form').ajaxSubmit(options);
		}
	};
	
	var buttonOptions = {};
	buttonOptions[acceptText] = function() {
		iframe[0].contentWindow.saveForm(function() {
			lastStatus = iframe.contents().find('input[name=Status]:checked').val();
		
			iframe.contents().find('input[name=Status][type=radio]').removeAttr('checked');
			iframe.contents().find('input[name=Status][type=radio][value=Active]').click();
			
			saveFunction();
		});
	};
	buttonOptions[activateText] = function() {
		iframe[0].contentWindow.saveForm(function() {
			lastStatus = iframe.contents().find('input[name=Status]:checked').val();
		
			iframe.contents().find('input[name=Status][type=radio]').removeAttr('checked');
			iframe.contents().find('input[name=Status][type=radio][value=Active]').click();			
			
			saveFunction();
		});
	};	
	buttonOptions[deactivateText] = function() {
		iframe[0].contentWindow.saveForm(function() {

			lastStatus = iframe.contents().find('input[name=Status]:checked').val();
		
			iframe.contents().find('input[name=Status][type=radio]').removeAttr('checked');
			iframe.contents().find('input[name=Status][type=radio][value=Passive]').click();
			
			saveFunction();
		});
	};		
	buttonOptions[rejectText] = function() {
		iframe[0].contentWindow.saveForm(function() {
			lastStatus = iframe.contents().find('input[name=Status]:checked').val();
		
			iframe.contents().find('input[name=Status][type=radio]').removeAttr('checked');
			iframe.contents().find('input[name=Status][type=radio][value=Passive]').click();			
			
			saveFunction();
		});		
	};			
	buttonOptions[saveText] = function() {
		// let the popup decide if a save is to be performed, by triggering a beforeSave event inside the iframe,
		// which scripts can bind to and cancel the save (and do the save later by calling the passed save function)
		iframe[0].contentWindow.saveForm(function() {
			saveFunction();
		});
	};
	buttonOptions[closeText] = function(){
		jQuery(this).dialog("close");
	};
	
	// show jQuery dialog
	domDialog.dialog({
		modal: isModal,
		title: dialogTitle,
		width: Math.min(700, jQuery(window).width()-6),
		height: Math.min(600, jQuery(window).height()-6),
		show: 'fade',
		buttons: buttonOptions,
		create: function() {
			// disable dialog buttons (will be enabled when iframe content is fully loaded)
			jQuery(".ui-button").attr("disabled","disabled").addClass('ui-state-disabled');
			
			setVisibleStatusButtons('[save]');
			
			// add ajax loader and output messages to dialog button-pane
			var loadingText = ss.i18n._t('DialogDataObjectManager.LOADING', 'Loading');
			jQuery(this).parent().find('.ui-dialog-buttonpane').append('<div id="Output" style="float:left; width: 380px"><div id="AjaxLoader" style="display:none;"><img src="dataobject_manager/images/ajax-loader-white.gif" alt="' + loadingText + '..." /></div><div id="StatusMessage" class="Message" style="display:none;"></div><div id="ErrorMessage" class="Message" style="display:none;"></div></div>');
		},
		close: function(event, ui){
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

function setVisibleStatusButtons(buttons, specificDialog) {
	
	var dialog = jQuery('.ui-dialog').last();
	
	if (typeof specificDialog !== 'undefined')
		dialog = specificDialog;
		
	jQuery(".ui-dialog-buttonset .ui-button", dialog).each(function(index) {
		if (index == 0) {
			if (buttons.indexOf('[accept]') != -1)
				jQuery(this).show();
			else
				jQuery(this).hide();
		}
			
		if (index == 1) {
			if (buttons.indexOf('[activate]') != -1)
				jQuery(this).show();
			else
				jQuery(this).hide();
		}
		
		if (index == 2) {
			if (buttons.indexOf('[deactivate]') != -1)
				jQuery(this).show();
			else
				jQuery(this).hide();
		}		
		
		if (index == 3) {
			if (buttons.indexOf('[reject]') != -1)
				jQuery(this).show();
			else
				jQuery(this).hide();
		}				
		
		if (index == 4) {
			if (buttons.indexOf('[save]') != -1) {
				jQuery(this).show();
			}
			else
				jQuery(this).hide();
		}		
	});
}