<?php
/**
 * TextHtmlContent
 *
 * @author Pierre - dev@nettools.ovh
 * @license MIT
 */



// namespace
namespace Nettools\Mailing\MailBuilder;



use \Nettools\Mailing\MailerEngine\Headers;





/** 
 * Base class for text/html part.
 *
 * The text output will be in utf-8 charset and QP encoded
 */
class TextHtmlContent extends Content {

// [----- PROTECTED -----

    /** @var string HTML raw data */
	protected $_html;
    
    /** @var string Cached HTML data */
    protected $_cachedHtml = NULL;
    

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
    public function setHtml($html) { $this->_html = $html; $this->_cachedHtml = NULL; }
	
	
	/** 
     * Get headers for this part ; abstract method to implemented in child classes
     *
     * @return Headers Mandatory headers for this part
     */
	public function getHeaders()
	{
		return new Headers ([
					'Content-Type'				=> $this->getContentType() . "; charset=UTF-8",
					'Content-Transfer-Encoding'	=> 'quoted-printable'
				 ]);
	}
	
	
	/**
     * Get the text content of this part (to implement in child classes)
     *
     * @return string Returns a string representing the body of this part (headers excluded) 
     */
	public function getContent()
	{
        if ( !is_null($this->_cachedHtml) )
            return $this->_cachedHtml;
        
		$this->_cachedHtml = trim(quoted_printable_encode($this->_html));
        return $this->_cachedHtml;
		//return trim(str_replace("=0A", "\n", str_replace("=0D", "\r", imap_8bit($this->_html)))) /*. "\r\n\r\n"*/;
	}
}



?>