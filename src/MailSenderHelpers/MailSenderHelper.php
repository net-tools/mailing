<?php

// namespace
namespace Nettools\Mailing\MailSenderHelpers;

// clauses use
use \Nettools\Mailing\MailPieces\MailContent;
use \Nettools\Mailing\Mailer;
use \Nettools\Mailing\MailSenderQueue\Queue;
use \Nettools\Mailing\MailSenderQueue\Store;




/**
 * Helper class to send several emails
 *
 * Subject, template, from address, bcc, replyto can be set at object construction, then all required customizations are applied to mail objects sent through `send` method.
 * It also makes it possible to send emails to tests recipients or to a queue object, again just by setting appropriate parameters of constructor
 */
class MailSenderHelper implements MailSenderHelperIntf
{
	protected $mail = NULL;
	protected $mailContentType = NULL;
	protected $from = NULL;
	protected $subject = NULL;
	protected $template = NULL;
	protected $queue = NULL;
	protected $queueParams = NULL;
	protected $testMode = NULL;
	protected $bcc = NULL;
	protected $replyTo = false;
	protected $toOverride = NULL;
	protected $testRecipients = NULL;
	
	
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


	
	
	/**
	 * Constructor
	 *
	 * Optionnal parameters for `$params` are :
	 *   - template : template string used for email content ; if set, it must include a `%content%` string that will be replaced (call to `render` method) by the actual mail content (arg `$mail`)
	 *   - queue : If set, a MailSenderQueue name to create and append emails to
	 *   - queueParams : If set, parameters of queue subsystem as an associative array with values for keys `root` and `batchCount`
	 *   - bcc : If set, email BCC address to send a copy to
	 *   - testRecipients : If set, an array of email addresses to send emails to for testing purposes
	 *   - replyTo : If set, an email address to set in a ReplyTo header
	 *   - toOverride : If set, sends all email to a given address (debug purposes)
	 *   - testMode : If true, email are sent to testing addresses (see `testRecipients` optionnal parameter) ; defaults to false
	 *
	 * @param \Nettools\Mailing\Mailer $mailer
	 * @param string $mail Mail content as a string
	 * @param string $mailContentType May be 'text/plain' or 'text/html'
	 * @param string $from Sender address
	 * @param string $subject
	 * @param string[] $params Associative array with optionnal parameters
	 */
	function __construct(Mailer $mailer, $mail, $mailContentType, $from, $subject, array $params = [])
	{
		// paramètres
		$this->mailer = $mailer;
		$this->mail = $mail;
		$this->mailContentType = $mailContentType;
		$this->from = $from;
		$this->subject = $subject ? $subject : NULL;

		
		// optionnal parameters
		$this->testMode = array_key_exists('testMode', $params) ? $params['testMode'] : false;
		$this->template = array_key_exists('template', $params) ? $params['template'] : '%content%';
		$this->queue = array_key_exists('queue', $params) ? $params['queue'] : NULL;
		$this->queueParams = array_key_exists('queueParams', $params) ? $params['queueParams'] : NULL;
		$this->bcc = array_key_exists('bcc', $params) ? $params['bcc'] : NULL;
		$this->toOverride = array_key_exists('toOverride', $params) ? $params['toOverride'] : NULL;
		$this->testRecipients = array_key_exists('testRecipients', $params) ? $params['testRecipients'] : NULL;
		$this->replyTo = array_key_exists('replyTo', $params) ? $params['replyTo'] : NULL;
	}
	
	
	
	/** 
	 * Getter for ToOverride
	 *
	 * @return NULL|string Returns NULL if no override, a string with email address otherwise
	 */
	public function getToOverride() { return $this->toOverride;}
	
	
	
	/**
	 * Setter for ToOverride
	 * 
	 * @param strig $o Email address to send all emails to (for debugging purpose)
	 * return \Nettools\Mailing\MailSenderHelpers\MailSenderHelper Returns the calling object for chaining
	 */
	public function setToOverride($o) { $this->toOverride = $o; return $this;}
	
	
	
	/**
	 * Accessor for test mode
	 *
	 * @return bool
	 */
	public function getTestMode() { return $this->testMode;}
	
	
	
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
		if ( empty($this->mailer) )
            throw new \Nettools\Mailing\MailSenderHelpers\Exception("MailSenderHelper::mailer is not defined");

