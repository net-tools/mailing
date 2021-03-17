<?php
/**
 * MailSendersFacade
 *
 * @author Pierre - dev@nettools.ovh
 * @license MIT
 */



// namespace
namespace Nettools\Mailing;



use \Nettools\Mailing\MailSendersFacade\Strategies\ProxyList;


/**
 * Class for email sending strategies facade (deals with back-office, build a list of strategies, get active strategy)
 */
class MailSendersFacade{

	protected $mslist;
	protected $listStrategy;
	
	
	
	/** 
	 * Constructor
	 *
	 * @param \Nettools\Mailing\MailSendersFacade\Strategies\ProxyList $listStrategy
	 */
	public function __construct(ProxyList $listStrategy)
	{
		$this->listStrategy = $listStrategy;
	}
	 
	
	
	/**
	 * Get a list of strategies proxy
	 * 
	 * @return \Nettools\Mailing\MailSenderProxy[]
	 */	 
	public function getProxyList()
	{
		if ( is_null($this->mslist) )
			$this->mslist = $this->listStrategy->getProxyList();
			
		return $this->mslist;
	}
	
	
	
	/**
	 * Get active sending strategy proxy
	 * 
	 * @return \Nettools\Mailing\MailSenderProxy
	 */	 
	public function getActiveProxy()
	{
		return $this->listStrategy->getActiveProxy();
	}
}


?>