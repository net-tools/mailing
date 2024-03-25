<?php
/**
 * MailSender
 *
 * @author Pierre - dev@nettools.ovh
 * @license MIT
 */



// namespace
namespace Nettools\Mailing\MailSenders;


use \Nettools\Mailing\MailerEngine\Headers;





/**
 * Base class for an email sending strategy (PHP Mail function, SMTP, etc.)
 */
abstract class MailSender {

	// [----- PROTECTED -----
	
	/** @var SentHandler[] $sentEvent Event handler list for `sent` notification */
	protected $sentEvents = array();

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
     * Send the email and fire `sent` event
     *
     * @param string $to Recipient (no friendly name, only address part)
     * @param string $subject Subject (must be encoded)
     * @param string $mail String containing the email data
     * @param \Nettools\Mailing\MailerEngine\Headers $headers Email headers object
     */
	function send($to, $subject, $mail, Headers $headers)
	{
		// send email through implementation of abstract method `doSend`
		$this->doSend($to, $subject, $mail, $headers->toString());

		
		// fire event
		foreach ( $this->getSentEventHandlers() as $evt )
			$evt->notify($to, $subject, $headers);
	}
	

	
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