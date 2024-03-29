<?php
/**
 * Engine
 *
 * @author Pierre - dev@nettools.ovh
 * @license MIT
 */



// namespace
namespace Nettools\Mailing\FluentEngine;



// clauses use
use \Nettools\Mailing\Mailer;






/**
 * Class holding function to define a fluent interface 
 */
class Engine {
	
	protected $_mailer = null;
	
	

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
	 * Get underlying Mailer object
	 *
	 * @return \Nettools\Mailing\Mailer
	 */
	function getMailer()
	{
		return $this->_mailer;
	}
	
	

	/**
	 * Begin email creation with fluent interface
	 *
	 * @return Compose
	 */
	function compose()
	{
		return new Compose($this);
	}
	
	

	/**
	 * Create content for attachment
	 *
	 * @param string $content Attachment/embedding filepath or content (if $isFile = false)
	 * @param string $ctype Mime type
	 * @return Attachment
	 */
	function attachment($content, $ctype)
	{
		return new Attachment($content, $ctype);
	}
	
	

	/**
	 * Create content for embedding
	 *
	 * @param string $content Attachment/embedding filepath or content (if $isFile = false)
	 * @param string $ctype Mime type
	 * @param string $cid Content-Id
	 * @return Embedding
	 */
	function embedding($content, $ctype, $cid)
	{
		return new Embedding($content, $ctype, $cid);
	}
}
?>