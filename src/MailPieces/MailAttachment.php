<?php

// namespace
namespace Nettools\Mailing\MailPieces;

// clauses use
use \Nettools\Mailing\MailPieces\MailMixedContent;
use \Nettools\Mailing\Mailer;



// classe de base : pièce-jointe
class MailAttachment extends MailMixedContent {

// [----- MEMBRES PROTEGES -----

	protected $_filename = NULL;


	// obtenir le cache spécifique
	protected function _getCache()
	{
		return Mailer::getAttachmentsCache();
	}

// ----- MEMBRES PROTEGES -----]



// [----- METHODES PUBLIQUES -----

	// constructeur
	public function __construct($file, $filename, $file_type, $ignoreCache = false)
	{
		parent::__construct($file, $file_type, $ignoreCache);
		$this->_filename = $filename;
	}
	
	
	// accesseurs
	public function getFileName() { return $this->_filename; }
	public function setFileName($f) { $this->_filename = $f; }
	

	// en-tete
	public function getHeaders()
	{
		return 	"Content-Type: " . $this->getContentType() . ";\r\n   name=\"" . $this->_filename . "\"\r\n" .
				"Content-Transfer-Encoding: base64\r\n" .
				"Content-Disposition: attachment;\r\n   filename=\"" . $this->_filename . "\"";
	}
}


?>