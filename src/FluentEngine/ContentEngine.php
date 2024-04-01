<?php
/**
 * ContentEngine
 *
 * @author Pierre - dev@nettools.ovh
 * @license MIT
 */



// namespace
namespace Nettools\Mailing\FluentEngine;





/**
 * Class holding function to define a fluent interface for creating mail content
 *
 * Other data, such as recipients are handled by ComposeEngine class
 */
class ContentEngine extends Engine {
		
	/**
	 * Begin email content creation with fluent interface
	 *
	 * @param string[] $params Associative array of parameters to set in constructor ; equivalent of calling corresponding fluent functions
	 * @return Content
	 */
	function content(array $params = [])
	{
		return new Content($this, $params);
	}
	
}
?>