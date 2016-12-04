<?php

// namespace
namespace Nettools\Mailing\MailPieces;



// base class for text/plain ; output will be in utf8 charset, QP encoded
class MailTextPlainContent extends MailContent {

// [----- PROTECTED -----

	protected $_text;

// ----- PROTECTED -----]



// [----- PUBLIC -----

	// constructor
	public function __construct($text)
	{
		parent::__construct("text/plain");
		$this->_text = $text;
	}
	
	
	// accessors
	public function getText() { return $this->_text; }
	public function setText($t) { $this->_text = $t; }
	
	
	// get headers
	public function getHeaders()
	{
		return 	"Content-Type: " . $this->getContentType() . "; charset=UTF-8\r\n" .
				"Content-Transfer-Encoding: quoted-printable";
	}
	
	
	// get content
	public function getContent()
	{
		return trim(str_replace("=0A", "\n", str_replace("=0D", "\r", imap_8bit($this->_text)))) /*. "\r\n\r\n"*/;
	}
}


?>