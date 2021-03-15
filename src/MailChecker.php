<?php
/**
 * MailChecker
 *
 * @author Pierre - dev@nettools.ovh
 * @license MIT
 */


// namespace
namespace Nettools\Mailing;




/** 
 * Class to handle mail existence check
 */
class MailChecker
{
	protected $checker;
	
	
	
	/**
	 * Constructor
	 *
	 * @param \Nettools\Mailing\MailCheckers\Checker $checker Class strategy to use to check the email
	 */
	public function __construct(MailCheckers\Checker $checker)
	{
		$this->checker = $checker;			
	}
	
	
	
	/** 
	 * Get MaiChecker strategy
	 *
	 * @return \Nettools\Mailing\MailCheckers\Checker
	 */
	public function getMailChecker()
	{
		return $this->checker;
	}
	
	
	
	/**
	 * Check that a given email exists
	 * 
	 * @param string $email
	 * @return bool Returns true if the email can be delivered, false otherwise
	 */
	public function check($email)
	{
		return $this->getMailChecker()->check($email);
	}
}
?>