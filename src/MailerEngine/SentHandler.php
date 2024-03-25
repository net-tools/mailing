<?php
/**
 * SentHandler
 *
 * @author Pierre - dev@nettools.ovh
 * @license MIT
 */


// namespace
namespace Nettools\Mailing\MailerEngine;



use \Nettools\Mailing\MailPieces\Headers;





/** 
 * Class to define an event handler for `sent` event (makes it possible to compute quotas if event called at each mail sent event)
 */
abstract class SentHandler 
{
	/** 
	 * Notify about `sent` event
	 *
     * @param string $to Recipient
     * @param string $subject Subject
     * @param Nettools\Mailing\MailPieces\Headers $headers Email headers
	 */
	abstract function notify($to, $subject, Headers $headers);
}




?>