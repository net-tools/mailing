<?php
/**
 * ComposeEngine
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
class ComposeEngine extends Engine {
	
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
}
?>