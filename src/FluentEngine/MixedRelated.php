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
class MixedRelated {
// [----- PROTECTED -----

	protected $_content = null;
	protected $_ctype = null;
	protected $_isFile = true;
	protected $_ignoreCache = false;
	
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
	 * Disable caching content
	 *
	 * @return MixedRelated Returns $this for chaining calls
	 */
	function disableCache()
	{
		$this->_ignoreCache = true;
		return $this;
	}
		
}
?>