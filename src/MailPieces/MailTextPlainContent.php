<?php
/**
 * MailTextPlainContent
 *
 * @author Pierre - dev@nettools.ovh
 * @license MIT
 */



// namespace
namespace Nettools\Mailing\MailPieces;



/**
 * Base class for text/plain
 * 
 * Output will be in utf8 charset, QP encoded
 */
class MailTextPlainContent extends MailContent {

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