<?php

// namespace
namespace Nettools\Mailing\MailSenderHelpers;

// clauses use
use \Nettools\Mailing\MailPieces\MailContent;




/**
 * Interface for mailing helper (queue may be used, but this is not mandatory)
 */
interface MailSenderHelperIntf
{
	/** 
	 * Testing that required parameters are set
	 *
	 * @throws \Nettools\Mailing\MailSenderHelpers\Exception
	 */
	public function ready();

	
	
	/**
	 * Compute email 
	 *
	 * @param mixed $data Data that may be required during rendering process
	 * @return \Nettools\Mailing\MailPieces\MailContent
	 * @throws \Nettools\Mailing\MailSenderHelpers\Exception
	 */
	public function render($data);
	
	
	
	/**
	 * Send the email
	 *
	 * @param \Nettools\Mailing\MailPieces\MailContent $mail
	 * @param string $to Email recipient
	 * @param string $subject Specific email subject ; if NULL, the default value passed to the constructor will be used
	 * @throws \Nettools\Mailing\MailSenderHelpers\Exception
	 */
	public function send(MailContent $mail, $to, $subject = NULL);
	
	
	
	/**
	 * Closing queue
	 */
	public function closeQueue();
	
	
	
	/**
	 * Get count of emails in queue
	 *
	 * @return int
	 */
	public function getQueueCount();	

	
	
	/**
	 * Destruct object
	 */
	public function destruct();
	
	
	
	/** 
	 * Getter for ToOverride
	 *
	 * @return NULL|string Returns NULL if no override, a string with email address otherwise
	 */
	public function getToOverride();
	
	
	
	/**
	 * Setter for ToOverride
	 * 
	 * @param string $o Email address to send all emails to (for debugging purpose)
	 * return \Nettools\Mailing\MailSenderHelpers\MailSenderHelperIntf Returns the calling object for chaining
	 */
	public function setToOverride($o);
	
	
	
	/**
	 * Accessor for test mode
	 *
	 * @return bool
	 */
	public function getTestMode();
	
	
	
	/**
	 * Get raw mail as a string before any rendering actions
	 *
	 * @return string
	 */	
	public function getRawMail();
	
	
	
	/**
	 * Update raw mail content
	 * 
	 * @param string $m
	 * return \Nettools\Mailing\MailSenderHelpers\MailSenderHelperIntf Returns the calling object for chaining
	 */
	public function setRawMail($m);
}

?>