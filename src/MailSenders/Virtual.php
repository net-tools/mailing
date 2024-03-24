<?php
/**
 * Virtual
 *
 * @author Pierre - dev@nettools.ovh
 * @license MIT
 */



// namespace
namespace Nettools\Mailing\MailSenders;



/** 
 * Strategy to send emails to an array (useful for unit testing)
 */
class Virtual extends MailSender
{
	// [----- PROTECTED -----
	
    /** @var string[] Emails sent (as an array of strings) */
	protected $_sent = array();
	
	// ----- PROTECTED -----]
	
	
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
		$m = $headers;
		$m .= "\r\n";
		$m .= "Delivered-To: $to";
		$m .= "\r\n\r\n";
		$m .= $mail;
			

		$this->_sent[] = $m;
	}
	
	
	
	/**
	 * Destroy instance
	 */
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