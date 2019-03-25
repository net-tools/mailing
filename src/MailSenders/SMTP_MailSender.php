<?php
/**
 * SMTP_MailSender
 *
 * @author Pierre - dev@nettools.ovh
 * @license MIT
 */




// namespace
namespace Nettools\Mailing\MailSenders;


use \Nettools\Mailing\MailSender;
use \Nettools\Mailing\Mailer;



/**
 * Strategy to send emails with SMTP protocol
 *
 * We expect that the constructor receives the following parameters :
 *
 * - host
 * - port
 * - auth (true or false)
 * - username
 * - password
 * - persist (true to let the connection open or false to close it)
 * 
 */
class SMTP_MailSender extends MailSender
{
	// [----- PROTECTED -----
	
    /** @var object SMTP object used (from PEAR lib) */
	protected $smtp = NULL;
    
    /** @var string Last error message */
	protected $initerror = NULL;
	
	
	/**
     * Send the email through smtp
     *
     * @param string $to Recipient
     * @param string $mail Email to send, as text
     * @param string[] $headers Email headers array
     */
	protected function _doSend($to, $mail, $headers)
	{
		$ret = $this->smtp->send($to, $headers, $mail);
		if ( $ret === TRUE )
			return FALSE;
		else
			return $ret->toString();
	}
	
	
	// ----- PROTECTED -----]

    // constructor
    function __construct($params = NULL)
	{
		// parent constructor call
		parent::__construct($params);
		

		// we must at least have the host
		if ( !isset($this->params['host']) )
		{
			$this->initerror = "SMTP Host parameter missing";
			return;
		}

		// default values
		$this->params['port'] or $this->params['port'] = '25';
		$this->params['auth'] or $this->params['auth'] = FALSE;
		$this->params['persist'] or $this->params['persist'] = FALSE;
		
		
		// test that required libraries are available
		if ( 
				(strpos(get_include_path(), 'net-tools/auth_sasl') === FALSE)
				||
				(strpos(get_include_path(), 'pear/mail') === FALSE)
				||
				(strpos(get_include_path(), 'pear/net_smtp') === FALSE)
			)
		{
			$this->smtp = null;
			$this->initerror = "Composer libraries missing : pear/mail or pear/net_smtp or net-tools/auth_sasl";
		}
		else
			// create connection
			$this->smtp = \Mail::factory('smtp', 
				array(
						  'host'         	=> $this->params['host'],
						  'port'         	=> $this->params['port'],
						  'auth'         	=> $this->params['auth'] ? TRUE:FALSE,
						  'username'     	=> $this->params['username'],
						  'password'     	=> $this->params['password'],
						  'persist'      	=> $this->params['persist'] ? TRUE:FALSE,
						  'socket_options'	=> array('ssl'=>array('verify_peer'=>false))
					)); 
	}
	
	
	// is the SMTP connection ready ?
	function ready()
	{
		// tester 
		return parent::ready() && $this->smtp && ($this->smtp instanceof \Mail);
	}
	
	
	// destruct object, and disconnet SMTP 
	function destruct()
	{
		if ( $this->params['persist'] && $this->ready() )
		{
			$level = error_reporting(E_ERROR);		// incompatibility php 5.4
			$this->smtp->disconnect();
			error_reporting($level);				// incompatibility php 5.4
		}
	}
	
	
	// get info for last error
	function getMessage()
	{
		if ( $this->smtp && $this->smtp instanceof \PEAR_Error )
			return $this->smtp->toString();
		else
		if ( $this->initerror )
			return $this->initerror;
		else
			return NULL;
	}
	
		
	// implement sending
	function doSend($to, $subject, $mail, $headers)
	{
		// PEAR SMTP::send expects headers to be an associative array
		return $this->_doSend($to, $mail, Mailer::headersToArray($headers));
	}
}
?>