		if ( empty($this->mail) )
            throw new \Nettools\Mailing\MailSenderHelpers\Exception("MailSenderHelper::mail is not defined");
        
		if ( empty($this->mailContentType) )
        	throw new \Nettools\Mailing\MailSenderHelpers\Exception("MailSenderHelper::mailContentType is not defined");

		if ( empty($this->from) )
            throw new \Nettools\Mailing\MailSenderHelpers\Exception("MailSenderHelper::from is not defined");

		if ( empty($this->template) )
            throw new \Nettools\Mailing\MailSenderHelpers\Exception("MailSenderHelper::template is not defined");
		
		
		if ( $this->testMode )
		{
			if ( empty($this->testRecipients) )
            	throw new \Nettools\Mailing\MailSenderHelpers\Exception("MailSenderHelper::testRecipients is not defined");
			
			if ( !is_array($this->testRecipients) )
            	throw new \Nettools\Mailing\MailSenderHelpers\Exception("MailSenderHelper::testRecipients is not an array");
			
			if ( count($this->testRecipients) == 0 )
            	throw new \Nettools\Mailing\MailSenderHelpers\Exception("MailSenderHelper::testRecipients is an empty array");
		}
	}
	
	
	
	/**
	 * Render email content
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
		// if sending as batch (otherwise NULL), msender contains the queue name at first call ; then it will contain the queue object
		if ( $this->queue && is_string($this->queue) )
		{
			// opening queues store
			$store = Store::read($this->queueParams['root'], true);
			$queue = $store->createQueue($this->queue . '_' . date('Ymd'), $this->queueParams['batchCount']);
			$this->queue = $queue;
		}


		// test mode ?
		if ( $this->testMode )
		{
			// next test mail
			$to = current($this->testRecipients);
			next($this->testRecipients);

			// if no more test email ($to = NULL), exiting as we only simulate
			if ( !$to )
				return; 
		}
		else
			// in real mode, sending to a real email address
			$to = $mto; 


		// checking overide parameter
		$dest = $this->toOverride;
		$dest or $dest = $to;		
		
		
		// checking email syntax
		if ( is_null($dest) )
			throw new \Nettools\Mailing\MailSenderHelpers\Exception("Empty email recipient");
		if ( !preg_match("/^[a-z0-9]+([_|\.|-]{1}[a-z0-9]+)*@[a-z0-9]+([_|\.|-]{1}[a-z0-9]+)*[\.]{1}[a-z]{2,6}$/", $dest) )
			throw new \Nettools\Mailing\MailSenderHelpers\Exception("Malformed email : '$dest'");
			
		
		// dealing with BCC
		if ( $this->bcc )
			$mail->headers->set('Bcc', $this->bcc);

		
		// dealing with replyTo
		if ( $this->replyTo )
			$mail->headers->set('Reply-To', $this->replyTo);

		
		// checking a subject is defined, either in constructor parameters or in this method argument
		$subject = $subject ? $subject : $this->subject;
		if ( is_null($subject) )
            throw new \Nettools\Mailing\MailSenderHelpers\Exception("Subject in 'send' method is not defined");


		// if sending to a queue
		if ( is_object($this->queue) && ($this->queue instanceof \Nettools\Mailing\MailSenderQueue\Queue) )
			$this->queue->push($mail, $this->from, $dest, $subject); 
		else
			$this->mailer->sendmail($mail, $this->from, $dest, $subject);
	}
	
	
	
	/**
	 * Closing queue
	 */
	public function closeQueue()
	{
		if ( $this->getQueueCount() > 0 )
			$this->queue->commit();
	}
	

	
	/**
	 * Get count of emails in queue
	 *
	 * @return int
	 */
	public function getQueueCount()
	{
		// if `msender` property is an object, the queue has already been created and used : at least an email is in there
		if ( is_object($this->queue) && ($this->queue instanceof \Nettools\Mailing\MailSenderQueue\Queue) )
			return $this->queue->count;

		
		// if queue but not used yet
		else if ( is_string($this->queue) )
			return 0;
		
		
		// no queue used
		else
			return NULL;
	}
}

?>