<?php

/**
 * Description of ComposedPdf
 *
 * @author Marcus Nyeholt <marcus@silverstripe.com.au>
 * @license BSD http://silverstripe.org/BSD-license
 */
class ComposedPdf extends DataObject {

	public static $db = array(
		'Title'					=> 'Varchar(125)',
		'Description'			=> 'HTMLText',
		'TableOfContents'		=> 'Boolean',
		'Template'				=> 'Varchar',
	);
	public static $defaults = array(
		
	);
	
	public static $has_one = array(
		'Page'					=> 'Page',
	);

	public static $has_many = array(
		'Pdfs'					=> 'ComposedPdfFile',
	);
	
	public function onBeforeWrite() {
		parent::onBeforeWrite();
		if ($this->ID && !$this->Title) {
			
			throw new Exception("Invalid title");
		}
	}
	
	public function getCMSFields() {
		$fields = parent::getCMSFields();
		
		if ($this->ID) {
			$fields->addFieldToTab('Root.Main', new LiteralField('PreviewLink', '<a href="admin/pdfs/previewpdf?ID=' . $this->ID.'" target="_blank">Preview</a>'), 'Description');
		}
		
		$fields->addFieldToTab('Root.Main', new CheckboxField('TableOfContents', _t('ComposedPdf.TOC', 'Table of contents?')), 'Description');
		$fields->addFieldToTab('Root.Main', new DropdownField('Template', _t('ComposedPdf.TEMPLATE', 'Template'), $this->templateSource()), 'Description');
		$fields->addFieldToTab('Root.Main', new TreeDropdownField('PageID', _t('ComposedPdf.ROOT_PAGE', 'Root Page'), 'Page'), 'Description');
		
		$pdfs = new TableListField(
			'Pdfs',
			'ComposedPdfFile',
			array(
					'Title'                                 => 'Title',
					'Created'                               => 'Generated',
					'ID'                                    => 'Links'
			),
			'"SourceID" = '.((int) $this->ID),
			'"Created" DESC'
		);

		$pdfs->setShowPagination(true);

		$links = '<a class=\'pdfDownloadLink\' target=\'blank\' href=\'".$Link()."\'>Download</a> ';

		$pdfs->setFieldFormatting(array(
				'ID' => $links,
		));
		
		$fields->addFieldToTab('Root.Pdfs', $pdfs);

		return $fields;
	}
	
	public function getCMSActions() {
		$actions = parent::getCMSActions();
		$actions->push(new FormAction('compose', _t('ComposedPdf.COMPOSE', 'Compose')));
		return $actions;
	}
	
	public function createPdf() {
		$storeIn = $this->getStorageFolder();
		$name = ereg_replace(' +','-',trim($this->Title));
		$name = ereg_replace('[^A-Za-z0-9.+_\-]','',$name);
		$name = $name . '.pdf';
		
		if (!$name) {
			throw new Exception("Must have a name!"); 
		}
		
		if (!$this->Template) {
			throw new Exception("Please specify a template before rendering");
		}

		$file = new ComposedPdfFile;
		$file->ParentID = $storeIn->ID;
		$file->SourceID = $this->ID;
		$file->Title = $this->Title;
		$file->setName($name);
		$file->write();

		$content = $this->renderPdf();
		$filename = singleton('PdfRenditionService')->render($content);
		
		if (file_exists($filename)) {
			copy($filename, $file->getFullPath());
		}
	}

	public function renderPdf() {
		Requirements::clear();
		$content = $this->renderWith($this->Template);
		Requirements::restore();
		
		return $content;
	}
	
	protected function getStorageFolder() {
		$id = $this->ID;
		$folderName = 'composed-pdfs/'.$this->ID;
		return Folder::findOrMake($folderName);
	}
		

	public static $template_paths = array();
	
	public function templatePaths() {
		if (!count(self::$template_paths)) {
			if (file_exists(Director::baseFolder() . DIRECTORY_SEPARATOR . THEMES_DIR . "/" . SSViewer::current_theme() . "/templates/pdfs")) {
				self::$template_paths[] = THEMES_DIR . "/" . SSViewer::current_theme() . "/templates/pdfs";
			}

			if (file_exists(Director::baseFolder() . DIRECTORY_SEPARATOR . project() . '/templates/pdfs')) {
				self::$template_paths[] = project() . '/templates/pdfs';
			}
			
			if (file_exists(Director::baseFolder() . DIRECTORY_SEPARATOR . 'pdfrendition/templates/pdfs')) {
				self::$template_paths[] = 'pdfrendition/templates/pdfs';
			}
		}

		return self::$template_paths;
	}

	/**
	 * Copied from NewsletterAdmin!
	 *
	 * @return array
	 */
	public function templateSource() {
		$paths = self::$this->templatePaths();
		$templates = array("" => _t('ComposedPdf.NONE', 'None'));

		if (isset($paths) && count($paths)) {
			$absPath = Director::baseFolder();
			if ($absPath{strlen($absPath) - 1} != "/")
				$absPath .= "/";

			foreach ($paths as $path) {
				$path = $absPath . $path;
				if (is_dir($path)) {
					$templateDir = opendir($path);

					// read all files in the directory
					while (( $templateFile = readdir($templateDir) ) !== false) {
						// *.ss files are templates
						if (preg_match('/(.*)\.ss$/', $templateFile, $match)) {
							$templates[$match[1]] = $match[1];
						}
					}
				}
			}
		}
		return $templates;
	}
}