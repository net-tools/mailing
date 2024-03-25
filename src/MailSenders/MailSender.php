<?php
/**
 * MailSender
 *
 * @author Pierre - dev@nettools.ovh
 * @license MIT
 */



// namespace
namespace Nettools\Mailing\MailSenders;






/**
 * Base class for an email sending strategy (PHP Mail function, SMTP, etc.)
 */
abstract class MailSender {

	// [----- PROTECTED -----
	
    /** @var string[] Array of strategy parameters */
	protected $params = NULL;

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
     * Is the sending strategy ready (all required parameters set) ?
     *
     * @return bool Returns TRUE if strategy is ready
     */
	function ready() { return true; }
	
	
	
	/**
     * Destruct strategy (do housecleaning stuff such as closing SMTP connections)
     */
	function destroy() {}
}


?>