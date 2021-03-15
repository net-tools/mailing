<?php
/**
 * Checker
 *
 * @author Pierre - dev@nettools.ovh
 * @license MIT
 */


// namespace
namespace Nettools\Mailing\MailCheckers;



/** 
 * Class to handle mail existence check
 */
abstract class Checker
{
	protected $http;
	
	

	/**
	 * Constructor
	 *
	 * @param \GuzzleHttp\Client $http GuzzleHttp interface to send request through
	 */
	public function __construct(\GuzzleHttp\Client $http)
	{
		$this->http = $http;
	}
	
	
	
	/**
	 * Check that a given email exists
	 * 
	 * @param string $email
	 * @return bool Returns true if the email can be delivered, false otherwise
	 */
	abstract function check ($email);
}
?>