<?php
/**
 * MailSenderIntf
 *
 * @author Pierre - dev@nettools.ovh
 * @license MIT
 */



// namespace
namespace Nettools\Mailing;



/**
 * Interface class for an email sending strategy (PHP Mail function, SMTP, etc.)
 */
interface MailSenderIntf{

	/**
     * Send the email
     *
     * @param string $to Recipient
     * @param string $subject Subject ; must be encoded if necessary
     * @param string $mail String containing the email data
     * @param string $headers Email headers
     * @return bool|string Returns FALSE if sending is done (no error), or an error string if an error occured
     */
	function send($to, $subject, $mail, $headers);	


	/**
     * Is the sending strategy ready (all required parameters set) ?
     *
     * @return bool Returns TRUE if strategy if ready
     */
	function ready();
	
	
	/**
     * Destruct strategy (do housecleaning stuff such as closing SMTP connections)
     */
	function destruct();
	
	
	/**
     * Get an error message explaining why the strategy is not ready
     *
     * @return string Error message
     */
	function getMessage();
}


?>