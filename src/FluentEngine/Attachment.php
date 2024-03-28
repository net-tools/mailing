<?php
/**
 * Attachment
 *
 * @author Pierre - dev@nettools.ovh
 * @license MIT
 */



// namespace
namespace Nettools\Mailing\FluentEngine;





/**
 * Class to create an email attachment with fluent interface
 */
class Attachment extends MixedRelated {
// [----- PROTECTED -----

	protected $_fileName = null;
	
// ----- PROTECTED -----]
	
	
	/**
	 * Set friendly file name
	 * 
	 * @param string $txt
	 * @return Attachment Returns $this for chaining
	 */
	function withFilename($txt)
	{
		$this->_fileName = $txt;
		return $this;
	}
	
}
?>