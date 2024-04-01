<?php
/**
 * Sent
 *
 * @author Pierre - dev@nettools.ovh
 * @license MIT
 */



// namespace
namespace Nettools\Mailing\FluentEngine;


// clauses use
use \Nettools\Mailing\Mailer;




/**
 * Class to deal with email sent
 */
class Sent {
// [----- PROTECTED -----

	protected $_mailer = null;
	
// ----- PROTECTED -----]


	
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
	 * Close connection
	 */
	function done()
	{
		$this->_mailer->destroy();
	}
}
?>