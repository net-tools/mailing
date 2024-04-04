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
use \Nettools\Mailing\MailBuilder\Builder;




/**
 * Class to create an email (content + recipients) with fluent interface
 */
class Compose extends Content {
// [----- PROTECTED -----

	protected $_subject = null;
	protected $_from = null;
	protected $_to = null;
	protected $_cc = null;
	protected $_bcc = null;
	protected $_replyTo = null;
	
// ----- PROTECTED -----]

	
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
	 * Set Reply-To recipient
	 *
	 * @param string $txt Reply-To recipient
	 * @return Compose Return $this for chaining calls
	 */
	function replyTo($txt)
	{
		$this->_replyTo = $txt;
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
	 * Create Nettools\Mailing\MailBuilder\Content object base on mail description through fluent interface
	 *
	 * @return Nettools\Mailing\MailBuilder\Content
	 */
	function create()
	{
		// create mail content
		$m = parent::create();

		
		// set toplevel headers
		if ( $this->_cc )
			$m->headers->set('Cc', $this->_cc);
		if ( $this->_bcc )
			$m->headers->set('Bcc', $this->_bcc);
		if ( $this->_replyTo )
			$m->headers->set('Reply-To', $this->_replyTo);		
		
		
		return $m;
	}
	
	
	
	/**
	 * Send mail to recipients
	 *
	 * @return Sent Returns a `Sent` object to deal with data connection after mail sent ; if no other emails are to be sent, closing connection is preferred
	 * @throws Nettools\Mailing\Exception
	 */
	function send()
	{
		if ( !$this->_from )
			throw new \Nettools\Mailing\Exception('`From` header missing in FluentEngine');
		if ( !$this->_to )
			throw new \Nettools\Mailing\Exception('`To` header missing in FluentEngine');
		if ( !$this->_subject )
			throw new \Nettools\Mailing\Exception('`Subject` header missing in FluentEngine');
		
		
		$this->_engine->getMailer()->sendmail($this->create(), $this->_from, $this->_to, $this->_subject, false);
		return new Sent($this->_engine->getMailer());
	}
}
?>