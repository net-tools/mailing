<?php

// namespace
namespace Nettools\Mailing;




// base class for an email sending strategy (PHP Mail function, SMTP, etc.)
abstract class MailSender{

	// [----- STATIC -----
	
	const EMLFILE = "EmlFile_MailSender";
	const PHPMAIL = "PHPMail_MailSender";
	const SMTP = "SMTP_MailSender";
	const VIRTUAL = "Virtual_MailSender";
	
	const CALLBACK_LOG = "callback_log";
	const CALLBACK_LOG_DATA = "callback_log_data";

	
	// create an object instance for the strategy
	static function factory($concreteSenderName, $params = NULL)
	{
		$cname = "\\Nettools\\Mailing\\MailSenders\\$concreteSenderName";
		return new $cname($params);
	}

	// ----- STATIC -----]


	// [----- PROTECTED -----
	
	protected $params = NULL;
	
	
	// callback to the caller when email sent (if needed)
	protected function _acknowledgeMailSent()
	{
		if ( $this->params[self::CALLBACK_LOG] )
			call_user_func_array($this->params[self::CALLBACK_LOG], $this->params[self::CALLBACK_LOG_DATA]);
	}

	// ----- PROTECTED -----]
	
	// constructor
	function __construct($params = NULL)
	{
		if ( is_null($params) )
			$params = array();
			
		$this->params = $params;
	}
	

	// implement email sending in child classes
	abstract function doSend($to, $subject, $mail, $headers);
	

	// handle Bcc case
	function handleBcc($to, $subject, $mail, &$headers)
	{
		// if BCC header, we must do specific things. SMTP does not handle Bcc. When having a Bcc header, we must send 
		// an 'normal' email to this Bcc recipient (and removing Bcc header, which the recipient must not see). If multiple
		// recipients, we must send as many emails. When using Php Mail function, it processes Bcc headers that way.
		if ( $bcc = Mailer::getHeader($headers, 'Bcc') )
		{
			// remove Bcc header
			$headers = Mailer::removeHeader($headers, 'Bcc');
			
			// for all Bcc recipients, send them a 'normal' email with their email in a To header
			$bcc_to = explode(',', $bcc);
			foreach ( $bcc_to as $bcc )
				// envoyer avec BCC comme destinataire ; headers est privé de son champ BCC
				$this->doSend(trim($bcc), $subject, $mail, $headers);
		}
		
		return FALSE;
	}

	
	// prepare for sending the email (handle Bcc case)
	function handleSend($to, $subject, $mail, $headers)
	{
		// handle Bcc ; headers array may be modified after the call (Bcc line removed)
		$this->handleBcc($to, $subject, $mail, $headers);
		
		// send the email
		return $this->doSend($to, $subject, $mail, $headers);
	}
	
	
	// handle the To and Subject headers
	function handleHeaders_ToSubject($to, $subject, $mail, &$headers)
	{
		$headers = Mailer::addHeader($headers, "To: $to");
		$headers = Mailer::addHeader($headers, "Subject: $subject");
	}
	
	
	// Handle priority ; we always set high priorty at the moment
	function handleHeaders_Priority($to, $subject, $mail, &$headers)
	{
		$headers = Mailer::addHeader($headers, "X-Priority: 1");
//		$headers = Mailer::addHeader($headers, "X-MSMail-Priority: High"); //nécessite X-MimeOLE qui indique que le message a été rédigé avec outlook
		$headers = Mailer::addHeader($headers, "Importance: High");
	}
	
	
	// handle headers modifications (to/subject/priority)
	function handleHeaders($to, $subject, $mail, &$headers)
	{
		$this->handleHeaders_ToSubject($to, $subject, $mail, $headers);
		$this->handleHeaders_Priority($to, $subject, $mail, $headers);
	}
	
	
	// send the email ; return FALSE if OK, an error string otherwise
	function send($to, $subject, $mail, $headers)
	{
		// if init OK
		if ( $this->ready() )
		{
			// handle headers processing
			$this->handleHeaders($to, $subject, $mail, $headers);
			
			// send
			$ret = $this->handleSend($to, $subject, $mail, $headers);
			
			// if sending OK, acknowledge it
			if ( $ret === FALSE )
				$this->_acknowledgeMailSent();
				
			return $ret;
		}
		else
			return __CLASS__ . ' not ready : \'' . $this->getMessage() . '\'';
	}	


	// are we ready ?
	function ready() { return true; }
	
	
	// destruct strategy
	function destruct() {}
	
	
	// get error
	function getMessage() { return NULL; }
}


?>