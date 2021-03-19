<?php
/**
 * Facade
 *
 * @author Pierre - dev@nettools.ovh
 * @license MIT
 */



// namespace
namespace Nettools\Mailing\MailSendersFacade;



use \Nettools\Mailing\MailSendersFacade\Res\ProxyList;
use \Nettools\Mailing\MailSendersFacade\Factories\ProxyCreator;




/**
 * Class for email sending strategies facade (deals with back-office, build a list of strategies, get active strategy)
 */
class Facade{

	protected $listStrategy;
	
	
	
	/** 
	 * Constructor
	 *
	 * @param \Nettools\Mailing\MailSendersFacade\Res\ProxyList $list
	 */
	public function __construct(ProxyList $list)
	{
		$this->listStrategy = $list;
	}
	 
	
	
	/**
	 * Get a proxy list strategy object
	 * 
	 * @return \Nettools\Mailing\MailSendersFacade\Res\ProxyList
	 */	 
	public function getProxyList()
	{
 		return $this->listStrategy;
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