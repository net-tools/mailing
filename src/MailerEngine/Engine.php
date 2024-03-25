<?php
/**
 * Engine
 *
 * @author Pierre - dev@nettools.ovh
 * @license MIT
 */



// namespace
namespace Nettools\Mailing\MailerEngine;


use \Nettools\Mailing\Mailer;
use \Nettools\Mailing\MailPieces\Headers;
use \Nettools\Mailing\MailSenders\MailSender;






/**
 * Sending mail helper, depending on email sending strategy (PHPMail, SMTP, etc.) available on MailSenders root subdirectory
 */
class Engine {

	// [----- PROTECTED -----
	
	/** @var \Nettools\Mailing\MailerEngine\SentHandler[] $sentEvent Event handler list for `sent` notification */
	protected $sentEvents = array();
	
	
	/** @var \Nettools\Mailing\MailSenders\MailSender $ms Mail sending strategy to send email through */
	protected $mailSender = null;
	
	// ----- PROTECTED -----]
	
	
	
	/** 
     * Constructor
     * 
     * @param \Nettools\Mailing\MailSenders\MailSender $ms Mail sending strategy
     */
	function __construct(MailSender $ms)
	{
		$this->mailSender = $ms;
	}
	
	
	
	/** 
	 * Get mailing strategy
	 *
	 * @return \Nettools\Mailing\MailSenders\MailSender
	 */
	function getMailSender()
	{
		return $this->mailSender;
	}
	
	
	
	/**
	 * Register an event handler
	 *
	 * @param SentHandler $sentEvent Event handler for `sent` notification
	 */
	function addSentEventHandler(SentHandler $sentEvent)
	{
		$this->sentEvents[] = $sentEvent;
	}
	

	
	/**
	 * Unregister an event handler
	 *
	 * @param SentHandler $sentEvent Event handler for `sent` notification
	 */
	function removeSentEventHandler(SentHandler $evt)
	{
		$this->sentEvents = array_filter($this->sentEvents, function($h) use ($evt) { return $h != $evt; });
	}
	

	
	/**
	 * Get event handler list
	 *
	 * @return SentHandler[] Event handler list
	 */
	function getSentEventHandlers()
	{
		return $this->sentEvents;
	}
	

	
	/**
     * Send the email through mail sending strategy
     *
     * @param string $to Recipient (no friendly name, only address part)
     * @param string $subject Subject (must be encoded)
     * @param string $mail String containing the email data
     * @param string $headers Email headers
	 * @throws \Nettools\Mailing\Exception
     */
	function doSend($to, $subject, $mail, $headers)
	{
		return $this->mailSender->doSend($to, $subject, $mail, $headers);
	}
	

	
	/**
	 * Get the email address from a recipient string that may have a friendly name part `recipient <me@at.domain>`
	 *
	 * @param string $addr The email address in the format `me@at.domain` or `recipient <me@at.domain>`
	 * @return string Returns only the email address part `me@at.domain`
	 */
	static function getAddressPart($addr)
	{
		if ( preg_match("/<(.*)>/", $addr, $regs) )
			return $regs[1];
		else
			return $addr;
	}
	


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
		// create message-id header
		$h2 = Headers::fromObject($headers);
		$this->handleHeaders_MessageId($h2);		
		
		// send the email
		$this->doSend(self::getAddressPart($to), mb_encode_mimeheader($subject), $mail, $h2->toString());
		
