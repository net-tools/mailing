<?php
/**
 * QuotaInterface
 *
 * @author Pierre - dev@nettools.ovh
 * @license MIT
 */



// namespace
namespace Nettools\Mailing\MailSendersFacade\Quotas;


/**
 * Interface to deal with quotas
 */
interface QuotaInterface{

	/**
	 * Acknowledge mail sent in quota
	 *
	 * @param string $name Name of sending strategy
	 * @param int $time Timestamp
	 */
	function add($name, $time);
	
	
	
	/**
	 * Compute a quota for a mailsender proxy
	 *
	 * @param string $name Name of sending strategy
	 * @param int $from Timestamp of period start to compute quota over
	 * @param int $to Timestamp of period end to compute quota over
	 * @return int Returns a number of mail sent during the period considered for quota computation
	 */
	function compute($name, $from, $to);
	
	
	
	/**
	 * Clean quota data before a timestamp
	 *
	 * @param int $before All quota data with timestamp lower than `$before` will be dumped
	 */
	function clean($before);
}


?>