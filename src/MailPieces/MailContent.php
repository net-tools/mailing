<?php
/**
 * MailContent
 *
 * @author Pierre - dev@nettools.ovh
 * @license MIT
 */


// namespace
namespace Nettools\Mailing\MailPieces;


use \Nettools\Mailing\Mailer;




/** 
 * Base class for defining a mail part : a content with a Content/Type
 */
abstract class MailContent {

// [----- PROTECTED -----

    /** @var string Mime type of this part */
    protected $_content_type = NULL;
	
    /** @var string[] Array of custom headers (set by the user) for this part */
    protected $_custom_headers = [];

// ----- PROTECTED -----]



// [----- PUBLIC -----

	/** 
     * Constructor
     *
     * @param string $content_type Mime type of the part
     */
	public function __construct($content_type)
	{
		$this->_content_type = $content_type;
	}
	
	
	/** 
     * Get content-type accessor
     * 
     * @return string The mime type of the part
     */
	public function getContentType() { return $this->_content_type; }

    
	/** 
     * Set content-type accessor
     * 
     * @param string $c The mime type of the part
     */
	public function setContentType($c) { $this->_content_type = $c; }
	
	
	/**
     * Get text value for this part
     * 
     * Headers and contents are merged
     *
     * @see MailMultipart::getContent
     * @return string The text representation of this part (headers and content merged)
     */
	public function toString()
	{
		return Mailer::arrayToHeaders($this->getAllHeaders()) . "\r\n\r\n" . $this->getContent() . "\r\n\r\n";
	}
	
	
	/**
     * Set custom headers 
     *
     * To add one header at a time call addCustomHeader()
     * 
     * @see MailContent::addCustomHeader
     * @param string[] $h Array of headers
     */
	public function setCustomHeaders(array $h)
	{
		$this->_custom_headers = $h;
	}


	/**
     * Add a custom header
     *
     * @param string $k Header name to set
     * @param string $h Header value
     */
	public function addCustomHeader($n, $h)
	{
		$this->_custom_headers[$n] = $h;
	}


	/**
     * Get custom headers
     * 
     * @return string[] Get custom headers for this part
     */
	public function getCustomHeaders()
	{
		return $this->_custom_headers;
	}


	/** 
     * Get headers for this part ; abstract method to implemented in child classes
     *
     * @return string[] Mandatory headers for this part
     */
	abstract public function getHeaders();
	
	
	/** 
     * Get all headers for this part
     * 
     * All headers are returned, both mandatory headers and user-defined custom headers
     *
     * @return string[] The headers of this part
     */
	public function getAllHeaders()
	{
		return array_merge($this->getHeaders(), $this->getCustomHeaders());
	}
	
	
	/**
     * Get the text content of this part (to implement in child classes)
     *
     * @return string Returns a string representing the body of this part (headers excluded) 
     */
	abstract public function getContent();
}


?>