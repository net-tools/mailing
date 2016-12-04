<?php

// namespace
namespace Nettools\Mailing\MailPieces;



// base class for text/html part ; the text output will be in utf-8 charset and QP encoded
class MailTextHtmlContent extends MailContent {

// [----- PROTECTED -----

	protected $_html;

// ----- PROTECTED -----]



// [----- PUBLIC -----

	// constructor
	public function __construct($html)
	{
		parent::__construct("text/html");
		$this->_html = $html;
	}
	
	
	// accessors
	public function getHtml() { return $this->_html; }
	public function setHtml($t) { $this->_html = $t; }
	
	
	// headers
	public function getHeaders()
	{
		return 	"Content-Type: " . $this->getContentType() . "; charset=UTF-8\r\n" .
				"Content-Transfer-Encoding: quoted-printable";
	}
	
	
	// content
	public function getContent()
	{
		return trim(str_replace("=0A", "\n", str_replace("=0D", "\r", imap_8bit($this->_html)))) /*. "\r\n\r\n"*/;
	}
}



?>