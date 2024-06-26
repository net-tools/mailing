<?php
/**
 * MixedRelated
 *
 * @author Pierre - dev@nettools.ovh
 * @license MIT
 */



// namespace
namespace Nettools\Mailing\FluentEngine;





/**
 * Class to create an email attachment or embedding with fluent interface
 */
abstract class MixedRelated {
// [----- PROTECTED -----

	protected $_content = null;
	protected $_ctype = null;
	protected $_isFile = true;
	protected $_noCache = true;
	protected $_cacheId = NULL;

	
	
	/** 
	 * Abstract function to define to create the Nettools\Mailing\MailBuilder\Attachment or Nettools\Mailing\MailBuilder\Embedding object
	 *
	 * @return Nettools\Mailing\MailBuilder\MixedRelated
	 */
	abstract protected function doCreate();	
	
	
// ----- PROTECTED -----]
	
	/**
	 * Create mixed or related content
	 * 
	 * @param string $content Attachment/embedding filepath or content (if $isFile = false)
	 * @param string $ctype Mime type
	 */
	function __construct($content, $ctype)
	{
		$this->_content = $content;
		$this->_ctype = $ctype;
	}
	
	
	
	
	/** 
	 * Set content string to be raw content data instead of file path to attach/embed
	 *
	 * @return MixedRelated Returns $this for chaining calls
	 */
	function asRawContent()
	{
		$this->_isFile = false;
		return $this;
	}
	
	
	
	/** 
	 * Enable caching content
	 *
	 * @return MixedRelated Returns $this for chaining calls
	 */
	function enableCache()
	{
		$this->_noCache = false;
		return $this;
	}
	
	
	
	/**
	 * Set cacheId
	 *
	 * @param string $id
	 * @return MixedRelated Returns $this for chaining calls
	 */
	function withCacheId($id)
	{
		$this->_cacheId = $id;
		return $this;
	}
	
	
	
	/** 
	 * Create the Nettools\Mailing\MailBuilder\Attachment or Nettools\Mailing\MailBuilder\Embedding object
	 *
	 * @return Nettools\Mailing\MailBuilder\MixedRelated
	 */
	function create()
	{
		// creating the object
		$o = $this->doCreate();
		
		// setting properties
		if ( $this->_cacheId )
			$o->setCacheId($this->_cacheId);
		
		return $o;
	}
}
?>