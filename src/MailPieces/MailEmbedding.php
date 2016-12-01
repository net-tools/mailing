<?php

// namespace
namespace Nettools\Mailing\MailPieces;

// clauses use
use \Nettools\Core\Helpers\FileHelper;
use \Nettools\Mailing\MailPieces\MailMixedContent;
use \Nettools\Mailing\Mailer;




// classe de base : fichier incorporé
class MailEmbedding extends MailMixedContent {

// [----- MEMBRES PROTEGES -----

	protected $_cid = NULL;


	// obtenir le cache spécifique
	protected function _getCache()
	{
		return Mailer::getEmbeddingsCache();
	}

// ----- MEMBRES PROTEGES -----]



// [----- METHODES PUBLIQUES -----

	// constructeur
	public function __construct($file, $file_type, $cid, $ignoreCache = false)
	{
		// si type non précisé, on le devine avec le nom du fichier
		parent::__construct($file, is_null($file_type) ? FileHelper::guessMimeType($file, "image/jpeg") : $file_type, $ignoreCache);
		$this->_cid = $cid;
	}
	
	
	// accesseurs
	public function getCid() { return $this->_cid; }
	public function setCid($c) { $this->_cid = $c; }
	
	
	// en-tete
	public function getHeaders()
	{
		return 	"Content-Type: " . $this->getContentType() . "\r\n" .
				"Content-Transfer-Encoding: base64\r\n" .
				"Content-Disposition: inline;\r\n   filename=\"" . $this->_cid . "\"\r\n" .
				"Content-ID: <" . $this->_cid . ">";
	}
}


?>