<?php

// namespace
namespace Nettools\Mailing\MailSenders;


use \Nettools\Mailing\MailSender;
use \Nettools\Mailing\Mailer;



// strategy to send emails with SMTP protocol
// expected constructor parameters : host, port, auth, username, password, persist
class SMTP_MailSender extends MailSender
{
	// [----- PROTECTED -----
	
	protected $smtp = NULL;
	protected $initerror = NULL;
	
	
	// send the email through smtp (headers is an array)
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
		if ( $this->smtp && $this->smtp instanceof \PEARError )
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