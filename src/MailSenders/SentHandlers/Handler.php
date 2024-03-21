<?php
/**
 * Handler
 *
 * @author Pierre - dev@nettools.ovh
 * @license MIT
 */




// namespace
namespace Nettools\Mailing\MailSenders\SentHandlers;





/** 
 * Class to define an event handler for `sent` event (makes it possible to compute quotas if event called at each mail sent event)
 */
abstract class Handler 
{
	/** 
	 * Notify about `sent` event
	 *
     * @param string $to Recipient
     * @param string $subject Subject ; must be encoded if necessary
     * @param string[] $headers Email headers
	 */
	abstract function notify($to, $subject, array $headers);
}




?>