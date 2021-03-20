<?php

// namespace
namespace Nettools\Mailing\MailSenderHelpers;

// clauses use
use \Nettools\Mailing\MailPieces\MailContent;
use \Nettools\Mailing\Mailer;
use \Nettools\Mailing\MailSenderQueue;




/**
 * Helper class to send several emails
 *
 * Subject, template, from address, bcc, replyto can be set at object construction, then all required customizations are applied to mail objects sent through `send` method.
 * It also makes it possible to send emails to tests recipients or to a queue object, again just by setting appropriate parameters of constructor
 */
class MailSenderHelper implements MailSenderHelperInterface
{
	protected $mail = NULL;
	protected $mailContentType = NULL;
	protected $from = NULL;
	protected $subject = NULL;
	protected $template = NULL;
	protected $msenderq = NULL;
	protected $msenderq_params = NULL;
	protected $testmode = NULL;
	protected $bcc = NULL;
	protected $replyto = false;
	protected $to_override = NULL;
	protected $testmails = NULL;
	protected $mailer = NULL;
	
	
	
	/**
	 * Create a MailContent object from a string
	 *
	 * @param string $mail Mail raw content as string
	 * @return \Nettools\Mailing\MailPieces\MailContent
	 * @throws \Nettools\Mailing\MailSenderHelpers\Exception
	 */
	protected function _createMailContent($mail)
	{
		switch ( $this->mailContentType )
		{
			case 'text/plain' : 
				return Mailer::addTextHtmlFromText($mail, $this->template);
				
			case 'text/html': 
				return Mailer::addTextHtmlFromHtml($mail, $this->template);
				
			default:
				throw new \Nettools\Mailing\MailSenderHelpers\Exception('Unknown content-type : ' . $this->mailContentType);
		}
	}


	const MAILSENDERQUEUE_PATH = "path";
	const MAILSENDERQUEUE_BATCH = "batch";

	
	
	
	/**
	 * Constructor
	 *
	 * @param \Nettools\Mailing\Mailer $mailer
	 * @param string $mail Mail content as a string
	 * @param string $mailContentType May be 'text/plain' or 'text/html'
	 * @param string $from Sender address
	 * @param string $subject
	 * @param bool $testmode If true, email are sent to testing addresses
	 * @param string $template Template string of email ; if set, must include a `%content%` string that will be replaced by the actual mail content
	 * @param string $bcc If set, Email BCC address to send a copy to
	 * @param string $msenderq If set, a MailSenderQueue name to append emails to
	 * @param string $msenderq_params If set, parameters of `$msenderq` queue
	 * @param string[] $testmails If set, an array of email addresses to send emails to for testing purposes
	 * @param string $replyto If set, an email address to set as ReplyTo header
	 */
	function __construct(Mailer $mailer, $mail, $mailContentType, $from, $subject, $testmode, $template = NULL, $bcc = NULL, $msenderq = NULL, $msenderq_params = NULL, $testmails = NULL, $replyto = NULL)
	{
		// paramètres
		$this->mailer = $mailer;
		$this->mail = $mail;
		$this->mailContentType = $mailContentType;
		$this->from = $from;
		$this->subject = $subject ? Mailer::encodeSubject($subject) : NULL;
		$this->template = $template ? $template : '%content%';
		$this->msenderq = $msenderq;
		$this->msenderq_params = $msenderq_params;
		$this->testmode = $testmode;
		$this->bcc = $bcc;
		$this->to_override = NULL;
		$this->testmails = $testmails;
		$this->replyto = $replyto;
	}
	
	
	
	/** 
	 * Getter for ToOverride
	 *
	 * @return NULL|string Returns NULL if no override, a string with email address otherwise
	 */
	public function getToOverride() { return $this->to_override;}
	
	
	
	/**
	 * Setter for ToOverride
	 * 
	 * @param strig $o Email address to send all emails to (for debugging purpose)
	 * return \Nettools\Mailing\MailSenderHelpers\MailSenderHelper Returns the calling object for chaining
	 */
	public function setToOverride($o) { $this->to_override = $o; return $this;}
	
	
	
	/**
	 * Accessor for test mode
	 *
	 * @return bool
	 */
	public function getTestMode() { return $this->testmode;}
	
	
	
	/**
	 * Get raw mail string before any rendering actions
	 *
	 * @return string
	 */
	public function getRawMail() { return $this->mail; }
	
	
	
	/**
	 * Update raw mail string
	 * 
	 * @param string $m
	 * return \Nettools\Mailing\MailSenderHelpers\MailSenderHelper Returns the calling object for chaining
	 */
	public function setRawMail($m) { $this->mail = $m; return $this; }

	
	
	/**
	 * Destruct object
	 */
	public function destruct()
	{
		if ( $this->mailer )
			$this->mailer->destruct();
	}
	
	
	
