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
}


?>