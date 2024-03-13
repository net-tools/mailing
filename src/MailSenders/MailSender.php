<?php
/**
 * MailSender
 *
 * @author Pierre - dev@nettools.ovh
 * @license MIT
 */



// namespace
namespace Nettools\Mailing\MailSenders;


use \Nettools\Mailing\Mailer;






/**
 * Base class for an email sending strategy (PHP Mail function, SMTP, etc.)
 */
abstract class MailSender {

	// [----- PROTECTED -----
	
    /** @var string[] Array of strategy parameters */
	protected $params = NULL;
	
	/** @var \Nettools\Mailing\Mailer\SentHandlers\Handler $sentEvent Event handler for `sent` notification */
	protected $sentEvent = NULL;
	
	// ----- PROTECTED -----]
	
	
	
	/** 
     * Constructor
     * 
     * @param string[]|NULL $params Array of parameters for the sending strategy 
     */
	function __construct($params = NULL)
	{
		$this->params = is_null($params)?array():$params;
	}
	
	
	
	/**
	 * Set event handler
	 *
	 * @param \Nettools\Mailing\Mailer\SentHandlers\Handler $sentEvent Event handler for `sent` notification
	 */
	function setSentEventHandler(?SentHandlers\Handler $sentEvent)
	{
		$this->sentEvent = $sentEvent;
	}
	

	
	/**
	 * Get event handler
	 *
	 * @return \Nettools\Mailing\Mailer\SentHandlers\Handler Event handler for `sent` notification
	 */
	function getSentEventHandler()
	{
		return $this->sentEvent;
	}
	

	
	/**
     * Send the email (to be implemented in child classes)
     *
     * @param string $to Recipient
     * @param string $subject Subject ; must be encoded if necessary
     * @param string $mail String containing the email data
     * @param string $headers Email headers
	 * @throws \Nettools\Mailing\Exception
     */
	abstract function doSend($to, $subject, $mail, $headers);
	

	
	/**
     * Handle Bcc 
     *
     * With a BCC header, we must do specific things. SMTP does not handle Bcc. When having a Bcc header, we must send 
	 * a 'normal' email to this Bcc recipient (and removing Bcc header, which the recipient must not see). If multiple
	 * recipients, we must send as many emails. When using Php Mail function, it processes Bcc headers that way.
     *
     * @param string $to Recipient
     * @param string $subject Subject ; must be encoded if necessary
     * @param string $mail String containing the email data
     * @param string $headers Email headers
	 * @throws \Nettools\Mailing\Exception
	 */
	function handleBcc($to, $subject, $mail, &$headers)
	{
		if ( $bcc = Mailer::getHeader($headers, 'Bcc') )
		{
			// remove Bcc and To header (previously set)
			$headers = Mailer::removeHeader($headers, 'Bcc');
			//$htmp = Mailer::removeHeader($headers, 'To');
			
			
			// for all Bcc recipients, send them a 'normal' email with their email in a To header
			$bcc_to = explode(',', $bcc);
			foreach ( $bcc_to as $bcc )
			{
				// add To header with bcc recipient
				//$h = Mailer::addHeader($htmp, "To: " . trim($bcc));
			
				// envoyer avec BCC comme destinataire ; headers est privé de son champ BCC
				$this->doSend(trim($bcc), $subject, $mail, $headers);
			}
			
			
			// revert original To header
			//$headers = Mailer::addHeader($htmp, "To: " . trim($to));
		}
	}


	
	/**
     * Prepare for sending the email (we handle here the Bcc case)
     *
     * @param string $to Recipient
     * @param string $subject Subject ; must be encoded if necessary
     * @param string $mail String containing the email data
     * @param string $headers Email headers
	 * @throws \Nettools\Mailing\Exception
     */
	function handleSend($to, $subject, $mail, $headers)
	{
		// handle Bcc ; headers array may be modified after the call (Bcc line removed)
		$this->handleBcc($to, $subject, $mail, $headers);
		
		// send the email
		$this->doSend($to, $subject, $mail, $headers);
	}
	
	
	
	/**
     * `Sent` event notify
     *
     * @param string $to Recipient
     * @param string $subject Subject ; must be encoded if necessary
     * @param string $headers Email headers
     */
	function handleSentEvent($to, $subject, $headers)
	{
		if ( $this->sentEvent )
			$this->sentEvent->notify($to, $subject, $headers);
	}
	
	
	
	/**
     * Add the To and Subject headers to the headers string
     * 
     * @param string $to Recipient
     * @param string $subject Subject ; must be encoded if necessary
     * @param string $mail String containing the email data
     * @param string $headers Email headers
     */
	function handleHeaders_ToSubject($to, $subject, $mail, &$headers)
	{
		$headers = Mailer::addHeader($headers, "To: $to");
		$headers = Mailer::addHeader($headers, "Subject: $subject");
	}
	
	
	
	/**
     * Handle priority ; we always set high priorty at the moment
     * 
     * @param string $to Recipient
     * @param string $subject Subject ; must be encoded if necessary
     * @param string $mail String containing the email data
     * @param string $headers Email headers
     */
	function handleHeaders_Priority($to, $subject, $mail, &$headers)
	{
		//$headers = Mailer::addHeader($headers, "X-Priority: 1");
//		$headers = Mailer::addHeader($headers, "X-MSMail-Priority: High"); //nécessite X-MimeOLE qui indique que le message a été rédigé avec outlook
		//$headers = Mailer::addHeader($headers, "Importance: High");
	}
	
	
	
	/**
     * Handle headers modifications (to/subject/priority)
     * 
     * @param string $to Recipient
     * @param string $subject Subject ; must be encoded if necessary
     * @param string $mail String containing the email data
     * @param string $headers Email headers
     */
	function handleHeaders($to, $subject, $mail, &$headers)
	{
		$this->handleHeaders_ToSubject($to, $subject, $mail, $headers);
		$this->handleHeaders_Priority($to, $subject, $mail, $headers);
	}
	
	
	
	/**
     * Send the email
     *
     * @param string $to Recipient
     * @param string $subject Subject ; must be encoded if necessary
     * @param string $mail String containing the email data
     * @param string $headers Email headers
	 * @throws \Nettools\Mailing\Exception
     */
	function send($to, $subject, $mail, $headers)
	{
		// if init OK
		if ( $this->ready() )
		{
			// handle headers processing
			$this->handleHeaders($to, $subject, $mail, $headers);
			
			// send
			$this->handleSend($to, $subject, $mail, $headers);
			
			// event
			$this->handleSentEvent($to, $subject, $headers);
		}
		else
			throw new \Nettools\Mailing\Exception(__CLASS__ . ' not ready for sending email');
	}	


	/**
     * Is the sending strategy ready (all required parameters set) ?
     *
     * @return bool Returns TRUE if strategy if ready
     */
	function ready() { return true; }
	
	
	/**
     * Destruct strategy (do housecleaning stuff such as closing SMTP connections)
     */
	function destruct() {}
}


?>