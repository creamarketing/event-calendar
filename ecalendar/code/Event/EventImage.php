<?php 

class EventImage extends DataObject {
	
	static $extensions = array(			
			'PermissionExtension',
			'TemporaryDataObjectOwner'		
	);
		
	static $db = array(
	);
	
	static $has_one = array(
			'Event' => 'Event',
			'Image'	=> 'Image',
	);
	
	function duplicate($doWrite = true) {
		$parentDuplicate = parent::duplicate($doWrite);
		if ($doWrite && $this->Image() && $this->Image()->exists()) {
			$duplicateImage = $this->Image()->DuplicateFile();
			$parentDuplicate->ImageID = $duplicateImage->ID;
		}
		return $parentDuplicate;
	}	
	
	public function onBeforeDelete() {
		parent::onBeforeDelete();
		
		if ($this->Image() && $this->Image()->exists())
			$this->Image()->delete();
	}	
 	
	public function getThumbnailImage() {
		if ($this->Image() && $this->Image()->exists()) {
			$thumbnailImage = $this->Image()->PaddedImage(48, 48);
			return '<a class="noClickPropagation" target="_blank" href="' . $this->Image()->Link(). '"><img width="48" height="48" src="'.$thumbnailImage->Link().'" alt="" border="0"></a>';
		}
		return '';
	}
	
	public function getFileIcon() {
		return '<img width="12" src="'.$this->Image()->Icon().'" alt="" border="0">';
	}	
	
	public function getLink() {
		return $this->Image()->Link();
	}
	
	public function getDownloadLink() {
		return $this->getFileIcon().'&nbsp;<a class="noClickPropagation" target="_blank" href="' . $this->Image()->Link(). '">' . _t('EventFile.DOWNLOAD', 'Download') . '</a>';
	}	
	
	public function getRequirementsForPopup() {
		Requirements::customCSS('.horizontal_tabs { margin-top: 0px; }');
		
		$customJS = <<<CUSTOM_JS
			jQuery(function() {
				jQuery(document).bind('Uploadify_busy', function() {
					top.enableDialogButtons(false);
				});

				jQuery(document).bind('Uploadify_ready', function() {
					top.enableDialogButtons(true);
				});	
				
				jQuery(document).bind('Uploadify_complete', function() {
					jQuery('.button_wrapper').closest('.horizontal_tabs').hide();
				});

				jQuery(document).bind('Uploadify_cancel', function() {
					jQuery('.button_wrapper').closest('.horizontal_tabs').show();
					jQuery('.button_wrapper .object_wrapper > input + object').css('visibility', 'hidden').css('visibility', 'visible');
				});	

				jQuery(document).bind('Uploadify_delete', function() {
					jQuery('.button_wrapper').closest('.horizontal_tabs').show();
					jQuery('.button_wrapper .object_wrapper > input + object').css('visibility', 'hidden').css('visibility', 'visible');
				});		

				if (!jQuery('.no_files').length)
					jQuery('.button_wrapper').closest('.horizontal_tabs').hide();
			});
			
			function onBeforeSerialize() {
				var inputs = jQuery('.UploadifyField .inputs'); 
				if (!inputs.find('input[name=ImageID]').length) 
					jQuery('<input type="hidden" name="ImageID" value="0">').appendTo(inputs);
			}
CUSTOM_JS;
		
		Requirements::customScript($customJS);
		
		$this->extend('getRequirementsForPopup');
	}
	
	public function getCMSFields() {		
		$fields = new FieldSet(				
			$DTSet = new DialogTabSet('TabSet',		
					$generalTab = new Tab(
						'GeneralTab', 
						_t('EventFile.GENERALTAB', 'General'),
						$filefield = new ImageUploadField('Image', _t('EventImage.SINGULARNAME', 'Event image')),
						new LabelField('MaxFilesize', sprintf(_t('EventFile.MAXFILESIZE', 'Max filesize: %s'), eCalendarExtension::formatBytes($filefield->getSetting('sizeLimit'))), null, true)
					)	
			)
		);
		
		$filefield->removeFolderSelection();
		$filefield->removeImporting();
		$filefield->setUploadFolder('events/images');
		$filefield->setBackend(false);
				
		$this->extend('updateCMSFields', $fields);	
		
		return $fields;
	}
	
	public function validate() {
		$data = Convert::raw2sql($_POST);
		
		if (isset($data['ImageID']) && empty($data['ImageID'])) {
			return new ValidationResult(false, _t('EventImage.ERROR_IMAGEMISSING', 'No image has been uploaded'));
		}
		
		return parent::validate();
	}	
}

?>