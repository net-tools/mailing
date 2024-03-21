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
     * @param string $to Recipient
     * @param string $subject Subject ; must be encoded if necessary
     * @param string $mail String containing the email data
     * @param string $headers Email headers
	 * @throws \Nettools\Mailing\Exception
     */
	abstract function doSend($to, $subject, $mail, $headers);
	

	
	/**
     * Send the email to a recipient and notify about sent event
     *
     * @param string $to Recipient
     * @param string $subject Subject ; must be encoded if necessary
     * @param string $mail String containing the email data
     * @param string[] $headers Email headers
     */
	function sendTo($to, $subject, $mail, array $headers)
	{
		// send the email
		$this->doSend($this->extractRecipient($to), $subject, $mail, Mailer::arrayToHeaders($headers));
		
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
     * @param string $subject Subject ; must be encoded if necessary
     * @param string $mail String containing the email data
     * @param string $headers Email headers
	 * @throws \Nettools\Mailing\Exception
	 */
	function handleBcc($to, $subject, $mail, array &$headers)
	{
		if ( $bcc = Mailer::getHeader($headers, 'Bcc') )
		{
			// remove Bcc header
			$headers = Mailer::removeHeader($headers, 'Bcc');
			
			
			// For all Bcc recipients, send a copy of the email ; 
			//
			// 1. We remind that bcc recipients are regular recipients ; `to` and `bcc` headers are only for information purposes
			// 2. If we deal with SMTP, it's the RCPT-TO smtp command that sets the real recipient. This is the same as the name on the enveloppe (RCPT-TO) and the
			// name on the letter inside the enveloppe (To and Bcc headers, that are not seen by mail carriers)
			// 3. Bcc headers are often removed by MTAs ; some, like Gmail, don't remove them. So we set the Bcc header.
			$bcc_to = explode(',', $bcc);
			foreach ( $bcc_to as $bcc )
			{
				// add bcc recipient one by one
				$h = Mailer::addHeader($headers, 'Bcc', $this->encodeAddress(trim($bcc)));
				$this->sendTo(trim($bcc), $subject, $mail, $h);
			}
		}
	}


	
	/**
     * Prepare for sending the email (we handle here the Bcc case)
     *
     * @param string $to Recipient
     * @param string $subject Subject ; must be encoded if necessary
     * @param string $mail String containing the email data
     * @param string[] $headers Email headers
	 * @throws \Nettools\Mailing\Exception
     */
	function handleSend($to, $subject, $mail, array $headers)
	{
		// handle Bcc ; headers array may be modified after the call (Bcc line removed)
		$this->handleBcc($to, $subject, $mail, $headers);
		
		// send the email
		$this->sendTo($to, $subject, $mail, $headers);
	}
	
	
	
	/**
     * `Sent` event notify
     *
     * @param string $to Recipient ; must be already encoded if required
     * @param string $subject Subject ; must be already encoded if required
     * @param string[] $headers Email headers
     */
	function handleSentEvent($to, $subject, array $headers)
	{
		$evts = $this->getSentEventHandlers();
		
		foreach ( $evts as $evt )
			$evt->notify(mb_decode_mimeheader($to), mb_decode_mimeheader($subject), $headers);
	}
	
	
	
	/**
     * Add the To and Subject headers to the headers string
     * 
     * @param string $to Recipient ; must be already encoded if required
     * @param string $subject Subject ; must be encoded if required
     * @param string $mail String containing the email data
     * @param string[] $headers Email headers
     */
	function handleHeaders_ToSubject($to, $subject, $mail, array &$headers)
	{
		$headers = Mailer::addHeader($headers, 'To', $to);
		$headers = Mailer::addHeader($headers, 'Subject', $subject);
	}
	
	
	
	/**
     * Handle priority ; we always set high priorty at the moment
     * 
     * @param string $to Recipient ; must be already encoded if required
     * @param string $subject Subject ; must be already encoded if required
     * @param string $mail String containing the email data
     * @param string[] $headers Email headers
     */
	function handleHeaders_Priority($to, $subject, $mail, array &$headers)
	{
		//$headers = Mailer::addHeader($headers, "X-Priority: 1");
//		$headers = Mailer::addHeader($headers, "X-MSMail-Priority: High"); //nécessite X-MimeOLE qui indique que le message a été rédigé avec outlook
		//$headers = Mailer::addHeader($headers, "Importance: High");
	}
	
	
	
	/**
     * Handle headers modifications (to/subject/priority)
     * 
     * @param string $to Recipient ; must be already encoded if required
     * @param string $subject Subject ; must be already encoded if required
     * @param string $mail String containing the email data
     * @param string $headers[] Email headers
     */
	function handleHeaders($to, $subject, $mail, array &$headers)
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
     * @param string[] $headers Email headers
     */
	function handleFromHeaderEncoding(array &$headers)
	{
		$h = $this->encodeAddress(Mailer::getHeader($headers, 'From'));
		$headers = Mailer::addHeader($headers, 'From', $h);
	}
	
	
	
	/**
	 * Encode address
	 * 
	 * @param string $to Email address in format `friendly name <recipient@domain.tld>`
	 * @return string Email address string encoded
	 */
	function encodeAddress($to)
	{
		// if email address in format "friendlyname <address>"
		if ( preg_match("/(.*)<(.*)>/", $to, $regs) )
		{
			$friendly = trim($regs[1]);
			$addr = trim($regs[2]);
			
			return mb_encode_mimeheader($friendly) . " <$addr>";
		}
		else
			return $to;
	}
	
	
	
	/**
     * Handle headers encoding (to/subject) and update arguments
     * 
     * @param string $to Recipient
     * @param string $subject Subject ; must be encoded if necessary
     */
	function handleToSubjectEncoding(&$to, &$subject)
	{
		$to = $this->encodeAddress($to);
		$subject = mb_encode_mimeheader($subject);
	}
	
	
	
	/**
	 * From a mail recipient that may be formatted as `"friendly name" <recipient@domain.tld>`, extract email part
	 *
	 * @param string $to Full email recipient, including friendy name
	 * @return string Returns email recipient
	 */
	function extractRecipient($to)
	{
		if ( preg_match("/<(.*)>/", $to, $regs) )
			return $regs[1];
		else
			return $to;
	}
	
	
	
	/**
     * Send the email
     *
     * @param string $to Recipient
     * @param string $subject Subject ; must be encoded if necessary
     * @param string $mail String containing the email data
     * @param string[] $headers Email headers
	 * @throws \Nettools\Mailing\Exception
     */
	function send($to, $subject, $mail, array $headers)
	{
		// if init OK
		if ( $this->ready() )
		{
			// backup $torecipient before it's encoded
			$torecipient = $to;
			
			// handle encoding for `to` and `subject` headers and update $to and $subject
			$this->handleToSubjectEncoding($to, $subject);
			
			// handle headers processing
			$this->handleHeaders($to, $subject, $mail, $headers);
			
			// send
			$this->handleSend($torecipient, $subject, $mail, $headers);
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