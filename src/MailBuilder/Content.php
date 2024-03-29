<?php
/**
 * Content
 *
 * @author Pierre - dev@nettools.ovh
 * @license MIT
 */


// namespace
namespace Nettools\Mailing\MailBuilder;


use \Nettools\Mailing\Mailer;
use \Nettools\Mailing\MailerEngine\Headers;





/** 
 * Base class for defining a mail part : a content with a Content/Type
 */
abstract class Content {

// [----- PROTECTED -----

    /** @var string Mime type of this part */
    protected $_content_type = NULL;
	
    /** @var Headers Object holding headers for this mail part */
    public $headers = NULL;

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
		$this->headers = new Headers([]);
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
	 * @see Multipart::getContent
     * @return string The text representation of this part (headers and content merged)
     */
	public function toString()
	{
		return $this->getAllHeaders()->toString() . "\r\n\r\n" . $this->getContent() . "\r\n\r\n";
	}
	
	
	/** 
     * Get headers for this part ; abstract method to implemented in child classes
	 *
	 * Must not be misunderstood with $headers property, which is for user-defined headers
     *
     * @return Headers Mandatory headers for this part
     */
	abstract public function getHeaders();
	
	
	/** 
     * Get all headers for this part
     * 
     * All headers are returned, both mandatory headers and user-defined headers
     *
     * @return Headers The headers of this part
     */
	public function getAllHeaders()
	{
		return Headers::fromObject($this->getHeaders())->mergeWith($this->headers);
	}
	
	
	/**
     * Get the text content of this part (to implement in child classes)
     *
     * @return string Returns a string representing the body of this part (headers excluded) 
     */
	abstract public function getContent();
}


?>