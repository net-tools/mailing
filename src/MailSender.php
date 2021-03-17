<?php
/**
 * MailSender
 *
 * @author Pierre - dev@nettools.ovh
 * @license MIT
 */



// namespace
namespace Nettools\Mailing;



/**
 * Base class for an email sending strategy (PHP Mail function, SMTP, etc.)
 */
abstract class MailSender{

	// [----- STATIC -----
	
    /** @var string Parameter name for the log callback ; to be used in the `$params` parameter in constructor of `factory` method */
	const CALLBACK_LOG = "callback_log";

    /** @var string Parameter name for the log callback data ; to be used in the `$params` parameter in constructor of `factory` method */
    const CALLBACK_LOG_DATA = "callback_log_data";

	// ----- STATIC -----]


	// [----- PROTECTED -----
	
    /** @var string[] Array of strategy parameters */
	protected $params = NULL;
	
	
	/** 
     * Callback to the caller when email sent (if needed)
     */
	protected function _acknowledgeMailSent()
	{
		if ( $this->params[self::CALLBACK_LOG] )
			call_user_func_array($this->params[self::CALLBACK_LOG], $this->params[self::CALLBACK_LOG_DATA]);
	}

	// ----- PROTECTED -----]
	
	/** 
     * Constructor
     * 
     * @param string[]|NULL $params Array of parameters for the sending strategy 
     */
	function __construct($params = NULL)
	{
		if ( is_null($params) )
			$params = array();
			
		$this->params = $params;
	}
	

	/**
     * Send the email (to be implemented in child classes)
     *
     * @param string $to Recipient
     * @param string $subject Subject ; must be encoded if necessary
     * @param string $mail String containing the email data
     * @param string $headers Email headers
     * @return bool|string Returns FALSE if sending is done (no error), or an error string if an error occured
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
     * @return bool Always returns FALSE (no error)
     */
	function handleBcc($to, $subject, $mail, &$headers)
	{
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

	
	/**
     * Prepare for sending the email (we handle here the Bcc case)
     *
     * @param string $to Recipient
     * @param string $subject Subject ; must be encoded if necessary
     * @param string $mail String containing the email data
     * @param string $headers Email headers
     * @return bool|string Returns FALSE if sending is done (no error), or an error string if an error occured
     */
	function handleSend($to, $subject, $mail, $headers)
	{
		// handle Bcc ; headers array may be modified after the call (Bcc line removed)
		$this->handleBcc($to, $subject, $mail, $headers);
		
		// send the email
		return $this->doSend($to, $subject, $mail, $headers);
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
		$headers = Mailer::addHeader($headers, "X-Priority: 1");
//		$headers = Mailer::addHeader($headers, "X-MSMail-Priority: High"); //nécessite X-MimeOLE qui indique que le message a été rédigé avec outlook
		$headers = Mailer::addHeader($headers, "Importance: High");
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
     * @return bool|string Returns FALSE if sending is done (no error), or an error string if an error occured
     */
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
	
	
	/**
     * Get an error message explaining why the strategy is not ready
     *
     * @return string Error message
     */
	function getMessage() { return NULL; }
}


?>