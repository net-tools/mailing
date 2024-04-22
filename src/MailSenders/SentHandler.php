<?php
/**
 * SentHandler
 *
 * @author Pierre - dev@nettools.ovh
 * @license MIT
 */


// namespace
namespace Nettools\Mailing\MailSenders;



use \Nettools\Mailing\MailerEngine\Headers;





/** 
 * Interface to define an event handler for `sent` event (makes it possible to compute quotas if event called at each mail sent event)
 */
interface SentHandler 
{
	/** 
	 * Notify about `sent` event
	 *
     * @param string $to Recipient
     * @param string $subject Subject
     * @param Nettools\Mailing\MailerEngine\Headers $headers Email headers
	 */
	function notify($to, $subject, Headers $headers);
}




?>