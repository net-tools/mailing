<?php
/**
 * TextPlainContent
 *
 * @author Pierre - dev@nettools.ovh
 * @license MIT
 */


// namespace
namespace Nettools\Mailing\MailBuilder;


use \Nettools\Mailing\MailerEngine\Headers;






/**
 * Base class for text/plain
 * 
 * Output will be in utf8 charset, QP encoded
 */
class TextPlainContent extends Content {

// [----- PROTECTED -----

    /** @var string Raw text data */
	protected $_text;

// ----- PROTECTED -----]



// [----- PUBLIC -----

	/** 
     * Constructor
     * 
     * @param string $text Raw text data
     */
	public function __construct($text)
	{
		parent::__construct("text/plain");
		$this->_text = $text;
	}
	
	
	/** 
     * Get text accessor
     *
     * @return string Text data
     */
	public function getText() { return $this->_text; }

    
	/** 
     * Set text accessor
     *
     * @param string $t Text data
     */
    public function setText($t) { $this->_text = $t; }
	
	
	/** 
     * Get headers for this part ; abstract method to implemented in child classes
     *
     * @return string[] Mandatory headers for this part
     */
	public function getHeaders()
	{
		return new Headers(
				[	'Content-Type'				=> $this->getContentType() . "; charset=UTF-8",
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
		return trim(str_replace("=0A", "\n", str_replace("=0D", "\r", imap_8bit($this->_text)))) /*. "\r\n\r\n"*/;
	}
}


?>