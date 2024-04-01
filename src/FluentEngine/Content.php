<?php
/**
 * Content
 *
 * @author Pierre - dev@nettools.ovh
 * @license MIT
 */



// namespace
namespace Nettools\Mailing\FluentEngine;


// clauses use
use \Nettools\Mailing\MailBuilder\Builder;




/**
 * Class to create an email content with fluent interface
 */
class Content {
// [----- PROTECTED -----

	protected $_content = null;
	protected $_contentType = null;
	protected $_noAlt = false;
	protected $_template = Builder::DEFAULT_TEMPLATE;
    protected $_attachments = [];
    protected $_embeddings = [];
	
	protected $_engine = null;
	
// ----- PROTECTED -----]


	
	/**
	 * Constructor
	 *
	 * @param Engine $engine
	 * @param string[] $params Associative array of parameters to set in constructor ; equivalent of calling corresponding fluent functions
	 */
	function __construct(Engine $engine, array $params = [])
	{
		$this->_engine = $engine;
		
		
		// maybe we want to set already some parameters, calling fluent method from here
		foreach ( $params as $k => $v )
			if ( method_exists($this, $k) )
				call_user_func([$this, $k], $v);
	}
	
	

	/**
	 * Conditionnal statement
	 *
	 * @param bool $cond Bool value to test ; if `$cond` = True, the action callback is called, otherwise it's ignored
	 * @param function $callback Function called as callback if `$cond` equals True, with `$this` as parameter so that calls can be chained
	 * @return Content Return $this for chaining calls
	 */
	function when($cond, $callback)
	{
		if ( $cond )
			// call user function
			call_user_func($callback, $this, $this->_engine);
		
		return $this;
	}
	
	
	
	/**
	 * Create an email with text/plain string provided
	 *
	 * @param string $txt text/plain content ; will be converted to text/html to provide an alternate content
	 * @return Content Return $this for chaining calls
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
	 * @return Content Return $this for chaining calls
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
	 * @return Content Return $this for chaining calls
	 */
	function withTemplate($template)
	{
		$this->_template = $template;
		return $this;
	}
	

		
	/**
	 * Disable auto creation of alternate part (text/html if provided mail content is text/plain, and vice-versa)
	 *
	 * @return Content Return $this for chaining calls
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
	 * @return Content Return $this for chaining calls
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
	 * @return Content Return $this for chaining calls
	 */	
	function attachSome(array $content)
	{
		$this->_attachments = array_merge($this->_attachments, $content);
		return $this;
	}
	
	
	
	/**
	 * Embed content already created with fluent interface
	 *
	 * @param Embedding $content Object of class Embedding already created with Engine::embedding method
     * @return Content Return $this for chaining calls
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
	 * @return Content Return $this for chaining calls
	 */	
	function embedSome(array $content)
	{
		$this->_embeddings = array_merge($this->_embeddings, $content);
		return $this;
	}
	
	
	
	/**
	 * Get underlyine engine object
	 *
	 * @return Engine
	 */
	function getEngine()
	{
		return $this->_engine;
	}
	
	
	
	/**
	 * Update content string before creating Nettools\Mailing\MailBuilder\Content object
	 *
	 * @return string
	 */
	function updateContentString()
	{
		return $this->_content;
	}
	
	
	
	/**
	 * Create Nettools\Mailing\MailBuilder\Content object base on mail description through fluent interface
	 *
	 * @return Nettools\Mailing\MailBuilder\Content
	 */
	function create()
	{
		$text = $this->updateContentString();
		
		
		// if alternative part is allowed
		if ( !$this->_noAlt )
			// prepare text parts
			if ( $this->_contentType == 'text/plain' )
				$m = Builder::addTextHtmlFromText($text, $this->_template);
			else
				$m = Builder::addTextHtmlFromHtml($text, $this->_template);
		
		
		// if no alternative part
		else
			if ( $this->_contentType == 'text/plain' )
				$m = Builder::createText($text);
			else
				$m = Builder::createHtml($text);
		
		
		// if embeddings
		if ( count($this->_embeddings) )
			// insert embeddings in email object
			$m = Builder::addEmbeddingObjects($m, array_map(function($e){ return $e->create(); }, $this->_embeddings));
		
		
		// if attachments
		if ( count($this->_attachments) )
			// insert attachments in email object
			$m = Builder::addAttachmentObjects($m, array_map(function($e){ return $e->create(); }, $this->_attachments));
		
		
		return $m;
	}
	
}
?>