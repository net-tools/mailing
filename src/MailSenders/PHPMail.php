<?php
/**
 * PHPMail
 *
 * @author Pierre - dev@nettools.ovh
 * @license MIT
 */



// namespace
namespace Nettools\Mailing\MailSenders;




/** 
 * Strategy to send emails with PHP built-in mail function
 */
class PHPMail extends MailSender
{
	/**
     * Analyse headers and maybe modify some. 
     * 
     * For PHPMail strategy, we do not have to set To and Subject headers, as this is php Mail() function that sets them internally
     */
	function handleHeaders_ToSubject($to, $subject, $mail, &$headers)
	{
	}
	

	/** 
     * Handle Bcc headers
     *
     * For PHPMail strategy, we don't have to do anything, as PHP Mail() function processes Bcc headers and send bcc emails accordingly
     */
	function handleBcc($to, $subject, $mail, &$headers)
	{
	}
	
	
	// concrete implemntation to send the email
	function doSend($to, $subject, $mail, $headers)
	{
		if ( !mail($to, $subject, $mail, $headers) )
			throw new \Nettools\Mailing\Exception("Unknown error with PHP:mail()");
	}
}
?>