		// event : 1 mail sent
		$this->handleSentEvent($to, $subject, $h2);
	}
	

	
	/**
     * Handle Bcc 
     *
     * With a BCC header, we must do specific things. SMTP does not handle Bcc. When having a Bcc header, we must send 
	 * a 'normal' email to this Bcc recipient (and removing Bcc header, which the recipient must not see). If multiple
	 * recipients, we must send as many emails. When using Php Mail function, it processes Bcc headers that way.
     *
     * @param string $subject Subject
     * @param string $mail String containing the email data
     * @param \Nettools\Mailing\MailPieces\Headers $headers Email headers
	 * @throws \Nettools\Mailing\Exception
	 */
	function handleBcc($subject, $mail, Headers $headers)
	{
		if ( $bcc = $headers->get('Bcc') )
		{
			// remove Bcc header
			$headers->remove('Bcc');
			
			
			// For all Bcc recipients, send a copy of the email 
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
     * Handle Cc 
     *
     * With a Cc header, we must do specific things. SMTP does not handle Cc.
     *
     * @param string $subject Subject
     * @param string $mail String containing the email data
     * @param \Nettools\Mailing\MailPieces\Headers $headers Email headers
	 * @throws \Nettools\Mailing\Exception
	 */
	function handleCc($subject, $mail, Headers $headers)
	{
		if ( $cc = $headers->get('Cc') )
		{
			// For all Cc recipients, send a copy of the email 
			//
			// 1. We remind that Cc recipients are regular recipients ; `to` and `cc` headers are only for information purposes
			// 2. If we deal with SMTP, it's the RCPT-TO smtp command that sets the real recipient. This is the same as the name on the enveloppe (RCPT-TO) and the
			// name on the letter inside the enveloppe (To and Cc headers, that are not seen by mail carriers)
			$cc_to = explode(',', $cc);
			foreach ( $cc_to as $cc )
				$this->sendTo(trim($cc), $subject, $mail, $headers);
		}
	}


	
	/**
     * Prepare for sending the email (we handle here the Bcc case)
     *
     * @param string $to Recipients, separated by `,`
     * @param string $subject Subject
     * @param string $mail String containing the email data
     * @param \Nettools\Mailing\MailPieces\Headers $headers Email headers
	 * @throws \Nettools\Mailing\Exception
     */
	function handleSend($to, $subject, $mail, Headers $headers)
	{
		// handle Bcc ; headers array may be modified after the call (Bcc line removed)
		$this->handleBcc($subject, $mail, $headers);
		
		// send to all Cc recipients
		$this->handleCc($subject, $mail, $headers);
		
		// send to all recipients
		$this->handleRecipients($to, $subject, $mail, $headers);
	}
	
	
	
	/**
     * Prepare for sending the email to each recipient
     *
     * @param string $to Recipients separated by `,`
     * @param string $subject Subject
     * @param string $mail String containing the email data
     * @param \Nettools\Mailing\MailPieces\Headers $headers Email headers
	 * @throws \Nettools\Mailing\Exception
     */
	function handleRecipients($to, $subject, $mail, Headers $headers)
	{
		// if recipients is not an array, converting it to an array of recipients
		$to = $to ? array_map(function($r){ return trim($r); }, explode(',', $to)) : array();			

		// send the email to each recipient
		foreach ( $to as $recipient )
			$this->sendTo($recipient, $subject, $mail, $headers);
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
     * @param string $to Recipients separated by `,`
     * @param string $subject Subject
     * @param \Nettools\Mailing\MailPieces\Headers $headers Email headers
     */
	function handleHeaders_ToSubject($to, $subject, Headers $headers)
	{
		$headers
			->setEncodedRecipient('To', $to)
			->setEncoded('Subject', $subject);
	}
	
	
	
	/**
     * Handle priority ; we always set high priorty at the moment
     * 
     * @param \Nettools\Mailing\MailPieces\Headers $headers Email headers
     */
	function handleHeaders_Priority(Headers $headers)
	{
/*		$headers->set('X-Priority', '1')
				->set('X-MSMail-Priority', '1')
				->set('Importance', 'High');*/
	}
	

	
	/**
     * Encode the Cc header 
     * 
     * @param \Nettools\Mailing\MailPieces\Headers $headers Email headers
     */
	function handleHeaders_Cc(Headers $headers)
	{
		$headers->encodeRecipient('Cc');
	}
	
	
	
	/**
     * Handle `From` header encoding
     * 
     * @param \Nettools\Mailing\MailPieces\Headers $headers Email headers
     */
	function handleHeaders_From(Headers $headers)
	{
		$headers->encodeRecipient('From');
	}
	
	
	
	/**
     * Create `Message-ID` header 
     * 
     * @param \Nettools\Mailing\MailPieces\Headers $headers Email headers
     */
	function handleHeaders_MessageId(Headers $headers)
	{
		$from = $headers->get('From');
		if ( $from && preg_match('/[^@]+(@[^>\\r\\n]+)/', $from, $regs) )
			$headers->set('Message-ID', '<' . sha1(uniqid()) . $regs[1] . '>');
		else
			$headers->set('Message-ID', '<' . sha1(uniqid()) . '@' . md5(time()) . '.com>');				
	}
	
	
	
	/**
     * Create `Date` header encoding
     * 
     * @param \Nettools\Mailing\MailPieces\Headers $headers Email headers
     */
	function handleHeaders_DateMimeVersion(Headers $headers)
	{
		$headers
			->set('Date', date('r'))
			->set('MIME-Version', '1.0');
	}
	
	
	
	/**
     * Handle headers modifications (from/to/subject/priority)
     * 
     * @param string $to Recipients separated by `,`
     * @param string $subject Subject
     * @param \Nettools\Mailing\MailPieces\Headers $headers Email headers
     */
	function handleHeaders($to, $subject, Headers $headers)
	{
		// encode From header if required
		$this->handleHeaders_From($headers);
		
		// encode Cc header
		$this->handleHeaders_Cc($headers);
		
		// create Date and Mime-Version header
		$this->handleHeaders_DateMimeVersion($headers);
		
		// add To and Subject headers
		$this->handleHeaders_ToSubject($to, $subject, $headers);
		
		// handle priority headers
		$this->handleHeaders_Priority($headers);
	}
	
	
	
	/**
     * Send the email
     *
     * @param string $to Recipients separated with `,`
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
			// processing mandatory headers
			$this->handleHeaders($to, $subject, $headers);
			
			// send to recipients and handle bcc
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
	function ready()
	{
		return $this->mailSender->ready();
	}
	
	
	
	/**
     * Destruct strategy (do housecleaning stuff such as closing SMTP connections)
     */
	function destroy()
	{
		$this->mailSender->destroy();
	}
}


?>