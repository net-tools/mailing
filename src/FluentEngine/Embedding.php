<?php
/**
 * Embedding
 *
 * @author Pierre - dev@nettools.ovh
 * @license MIT
 */



// namespace
namespace Nettools\Mailing\FluentEngine;



// clauses use
use \Nettools\Mailing\Mailer;






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
	
	
	
	/** 
	 * Create the Nettools\Mailing\MailParts\Embedding object
	 *
	 * @return Nettools\Mailing\MailParts\Embedding
	 */
	function create()
	{
		return Mailer::createEmbedding($this->_content, $this->_ctype, $this->_cid, $this->_ignoreCache, $this->_isFile);
	}
}
?>