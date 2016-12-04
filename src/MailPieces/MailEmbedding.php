<?php

// namespace
namespace Nettools\Mailing\MailPieces;


use \Nettools\Core\Helpers\FileHelper;
use \Nettools\Mailing\Mailer;




// class to deal with embeddings
class MailEmbedding extends MailMixedContent {

// [----- PROTECTED -----

	protected $_cid = NULL;


	// get embeddings cache
	protected function _getCache()
	{
		return Mailer::getEmbeddingsCache();
	}

// ----- PROTECTED -----]



// [----- PUBLIC -----

	// constructor
	public function __construct($file, $file_type, $cid, $ignoreCache = false)
	{
		// if file_type not provided, guess it from the filename
		parent::__construct($file, is_null($file_type) ? FileHelper::guessMimeType($file, "image/jpeg") : $file_type, $ignoreCache);
		$this->_cid = $cid;
	}
	
	
	// accessors
	public function getCid() { return $this->_cid; }
	public function setCid($c) { $this->_cid = $c; }
	
	
	// get headers for this part
	public function getHeaders()
	{
		return 	"Content-Type: " . $this->getContentType() . "\r\n" .
				"Content-Transfer-Encoding: base64\r\n" .
				"Content-Disposition: inline;\r\n   filename=\"" . $this->_cid . "\"\r\n" .
				"Content-ID: <" . $this->_cid . ">";
	}
}


?>