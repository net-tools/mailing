<?php
/**
 * SMTP
 *
 * @author Pierre - dev@nettools.ovh
 * @license MIT
 */



// namespace
namespace Nettools\Mailing\MailSenders;


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
class SMTP extends MailSender
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
	 * @throws \Nettools\Mailing\Exception
     */
	protected function _doSend($to, $mail, $headers)
	{
		$ret = $this->smtp->send($to, $headers, $mail);
		if ( $ret === TRUE )
			return;
		else
			throw new \Nettools\Mailing\Exception($ret->toString());
	}
	
	
	// ----- PROTECTED -----]

    /**
	 * Constructor
	 * 
	 * @param string[] $params
	 * @throws \Nettools\Mailing\Exception
	 */
    function __construct($params = NULL)
	{
		// parent constructor call
		parent::__construct($params);
		

		// we must at least have the host
		if ( !isset($this->params['host']) )
			throw new \Nettools\Mailing\Exception("SMTP Host parameter missing");

		
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
			throw new \Nettools\Mailing\Exception("Composer libraries missing : pear/mail or pear/net_smtp or net-tools/auth_sasl");

		
		
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
		
		
		if ( $this->smtp instanceof \PEAR_Error )
			throw new \Nettools\Mailing\Exception($this->smtp->toString());
	}
	
	
	
	/**
	 * is the SMTP connection ready ?
	 *
	 * @return bool
	 */
	function ready()
	{
		// tester 
		return parent::ready() && $this->smtp && ($this->smtp instanceof \Mail);
	}
	
	
	
	/**
	 * destruct object, and disconnet SMTP 
	 */
	function destruct()
	{
		if ( $this->params['persist'] && $this->ready() )
		{
			$level = error_reporting(E_ERROR);		// incompatibility php 5.4
			$this->smtp->disconnect();
			error_reporting($level);				// incompatibility php 5.4
		}
	}
	
	
	
	/**
	 * implement sending
	 *
     * @param string $to Recipient
     * @param string $subject Subject ; must be encoded if necessary
     * @param string $mail String containing the email data
     * @param string $headers Email headers
	 * @throws \Nettools\Mailing\Exception
	 */
	function doSend($to, $subject, $mail, $headers)
	{
		// PEAR SMTP::send expects headers to be an associative array
		$this->_doSend($to, $mail, Mailer::headersToArray($headers));
	}
}
?>