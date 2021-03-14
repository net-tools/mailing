<?php
/**
 * Virtual_MailSender
 *
 * @author Pierre - dev@nettools.ovh
 * @license MIT
 */



// namespace
namespace Nettools\Mailing\MailSenders;


use \Nettools\Mailing\MailSender;



/** 
 * Strategy to send emails to an array (useful for unit testing)
 */
class Virtual_MailSender extends MailSender
{
	// [----- PROTECTED -----
	
    /** @var string[] Emails sent (as an array of strings) */
	protected $_sent = array();
	
	// ----- PROTECTED -----]
	
	
	// send the email
	function doSend($to, $subject, $mail, $headers)
	{
		$m = $headers;
		$m .= "\r\n";
		$m .= "Delivered-To: $to";
		$m .= "\r\n\r\n";
		$m .= $mail;
			

		$this->_sent[] = $m;
		return FALSE; // ok
	}
	
	
	// destroy instance
	function destruct()
	{
		$this->_sent = array();
	}
	
	
	/**
     * Get array for emails sent
     *
     * @return string[] An array of emails as strings
     */
	function getSent()
	{
		return $this->_sent;
	}
}




?>