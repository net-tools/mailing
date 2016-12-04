<?php

// namespace
namespace Nettools\Mailing\MailPieces;


use \Nettools\Mailing\Mailer;



// class to deal with attachments
class MailAttachment extends MailMixedContent {

// [----- PROTECTED -----

	protected $_filename = NULL;


	// get attachments cache
	protected function _getCache()
	{
		return Mailer::getAttachmentsCache();
	}

// ----- PROTECTED -----]



// [----- PUBLIC -----

	// constructor
	public function __construct($file, $filename, $file_type, $ignoreCache = false)
	{
		parent::__construct($file, $file_type, $ignoreCache);
		$this->_filename = $filename;
	}
	
	
	// accessors
	public function getFileName() { return $this->_filename; }
	public function setFileName($f) { $this->_filename = $f; }
	

	// get headers for this part
	public function getHeaders()
	{
		return 	"Content-Type: " . $this->getContentType() . ";\r\n   name=\"" . $this->_filename . "\"\r\n" .
				"Content-Transfer-Encoding: base64\r\n" .
				"Content-Disposition: attachment;\r\n   filename=\"" . $this->_filename . "\"";
	}
}


?>