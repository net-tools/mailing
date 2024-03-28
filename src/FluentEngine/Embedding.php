<?php
/**
 * Embedding
 *
 * @author Pierre - dev@nettools.ovh
 * @license MIT
 */



// namespace
namespace Nettools\Mailing\FluentEngine;





/**
 * Class to create an email embedding with fluent interface
 */
class Embedding extends MixedRelated {
// [----- PROTECTED -----

	protected $_cid = null;
	
// ----- PROTECTED -----]
	
	
	/**
	 * Create related content
	 * 
	 * @param string $content Attachment/embedding filepath or content (if $isFile = false)
	 * @param string $ctype Mime type
	 * @param string $cid Content-Id for embedding
	 */
	function __construct($content, $ctype, $cid)
	{
		parent::__construct($content, $ctype);
		$this->_cid = $cid;
	}
	
}
?>