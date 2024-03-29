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
	protected $_template = Mailer::TEMPLATE;
    protected $_attachments = [];
    protected $_embeddings = [];
	
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
	 * Set email template as Html data
	 *
	 * @param string $template
	 * @return Compose Return $this for chaining calls
	 */
	function withTemplate($template)
	{
		$this->_template = $template;
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
	 *
	 * @return Compose Return $this for chaining calls
	 */
	function noAlternatePart()
	{
		$this->_noAlt = true;
		return $this;
	}
	
	
	
	/**
	 * Attach content already created with fluent interface
     * 
	 * @param Attachment $content Object of class Attachment already created with Engine::attachment method
	 * @return Compose Return $this for chaining calls
	 */	
	function attach(Attachment $content)
	{
		$this->_attachments[] = $content;
		return $this;
	}
	
	
	
	/**
	 * Attach several attachments already created with fluent interface
	 *
     * @param Attachments[] $content Array of Attachments objects
	 * @return Compose Return $this for chaining calls
	 */	
	function setAttachments(array $content)
	{
		$this->_attachments = array_merge($this->_attachments, $content);
		return $this;
	}
	
	
	
	/**
	 * Embed content already created with fluent interface
	 *
	 * @param Embedding $content Object of class Embedding already created with Engine::embedding method
     * @return Compose Return $this for chaining calls
	 */	
	function embed(Embedding $content)
	{
		$this->_embeddings[] = $content;
		return $this;
	}
	
    
    
	/**
	 * Attach several embeddings already created with fluent interface
	 *
     * @param Embeddings[] $content Array of Embeddings objects
	 * @return Compose Return $this for chaining calls
	 */	
	function setEmbeddings(array $content)
	{
		$this->_embeddings = array_merge($this->_embeddings, $content);
		return $this;
	}
	
	
	
	/**
	 * Create Nettools\Mailing\MailParts\Content object base on mail description through fluent interface
	 *
	 * @return Nettools\Mailing\MailParts\Content
	 */
	function create()
	{
		// if alternative part is allowed
		if ( !$this->_noAlt )
			// prepare text parts
			if ( $this->_contentType == 'text/plain' )
				$m = Mailer::addTextHtmlFromText($this->_content, $this->_template);
			else
				$m = Mailer::addTextHtmlFromHtml($this->_content, $this->_template);
		
		
		// if no alternative part
		else
			if ( $this->_contentType == 'text/plain' )
				$m = Mailer::createText($this->_content);
			else
				$m = Mailer::createHtml($this->_content);
		
		
		// set headers
		if ( $this->_cc )
			$m->headers->set('Cc', $this->_cc);
		if ( $this->_bcc )
			$m->headers->set('Bcc', $this->_bcc);
		
		
		
		// if embeddings
		if ( count($this->_embeddings) )
			// insert embeddings in email object
			$m = Mailer::addEmbeddingObjects($m, array_map(function($e){ return $e->create(); }, $this->_embeddings));
		
		
		// if attachments
		if ( count($this->_attachments) )
			// insert attachments in email object
			$m = Mailer::addAttachmentObjects($m, array_map(function($e){ return $e->create(); }, $this->_attachments));
		
		
		return $m;
	}
	
	
	
	/**
	 * Send mail to recipients
	 *
	 * @return Sent Returns a `Sent` object to deal with data connection after mail sent ; if no other emails are to be sent, closing connection is preferred
	 */
	function send()
	{
		$this->_mailer->sendmail($this->create(), $this->_from, $this->_to, $this->_subject, false);
		return new Sent($this->_mailer);
	}
}
?>