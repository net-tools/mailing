<?php

// namespace
namespace Nettools\Mailing\MailPieces;


use \Nettools\Mailing\Mailer;




// base class for defining a mail part : a content with a Content/Type
abstract class MailContent {

// [----- PROTECTED -----

	protected $_content_type = NULL;
	protected $_custom_headers = "";

// ----- PROTECTED -----]



// [----- PUBLIC -----

	// constructor
	public function __construct($content_type)
	{
		$this->_content_type = $content_type;
	}
	
	
	// accessors
	public function getContentType() { return $this->_content_type; }
	public function setContentType($c) { $this->_content_type = $c; }
	
	
	// get text for this part : headers and contents are merged ; used in MailMultipart::getContent()
	public function toString()
	{
		return $this->getFullHeaders() . "\r\n\r\n" . $this->getContent() . "\r\n\r\n";
	}
	
	
	// set custom headers
	public function setCustomHeaders($h)
	{
		$this->_custom_headers = $h;
	}


	// add a custom header
	public function addCustomHeader($h)
	{
		$this->_custom_headers = Mailer::addHeader($this->_custom_headers, $h);
	}


	// get custom headers
	public function getCustomHeaders()
	{
		return $this->_custom_headers;
	}


	// get headers for this part ; abstract method to implemented in child classes
	abstract public function getHeaders();
	
	
	// get headers (headers for this part and also custom headers defined by user)
	public function getFullHeaders()
	{
		return Mailer::addHeader($this->getHeaders(), $this->getCustomHeaders());
	}
	
	
	// get the text content of this part (to implement in child classes)
	abstract public function getContent();
}


?>