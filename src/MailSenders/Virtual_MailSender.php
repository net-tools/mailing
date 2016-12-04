<?php

// namespace
namespace Nettools\Mailing\MailSenders;


use \Nettools\Mailing\MailSender;



// strategy to send emails to an array (useful for unit testing)
class Virtual_MailSender extends MailSender
{
	// [----- PROTECTED -----
	
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
	
	
	// get array for emails sent
	function getSent()
	{
		return $this->_sent;
	}
}




?>