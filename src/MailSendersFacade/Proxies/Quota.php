<?php
/**
 * Quota
 *
 * @author Pierre - dev@nettools.ovh
 * @license MIT
 */



// namespace
namespace Nettools\Mailing\MailSendersFacade\Proxies;



use \Nettools\Mailing\MailSendersFacade\Quotas\SentHandler;
use \Nettools\Mailing\MailSendersFacade\Quotas\QuotaInterface;




/**
 * Class for an email sending strategy proxy with quota computation
 */
class Quota extends Proxy{
	
	protected $qi;
	const QUOTA = 'quota';
	
	
	
	/**
	 * Constructor
	 * 
	 * @param string $class Name of MailSender strategy (SMTP, for example)
	 * @param string $name Identifier (usually the $class value with a parameter set name, such as SMTP::aws, to distinguish several strategy with same class but different parameters)
	 * @param object $params Parameters of strategy constructor (usually, login/passwords for smtp) as an object litteral
	 * @param \Nettools\Mailing\MailSendersFacade\Quotas\QuotaInterface $qi Object to send email acknowledgements to
	 */	 
	public function __construct($class, $name, $params, QuotaInterface $qi)
	{
		parent::__construct($class, $name, $params);
		
		$this->qi = $qi;
	}
	
	
	
	/**
	 * Get a concrete MailSender instance from this mail sender proxy
	 * 
	 * @return \Nettools\Mailing\MailSenders\MailSender
	 */
	public function getMailSender()
	{
		// get concrete mailsender through parent call
		$subc = parent::getMailSender();		
		
		// set event handler
		$subc->addSentEventHandler(new SentHandler($this->name, $this->qi));
		
		return $subc;
	}
	
	
	
	/** 
	 * Compute quotas
	 *
	 * @return object Returns an object litteral with `pct` (percentage of quota usage), `value` (number of items), `quota` (max number of items allow for quota) and `period` (quota per day[d] or hour[h]) properties 
	 */
	public function computeQuota()
	{
		// if quota is defined in parameters
		if ( property_exists($this->params, self::QUOTA) )
		{
			// fetching quota definition (ex: 1000:j)
			$quotadef = $this->params->{self::QUOTA};
			list($quota, $period) = explode(':', $quotadef);
			
			// computing timestamp interval for quota calculation (entire day or hour)
			switch ( $period )
			{
				// quota per day
				case 'd':
					$dt1 = strtotime('today'); // today = midnight today
					$dt2 = strtotime('midnight +1 day');
					break;
					
				// quota per hour
				case 'h':
					$dt1 = time() - date('i') * 60 - date('s');
					$dt2 = $dt1 + 60*60;
					break;
					
				default:
					// quota period is not defined
					return (object)[ 'pct' => 0, 'value' => 0, 'quota' => 0, 'period' => ''];
			}
			

			// computing quota through QuotaInterface
			$q = $this->qi->compute($this->name, $dt1, $dt2);
			return (object)[ 'pct' => floor(100*$q/$quota), 'value' => $q, 'quota' => $quota, 'period' => $period];
		}
		
		
		return (object)[ 'pct' => 0, 'value' => 0, 'quota' => 0, 'period' => ''];
	}
}


?>