<?php
/**
 * ContentEngine
 *
 * @author Pierre - dev@nettools.ovh
 * @license MIT
 */



// namespace
namespace Nettools\Mailing\FluentEngine;



// clauses use
use \Nettools\Mailing\Mailer;






/**
 * Class holding function to define a fluent interface for creating mail content
 *
 * Other data, such as recipients are handled by ComposeEngine class
 */
class ContentEngine extends Engine {
		
	/**
	 * Begin email content creation with fluent interface
	 *
	 * @return Content
	 */
	function content()
	{
		return new Content($this);
	}
	
}
?>