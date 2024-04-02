<?php
/**
 * Attachment
 *
 * @author Pierre - dev@nettools.ovh
 * @license MIT
 */



// namespace
namespace Nettools\Mailing\FluentEngine;



// clauses use
use \Nettools\Mailing\MailBuilder\Builder;





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
	function withFileName($txt)
	{
		$this->_fileName = $txt;
		return $this;
	}

	
	
	/** 
	 * Create the Nettools\Mailing\MailBuilder\Attachment object
	 *
	 * @return Nettools\Mailing\MailBuilder\Attachment
	 */
	function create()
	{
		if ( $this->_isFile )
			$fname = $this->_fileName ? $this->_fileName : substr(strrchr($this->_content, '/'), 1);
		else
			$fname = $this->_fileName ? $this->_fileName : 'no_name';
		
		return Builder::createAttachment($this->_content, $fname, $this->_ctype, $this->_noCache, $this->_isFile);
	}

}
?>