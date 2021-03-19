<?php
/**
 * PdoQuotaInterface
 *
 * @author Pierre - dev@nettools.ovh
 * @license MIT
 */



// namespace
namespace Nettools\Mailing\MailSendersFacade\Quotas;



/**
 * Class to deal with quotas through a pdo log table
 */
class PdoQuotaInterface implements QuotaInterface{

	protected $ackQuery;
	protected $computeQuery;
	protected $cleanQuery;
	
	
	
	/**
	 * Constructor
	 * 
	 * The `$ackQuery` prepared statement must have 2 named parameters, ':name' and ':timestamp'
	 * The `$computeQuery` prepared statement must have 3 named parameters, ':name', ':from' and ':to'
	 * The `$cleanQuery` prepared statement must have 1 named parameter, ':before'
	 *
	 * @param \PDOStatement $ackQuery Prepared PDO statement responsible for adding a quota
	 * @param \PDOStatement $computeQuery Prepared PDO statement responsible for computing a quota over a time interval
	 * @param \PDOStatement $cleanQuery Prepared PDO statement responsible for clean quota log before a given timestamp
	 */
	public function __construct(\PDOStatement $ackQuery, \PDOStatement $computeQuery, \PDOStatement $cleanQuery)
	{
		$this->ackQuery = $ackQuery;	
		$this->computeQuery = $computeQuery;
		$this->cleanQuery = $cleanQuery;
	}
	
	
	
	/**
	 * Acknowledge mail sent in quota
	 *
	 * @param string $name Name of sending strategy
	 * @param int $time Timestamp
	 */
	function add($name, $time)
	{
		$this->ackquery->execute([':name'=>$name, ':timestamp'=>$time]);
	}
	
	
	
	/**
	 * Compute a quota for a mailsender proxy
	 *
	 * @param string $name Name of sending strategy
	 * @param int $from Timestamp of period start to compute quota over
	 * @param int $to Timestamp of period end to compute quota over
	 * @return int Returns a number of mail sent during the period considered for quota computation
	 */
	function compute($name, $from, $to)
	{
		$this->computeQuery->execute([':name'=>$name, ':from'=>$from, ':to'=>$to]);
	}
	
	
	
	/**
	 * Clean quota data before a timestamp
	 *
	 * @param int $before All quota data with timestamp lower than `$before` will be dumped
	 */
	function clean($before)
	{
		$this->cleanQuery->execute([':before'=>$before]);
	}
	
}


?>