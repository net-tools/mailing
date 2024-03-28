<?php
/**
 * Compose
 *
 * @author Pierre - dev@nettools.ovh
 * @license MIT
 */



// namespace
namespace Nettools\Mailing\FluentEngine;


// clauses use
use \Nettools\Mailing\MailerEngine\Headers;
use \Nettools\Mailing\Mailer;




/**
 * Class to create an email with fluent interface
 */
class Compose {
// [----- PROTECTED -----

	protected $_content = null;
	protected $_subject = null;
	protected $_contentType = null;
	protected $_from = null;
	protected $_to = null;
	protected $_cc = null;
	protected $_bcc = null;
	protected $_noAlt = false;
	
	protected $_mailer = null;
	
// ----- PROTECTED -----]


	
	/**
	 * Constructor
	 *
	 * @param \Nettools\Mailing\Mailer $ml
	 */
	function __construct(Mailer $ml)
	{
		$this->_mailer = $ml;
	}
	
	
	
	/**
	 * Create an email with text/plain string provided
	 *
	 * @param string $txt text/plain content ; will be converted to text/html to provide an alternate content
	 * @return Compose Return $this for chaining calls
	 */
	function text($txt)
	{
		$this->_content = $txt;
		$this->_contentType = 'text/plain';
		return $this;
	}

	
	
	/**
	 * Create an email with text/html string provided
	 *
	 * @param string $txt text/html content ; will be converted to text/plain to provide an alternate content
	 * @return Compose Return $this for chaining calls
	 */
	function html($txt)
	{
		$this->_content = $txt;
		$this->_contentType = 'text/html';
		return $this;
	}
	

	
	/**
	 * Set subject
	 *
	 * @param string $txt Subject string ; no encoding needed
	 * @return Compose Return $this for chaining calls
	 */
	function about($txt)
	{
		$this->_subject = $txt;
		return $this;
	}
	

	
	/**
	 * Set `from` origin
	 *
	 * @param string $txt From origin email
	 * @return Compose Return $this for chaining calls
	 */
	function from($txt)
	{
		$this->_from = $txt;
		return $this;
	}
	

	
	/**
	 * Set recipients
	 *
	 * @param string $txt Recipients ; if multiple recipients, use `,`
	 * @return Compose Return $this for chaining calls
	 */
	function to($txt)
	{
		$this->_to = $txt;
		return $this;
	}
	
	
	
	/**
	 * Set CC recipients
	 *
	 * @param string $txt CC recipients ; if multiple recipients, use `,`
	 * @return Compose Return $this for chaining calls
	 */
	function ccTo($txt)
	{
		$this->_cc = $txt;
		return $this;
	}
	
	
	
	/**
	 * Set CC recipients
	 *
	 * @param string $txt CC recipients ; if multiple recipients, use `,`
	 * @return Compose Return $this for chaining calls
	 */
	function bccTo($txt)
	{
		$this->_bcc = $txt;
		return $this;
	}
	
	
	
	/**
	 * Disable auto creation of alternate part (text/html if provided mail content is text/plain, and vice-versa)
	 */
	function noAlternatePart()
	{
		$this->_noAlt = true;
	}
	
	
	
	/**
	 * Attach content already created with fluent interface
	 *
	 * @return Compose Return $this for chaining calls
	 */	
	function attach(Attachment $content)
	{
		
	}
	
	
	
	/**
	 * Embed content already created with fluent interface
	 *
	 * @return Compose Return $this for chaining calls
	 */	
	function embed(Embedding $content)
	{
		
	}
	
}
?>