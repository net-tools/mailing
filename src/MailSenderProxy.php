<?php
/**
 * MailSenderProxy
 *
 * @author Pierre - dev@nettools.ovh
 * @license MIT
 */



// namespace
namespace Nettools\Mailing;



/**
 * Class for an email sending strategy proxy (proxies a real strategy during back-office facade stuff such as quotas, create the concrete sending strategy)
 */
class MailSenderProxy{

	public $className;
	public $name;
	public $params;
	
	
	/**
	 * Constructor
	 * 
	 * @param string $class Name of MailSender strategy (SMTP, for example)
	 * @param string $name Identifier (usually the $class value with a parameter set name, such as SMTP::aws, to distinguish several strategy with same class but different parameters)
	 * @param object $params Parameters of strategy constructor (usually, login/passwords for smtp) as an object litteral
	 */	 
	public function __construct($class, $name, $params)
	{
		$this->className = $class;
		$this->name = $name;
		
		if ( is_null($params) )
			$this->params = (object)[];
		else
			if ( !is_object($params) )
				$this->params = (object)$params;
			else
				$this->params = $params;
	}
	
	
	
	/**
	 * Get a MailSender instance from this facade
	 * 
	 * @return \Nettools\Mailing\MailSender
	 */
	public function getMailSender()
	{
		$class = "\\Nettools\\Mailing\\MailSenders\\" . $this->className;
		return new $class((array)($this->params));
	}
	
}


?>