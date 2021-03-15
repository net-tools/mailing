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
	 * Create a checker instance with default GuzzleHttp interface
	 *
	 * Late static binding is use to know which is the real calling class	 
	 *
	 * @return Nettools\Mailing\MailCheckers\Checker
	 */
	static function create()
	{
		$class = get_called_class();
		return new $class(new \GuzzleHttp\Client());
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