<?php
/**
 * MailTextHtmlContent
 *
 * @author Pierre - dev@net-tools.ovh
 * @license MIT
 */




// namespace
namespace Nettools\Mailing\MailPieces;



/** 
 * Base class for text/html part.
 *
 * The text output will be in utf-8 charset and QP encoded
 */
class MailTextHtmlContent extends MailContent {

// [----- PROTECTED -----

    /** @var string HTML raw data */
	protected $_html;

// ----- PROTECTED -----]



// [----- PUBLIC -----

	/**
     * Constructor
     *
     * @param string $html Raw html to this part
     */
	public function __construct($html)
	{
		parent::__construct("text/html");
		$this->_html = $html;
	}
	
	
	/**
     * Get Html accessor
     * 
     * @return string Raw HTML data
     */
	public function getHtml() { return $this->_html; }

    
	/**
     * Set Html accessor
     * 
     * @param string $html Raw HTML data
     */
    public function setHtml($html) { $this->_html = $html; }
	
	
	public function getHeaders()
	{
		return 	"Content-Type: " . $this->getContentType() . "; charset=UTF-8\r\n" .
				"Content-Transfer-Encoding: quoted-printable";
	}
	
	
	public function getContent()
	{
		return trim(str_replace("=0A", "\n", str_replace("=0D", "\r", imap_8bit($this->_html)))) /*. "\r\n\r\n"*/;
	}
}



?>