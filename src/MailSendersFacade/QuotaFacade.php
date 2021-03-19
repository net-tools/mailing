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
use \Nettools\Mailing\MailSendersFacade\Quotas\QuotaInterface;




/**
 * Class for email sending strategies facade with quota handling
 */
class QuotaFacade extends Facade{

	protected $qi;
	
	

	/** 
	 * Constructor
	 *
	 * @param \Nettools\Mailing\MailSendersFacade\Lists\Proxies $list
	 * @param \Nettools\Mailing\MailSendersFacade\Quotas\QuotaInterface $qi
	 */
	public function __construct(Proxies $list, QuotaInterface $qi)
	{
		parent::__construct($list);
		
		$this->qi = $qi;
	}
	 
	
	
	/** 
	 * Compute quotas for all mailsenders proxies
	 * 
	 * @return object Returns an object litteral whose keys are mailsender proxy names and values the quota (%) for each proxy
	 */
	public function compute()
	{
		$quotas = [];
		$lst = $this->getProxies();
		

		// create an associative array (name => quota%)
		foreach ( $lst as $l )
			$quotas[$l->name] = $l->computeQuota();
		
		
		// cleaning stuff (removing data before midnight today)
		$this->qi->clean(strtotime('today'));
		
		return (object)$quotas;
	}
	
}


?>