<?php
/**
 * ProxyCreator
 *
 * @author Pierre - dev@nettools.ovh
 * @license MIT
 */



// namespace
namespace Nettools\Mailing\MailSendersFacade\Factories;



/**
 * Creating a mailsender proxy object
 */
class ProxyCreator{

	/**
	 * Default builder
	 *
	 * @param string $class Name of MailSender strategy (SMTP, for example)
	 * @param string $name Identifier (usually the $class value with a parameter set name, such as SMTP::aws, to distinguish several strategy with same class but different parameters)
	 * @param object $params Parameters of strategy constructor (usually, login/passwords for smtp) as an object litteral
	 * @return \Nettools\Mailing\MailSendersFacade\Proxies\Proxy
	 */
	public function create($class, $name, $params)
	{
		return new \Nettools\Mailing\MailSendersFacade\Proxies\Proxy($class, $name, $params);
	}	
}


?>