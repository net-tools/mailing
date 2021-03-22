<?php
/**
 * MailSender
 *
 * @author Pierre - dev@nettools.ovh
 * @license MIT
 */

// namespace
namespace Nettools\Mailing\MailSendersFacade\Quotas;



use \Nettools\Mailing\MailSenders\MailSenderIntf;




/**
 * Email sending strategy with quota handling ; acts as a decorator to another class implementing MailSenderIntf interface
 */
class MailSender implements MailSenderIntf{

	protected $ms;
	protected $qi;
	protected $name;
	
	
	/** 
     * Constructor
     * 
	 * @param string $name Name of mailsender strategy (same name as proxy, ex 'SMTP:aws')
	 * @param \Nettools\Mailing\Quotas\QuotaInferface $qi Quota interface object to send 'email sent' events acknowledgements to
     * @param \Nettools\Mailing\MailSenders\MailSenderIntf $ms Mailsender strategy to decorate with quota handling
     */
	function __construct($name, MailSenderIntf $ms, QuotaInterface $qi)
	{
		$this->name = $name;
		$this->ms = $ms;
		$this->qi = $qi;
	}
	
	
	
	/**
	 * Get underlying mail sender
	 *
	 * @return \Nettools\Mailing\MailSenders\MailSenderIntf
	 */
	function getUnderlyingObject()
	{
		return $this->ms;
	}
	
	
	
	/**
     * Send the email
     *
     * @param string $to Recipient
     * @param string $subject Subject ; must be encoded if necessary
     * @param string $mail String containing the email data
     * @param string $headers Email headers
	 * @throws \Nettools\Mailing\Exception
     */
	function send($to, $subject, $mail, $headers)
	{
		// send email through decorated instance
		$this->ms->send($to, $subject, $mail, $headers);
		$this->quotaAdd();
	}

	

	/**
     * Is the sending strategy ready (all required parameters set) ?
     *
     * @return bool Returns TRUE if strategy if ready
     */
	function ready()
	{
		return $this->ms->ready();
	}
	
	
	
	/**
     * Destruct strategy (do housecleaning stuff such as closing SMTP connections)
     */
	function destruct()
	{
		$this->ms->destruct();
	}
	
	
	
	/** 
	 * Add quota
	 */
	protected function quotaAdd()
	{
		$this->qi->add($this->name, time());
	}
}


?>