<?php
/**
 * SentHandler
 *
 * @author Pierre - dev@nettools.ovh
 * @license MIT
 */

// namespace
namespace Nettools\Mailing\MailSendersFacade\Quotas;


use \Nettools\Mailing\MailerEngine\Headers;





/**
 * Event handler for `sent` event of a MailSender strategy
 */
class SentHandler extends \Nettools\Mailing\MailSenders\SentHandlers\Handler
{
	protected $qi;
	protected $name;
	
	
	
	/** 
     * Constructor
     * 
	 * @param string $name String for mailsender strategy, same name as proxy name (ex. SMTP:aws)
	 * @param \Nettools\Mailing\Quotas\QuotaInferface $qi Quota interface object to send 'email sent' events acknowledgements to
     */
	function __construct($name, QuotaInterface $qi)
	{
		$this->qi = $qi;
		$this->name = $name;
	}
	
	
	
	/** 
	 * Event handler for `sent` notification
	 *
     * @param string $to Recipient
     * @param string $subject Subject
     * @param Nettools\Mailing\MailerEngine\Headers $headers Email headers
	 */
	function notify($to, $subject, Headers $headers)
	{
		$this->qi->add($this->name, time());
	}
}


?>