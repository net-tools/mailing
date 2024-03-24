<?php
/**
 * PHPMail
 *
 * @author Pierre - dev@nettools.ovh
 * @license MIT
 */



// namespace
namespace Nettools\Mailing\MailSenders;


use \Nettools\Mailing\MailPieces\Headers;






/** 
 * Strategy to send emails with PHP built-in mail function
 */
class PHPMail extends MailSender
{
	/**
     * Add the To and Subject headers to the headers string
     * 
     * For PHPMail strategy, we do not have to set To and Subject headers, as this is php Mail() function that sets them internally
	 *
     * @param string $to Recipient
     * @param string $subject Subject
     * @param string $mail String containing the email data
     * @param \Nettools\Mailing\MailPieces\Headers $headers Email headers
     */
	function handleHeaders_ToSubject($to, $subject, $mail, Headers $headers)
	{
	}
	
	

	/**
     * Handle Bcc 
     *
     * For PHPMail strategy, we don't have to do anything, as PHP Mail() function processes Bcc headers 
     *
     * @param string $to Recipient
     * @param string $subject Subject
     * @param string $mail String containing the email data
     * @param \Nettools\Mailing\MailPieces\Headers $headers Email headers
     */
	function handleBcc($to, $subject, $mail, Headers $headers)
	{
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