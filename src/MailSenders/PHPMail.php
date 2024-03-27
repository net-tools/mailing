<?php
/**
 * PHPMail
 *
 * @author Pierre - dev@nettools.ovh
 * @license MIT
 */



// namespace
namespace Nettools\Mailing\MailSenders;


use \Nettools\Mailing\MailerEngine\Headers;






/** 
 * Strategy to send emails with PHP built-in mail function
 */
class PHPMail extends MailSender
{	
	/**
	 * Update headers according to strategy requirements (PHPMail removes Subject and To headers)
	 *
     * @param \Nettools\Mailing\MailerEngine\Headers $headers Email headers object
	 */
	function updateHeaders(Headers $headers)
	{
		$headers
			->remove('Subject')
			->remove('To');
	}
	 	

	
	/**
	 * Is the strategy dealing with Cc and Bcc recipients ; this is the case for PHPMail or Gmail.
	 * Other strategies ignore Cc and Bcc headers and emails must be sent for each recipients (To, Bcc, Cc)
	 *
	 * @return bool Returns True if sending strategy handles Cc and Bcc recipients, false otherwise (by default)
	 */
	function isStrategyHandling_CcBcc()
	{
		return true;
	}
	 	

	
	/**
	 * Send the email
	 *
     * @param string $to Recipient ; only address part, no friendly name
     * @param string $subject Subject ; must be encoded if necessary
     * @param string $mail String containing the email data
     * @param string $headers Email headers
	 */
	function doSend($to, $subject, $mail, $headers)
	{
		if ( !mail($to, $subject, $mail, $headers) )
			throw new \Nettools\Mailing\Exception("Unknown error with PHP:mail()");
	}
}
?>