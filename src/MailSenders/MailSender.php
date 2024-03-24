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
use \Nettools\Mailing\MailPieces\Headers;






/**
 * Base class for an email sending strategy (PHP Mail function, SMTP, etc.)
 */
abstract class MailSender {

	// [----- PROTECTED -----
	
    /** @var string[] Array of strategy parameters */
	protected $params = NULL;
	
	/** @var \Nettools\Mailing\Mailer\SentHandlers\Handler[] $sentEvent Event handler list for `sent` notification */
	protected $sentEvents = array();
	
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
	 * Register an event handler
	 *
	 * @param \Nettools\Mailing\Mailer\SentHandlers\Handler $sentEvent Event handler for `sent` notification
	 */
	function addSentEventHandler(SentHandlers\Handler $sentEvent)
	{
		$this->sentEvents[] = $sentEvent;
	}
	

	
	/**
	 * Unregister an event handler
	 *
	 * @param \Nettools\Mailing\Mailer\SentHandlers\Handler $sentEvent Event handler for `sent` notification
	 */
	function removeSentEventHandler(SentHandlers\Handler $evt)
	{
		$this->sentEvents = array_filter($this->sentEvents, function($h) use ($evt) { return $h != $evt; });
	}
	

	
	/**
	 * Get event handler list
	 *
	 * @return \Nettools\Mailing\Mailer\SentHandlers\Handler[] Event handler list
	 */
	function getSentEventHandlers()
	{
		return $this->sentEvents;
	}
	

	
	/**
     * Send the email (to be implemented in child classes)
     *
     * @param string $to Recipient (no friendly name, only address part)
     * @param string $subject Subject (must be encoded)
     * @param string $mail String containing the email data
     * @param string $headers Email headers
	 * @throws \Nettools\Mailing\Exception
     */
	abstract function doSend($to, $subject, $mail, $headers);
	

	
	/**
     * Send the email to a recipient and notify about sent event
     *
     * @param string $to Recipient
     * @param string $subject Subject
     * @param string $mail String containing the email data
     * @param \Nettools\Mailing\MailPieces\Headers $headers Email headers
     */
	function sendTo($to, $subject, $mail, Headers $headers)
	{
		// send the email
		$this->doSend(Mailer::getAddress($to), mb_encode_mimeheader($subject), $mail, $headers->toString());
		
		// event : 1 mail sent
		$this->handleSentEvent($to, $subject, $headers);
	}
	

	
	/**
     * Handle Bcc 
     *
     * With a BCC header, we must do specific things. SMTP does not handle Bcc. When having a Bcc header, we must send 
	 * a 'normal' email to this Bcc recipient (and removing Bcc header, which the recipient must not see). If multiple
	 * recipients, we must send as many emails. When using Php Mail function, it processes Bcc headers that way.
     *
     * @param string $to Recipient
     * @param string $subject Subject
     * @param string $mail String containing the email data
     * @param \Nettools\Mailing\MailPieces\Headers $headers Email headers
	 * @throws \Nettools\Mailing\Exception
	 */
	function handleBcc($to, $subject, $mail, Headers $headers)
	{
		if ( $bcc = $headers->get('Bcc') )
		{
			// remove Bcc header
			$headers->remove('Bcc');
			
			
			// For all Bcc recipients, send a copy of the email ; 
			//
			// 1. We remind that bcc recipients are regular recipients ; `to` and `bcc` headers are only for information purposes
			// 2. If we deal with SMTP, it's the RCPT-TO smtp command that sets the real recipient. This is the same as the name on the enveloppe (RCPT-TO) and the
			// name on the letter inside the enveloppe (To and Bcc headers, that are not seen by mail carriers)
			// 3. Bcc headers are often removed by MTAs ; some, like Gmail, don't remove them. So we set the Bcc header.
			$bcc_to = explode(',', $bcc);
			foreach ( $bcc_to as $bcc )
			{
				// add bcc recipient one by one, value is encoded
				$h = Headers::fromObject($headers)->setEncodedRecipient('Bcc', trim($bcc));
				$this->sendTo(trim($bcc), $subject, $mail, $h);
			}
		}
	}


	
	/**
     * Prepare for sending the email (we handle here the Bcc case)
     *
     * @param string $to Recipient
     * @param string $subject Subject
     * @param string $mail String containing the email data
     * @param \Nettools\Mailing\MailPieces\Headers $headers Email headers
	 * @throws \Nettools\Mailing\Exception
     */
	function handleSend($to, $subject, $mail, Headers $headers)
	{
		// handle Bcc ; headers array may be modified after the call (Bcc line removed)
		$this->handleBcc($to, $subject, $mail, $headers);
		
		// send the email
		$this->sendTo($to, $subject, $mail, $headers);
	}
	
	
	
	/**
     * `Sent` event notify
     *
     * @param string $to Recipient
     * @param string $subject Subject
     * @param \Nettools\Mailing\MailPieces\Headers $headers Email headers
     */
	function handleSentEvent($to, $subject, Headers $headers)
	{
		$evts = $this->getSentEventHandlers();
		
		foreach ( $evts as $evt )
			$evt->notify($to, $subject, $headers);
	}
	
	
	
	/**
     * Add the To and Subject headers to the headers string
     * 
     * @param string $to Recipient
     * @param string $subject Subject
     * @param string $mail String containing the email data
     * @param \Nettools\Mailing\MailPieces\Headers $headers Email headers
     */
	function handleHeaders_ToSubject($to, $subject, $mail, Headers $headers)
	{
		$headers
			->setEncodedRecipient('To', $to)
			->setEncoded('Subject', $subject);
	}
	
	
	
	/**
     * Handle priority ; we always set high priorty at the moment
     * 
     * @param string $to Recipient
     * @param string $subject Subject
     * @param string $mail String containing the email data
     * @param \Nettools\Mailing\MailPieces\Headers $headers Email headers
     */
	function handleHeaders_Priority($to, $subject, $mail, Headers $headers)
	{
/*		$headers->set('X-Priority', '1')
				->set('X-MSMail-Priority', '1')
				->set('Importance', 'High');*/
	}
	
	
	
	/**
     * Handle headers modifications (from/to/subject/priority)
     * 
     * @param string $to Recipient
     * @param string $subject Subject
     * @param string $mail String containing the email data
     * @param \Nettools\Mailing\MailPieces\Headers $headers Email headers
     */
	function handleHeaders($to, $subject, $mail, Headers $headers)
	{
		// encode From header if required
		$this->handleFromHeaderEncoding($headers);
		
		// add To and Subject headers
		$this->handleHeaders_ToSubject($to, $subject, $mail, $headers);
		
		// handle priority headers
		$this->handleHeaders_Priority($to, $subject, $mail, $headers);
	}
	
	
	
	/**
     * Handle from header encoding
     * 
     * @param \Nettools\Mailing\MailPieces\Headers $headers Email headers
     */
	function handleFromHeaderEncoding(Headers $headers)
	{
		$headers->encodeRecipient('From');
	}
	
	
	
	/**
     * Send the email
     *
     * @param string $to Recipient
     * @param string $subject Subject
     * @param string $mail String containing the email data
     * @param \Nettools\Mailing\MailPieces\Headers $headers Email headers
	 * @throws \Nettools\Mailing\Exception
     */
	function send($to, $subject, $mail, Headers $headers)
	{
		// if init OK
		if ( $this->ready() )
		{
			// handle headers processing
			$this->handleHeaders($to, $subject, $mail, $headers);
			
			// send
			$this->handleSend($to, $subject, $mail, $headers);
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