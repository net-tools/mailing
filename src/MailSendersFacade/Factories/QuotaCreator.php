<?php
/**
 * QuotaCreator
 *
 * @author Pierre - dev@nettools.ovh
 * @license MIT
 */



// namespace
namespace Nettools\Mailing\MailSendersFacade\Factories;



/**
 * Creating a mailsender proxy object with quota handling
 */
class QuotaCreator extends ProxyCreator {

	protected $qi;
	
	
	
	/**
	 * Constructor
	 *
	 * @param \Nettools\Mailing\MailSendersFacade\Quotas\QuotaInterface $qi
	 */
	public function __construct(\Nettools\Mailing\MailSendersFacade\Quotas\QuotaInterface $qi)
	{
		$this->qi = $qi;
	}
	
	
	
	/**
	 * Default builder
	 *
	 * @param string $class Name of MailSender strategy (SMTP, for example)
	 * @param string $name Identifier (usually the $class value with a parameter set name, such as SMTP::aws, to distinguish several strategy with same class but different parameters)
	 * @param object $params Parameters of strategy constructor (usually, login/passwords for smtp) as an object litteral
	 * @return \Nettools\Mailing\MailSendersFacade\Proxies\Quota
	 */
	public function create($class, $name, $params)
	{
		return new \Nettools\Mailing\MailSendersFacade\Proxies\Quota($class, $name, $params, $this->qi);
	}	
}


?>