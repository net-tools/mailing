<?php
/**
 * Facade
 *
 * @author Pierre - dev@nettools.ovh
 * @license MIT
 */



// namespace
namespace Nettools\Mailing\MailSendersFacade;



use \Nettools\Mailing\MailSendersFacade\Lists\Proxies;
use \Nettools\Mailing\MailSendersFacade\Lists\JsonProxies;
use \Nettools\Mailing\MailSendersFacade\Factories\ProxyCreator;




/**
 * Class for email sending strategies facade (deals with back-office, build a list of strategies, get active strategy)
 */
class Facade{

	protected $listStrategy;
	
	
	
	/** 
	 * Constructor
	 *
	 * @param \Nettools\Mailing\MailSendersFacade\Lists\Proxies $list
	 */
	public function __construct(Proxies $list)
	{
		$this->listStrategy = $list;
	}
	 
	
	
	/**
	 * Get a proxy array
	 * 
	 * @return \Nettools\Mailing\MailSendersFacade\Proxies\Proxy[]
	 */	 
	public function getProxies()
	{
 		return $this->listStrategy->getList();
	}
	
	
	
	/**
	 * Get active mailsender strategy proxy
	 *
	 * @return \Nettools\Mailing\MailSendersFacade\Proxies\Proxy
	 */
	public function getActiveProxy()
	{
		return $this->listStrategy->getActive();
	}
	
	
	
	/** 
	 * Get the mailsender concrete object from active mailsender proxy
	 *
	 * @return \Nettools\Mailing\MailSenderIntf
	 */
	public function getActiveMailSender()
	{
		return $this->getActiveProxy()->getMailSender();
	}
	
	
	
	/**
	 * Static method to create a Facade from json data
	 *
	 * @param string[] $list List of mailsenders strategies as a string array ["SMTP:aws", "PHPMail", "SMTP:gmail"]
	 * @param string $json Json-formatted string describing the `$list` items : {"SMTP:aws":{"className":"SMTP","key1":"value1","k2":"value2"}, "PHPMail":{"className":"PHPMail"}}
	 * @param string $active Name of active mailsender strategy (ex. 'SMTP:aws')
	 */
	static function facadeProxiesFromJson(array $list, $json, $active)
	{
		return new Facade(new JsonProxies($list, $json, $active, new ProxyCreator()));
	}
}


?>