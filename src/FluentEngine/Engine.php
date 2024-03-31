<?php
/**
 * Engine
 *
 * @author Pierre - dev@nettools.ovh
 * @license MIT
 */



// namespace
namespace Nettools\Mailing\FluentEngine;





/**
 * Base class holding function to define a fluent interface for creating mail content
 */
abstract class Engine {
		
	/**
	 * Create content for attachment
	 *
	 * @param string $content Attachment/embedding filepath or content (if $isFile = false)
	 * @param string $ctype Mime type
	 * @return Attachment
	 */
	static function attachment($content, $ctype)
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
	static function embedding($content, $ctype, $cid)
	{
		return new Embedding($content, $ctype, $cid);
	}
}
?>