	/** 
	 * Testing that required parameters are set
	 *
	 * @throws \Nettools\Mailing\MailSenderHelpers\Exception
	 */
	public function ready()
	{
		if ( !isset($this->mailer) )
            throw new \Nettools\Mailing\MailSenderHelpers\Exception("MailSenderHelper::mailer is not defined");

		if ( !isset($this->mail) )
            throw new \Nettools\Mailing\MailSenderHelpers\Exception("MailSenderHelper::mail is not defined");
        
		if ( !isset($this->mailContentType) )
        	throw new \Nettools\Mailing\MailSenderHelpers\Exception("MailSenderHelper::mailContentType is not defined");

		if ( !isset($this->from) )
            throw new \Nettools\Mailing\MailSenderHelpers\Exception("MailSenderHelper::from is not defined");

		if ( !isset($this->subject) )
            throw new \Nettools\Mailing\MailSenderHelpers\Exception("MailSenderHelper::subject is not defined");

		if ( !isset($this->template) )
            throw new \Nettools\Mailing\MailSenderHelpers\Exception("MailSenderHelper::template is not defined");
		
		if ( !isset($this->testmode) )
            throw new \Nettools\Mailing\MailSenderHelpers\Exception("MailSenderHelper::testmode is not defined");

		
		if ( $this->testmode )
		{
			if ( !isset($this->testmails) )
            	throw new \Nettools\Mailing\MailSenderHelpers\Exception("MailSenderHelper::testmails is not defined");
			
			if ( !is_array($this->testmails) )
            	throw new \Nettools\Mailing\MailSenderHelpers\Exception("MailSenderHelper::testmails is not an array");
			
			if ( count($this->testmails) == 0 )
            	throw new \Nettools\Mailing\MailSenderHelpers\Exception("MailSenderHelper::testmails is an empty array");
		}
	}
	
	
	
	/**
	 * Compute email 
	 *
	 * @param mixed $data Data that may be required during rendering process
	 * @return \Nettools\Mailing\MailPieces\MailContent
	 * @throws \Nettools\Mailing\MailSenderHelpers\Exception
	 */
	public function render($data)
	{
		// testing mandatory parameters (exception thrown)
		$this->ready();
		
		// render email and get a MailContent object
		return $this->_createMailContent($this->mail);
	}
	
	
	
	/**
	 * Send the email
	 *
	 * @param \Nettools\Mailing\MailPieces\MailContent $mail
	 * @param string $mto Email recipient
	 * @param string $subject Specific email subject ; if NULL, the default value passed to the constructor will be used
	 * @throws \Nettools\Mailing\MailSenderHelpers\Exception
	 */
	public function send(MailContent $mail, $mto, $subject = NULL)
	{
		// if sending as batch (otherwise NULL), msender contains the queue name at first call ; then it will contain a record with required queue objects to send emails to
		if ( $this->msenderq && is_string($this->msenderq) )
		{
			// getting path of queue, creating
			$ms = new MailSenderQueue($this->msenderq_params[self::MAILSENDERQUEUE_PATH]);
			$msqueue = $ms->createQueue($this->msenderq . '_' . date('Ymd'), $this->msenderq_params[self::MAILSENDERQUEUE_BATCH]);
			$this->msenderq = array('ms'=>$ms, 'msqueue'=>$msqueue);
		}


		// test mode ?
		if ( $this->testmode )
		{
			// next test mail
			$to = current($this->testmails);
			next($this->testmails);

			// if no more test email ($to = NULL) or if we are not using a queue, exiting as we only simulate
			if ( !$to || is_null($this->msenderq) )
				return; 
		}
		else
			// in real mode, sending to a real email address
			$to = $mto; 


		// checking overide parameter
		$dest = $this->to_override;
		$dest or $dest = $to;		
		
		// checking email syntax
		if ( is_null($dest) )
			throw new \Nettools\Mailing\MailSenderHelpers\Exception("Empty email recipient");
		if ( !preg_match("/^[a-z0-9]+([_|\.|-]{1}[a-z0-9]+)*@[a-z0-9]+([_|\.|-]{1}[a-z0-9]+)*[\.]{1}[a-z]{2,6}$/", $dest) )
			throw new \Nettools\Mailing\MailSenderHelpers\Exception("Malformed email : '$dest'");
			
		
		// dealing with BCC
		if ( $this->bcc )
			$mail->addCustomHeader("Bcc: " . $this->bcc);

		
		// dealing with replyTo
		if ( $this->replyto )
			$mail->addCustomHeader("Reply-To: " . $this->replyto);


		// if sending to a queue
		if ( $this->msenderq )
			$this->msenderq['ms']->push($this->msenderq['msqueue'], $mail, $this->from, $dest, $subject ? $subject : $this->subject); 
		else
			$this->mailer->sendmail($mail, $this->from, $dest, $subject ? $subject : $this->subject);
	}
	
	
	
	/**
	 * Closing queue
	 */
	public function closeQueue()
	{
		if ( $this->getQueueCount() > 0 )
			$this->msenderq['ms']->closeQueue($this->msenderq['msqueue']);
	}
	

	
	/**
	 * Get count of emails in queue
	 *
	 * @return int
	 */
	public function getQueueCount()
	{
		// if `msender` property is an array, the queue has already been created and used : at least an email is in there
		if ( is_array($this->msenderq) )
		{
			$q = $this->msenderq['ms']->getQueue($this->msenderq['msqueue']);
			return $q->count;
		}
		
		// if queue but not used yet
		else if ( is_string($this->msenderq) )
			return 0;
		
		
		// no queue used
		else
			return NULL;
	}
}

?>