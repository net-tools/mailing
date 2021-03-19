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
use \Nettools\Mailing\MailSendersFacade\Factories\QuotaCreator;




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
	
	

	/**
	 * Static method to create a QuotaFacade object from json data
	 *
	 * @param string[] $list List of mailsenders strategies as a string array ["SMTP:aws", "PHPMail", "SMTP:gmail"]
	 * @param string $json Json-formatted string describing the `$list` items : {"SMTP:aws":{"className":"SMTP","key1":"value1","k2":"value2"}, "PHPMail":{"className":"PHPMail"}}
	 * @param string $active Name of active mailsender strategy (ex. 'SMTP:aws')
	 * @param \Nettools\Mailing\MailSendersFacade\Quotas\QuotaInterface $qi Quota interface to deal with quota computations
	 */
	static function facadeQuotaProxiesFromJson(array $list, $json, $active, QuotaInterface $qi)
	{
		return new QuotaFacade(new JsonProxies($list, $json, $active, new QuotaCreator($qi)));
	}
}


?>