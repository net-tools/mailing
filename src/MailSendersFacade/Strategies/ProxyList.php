<?php
/**
 * ProxyList
 *
 * @author Pierre - dev@nettools.ovh
 * @license MIT
 */



// namespace
namespace Nettools\Mailing\MailSendersFacade\Strategies;



/**
 * Listing all email sending strategies
 */
class ProxyList{

	protected $lst;
	protected $active;
	
	
	/**
	 * Constructor
	 *
	 * $list is an array of object litterals describing how to build the MailSender proxy class list :
	 * 		[
	 *			{
	 				className	: 'SMTP',
					name		: 'SMTP:aws',
					params		: { param1 : value1, ...}
	 *			}
	 * 		]
	 *
	 * @param object[] $list List of mailsenders strategies as object litterals
	 * @param string $active Name of active mailsender strategy (ex. 'SMTP:aws')
	 */
	public function __construct($list, $active)
	{
		$this->lst = [];
		
		// creating MailSenderProxy instances based on `$list` array data
		foreach ( $list as $item )
			$this->lst[] = new \Nettools\Mailing\MailSenderProxy($item->className, $item->name, $item->params);

		// search for strategy with name $active
		foreach ( $this->lst as $msp )
			if ( $msp->name == $active )
			{
				$this->active = $msp;
				break;
			}
	}
	
	

	/**
	 * Get a list of strategies proxy
	 * 
	 * @return \Nettools\Mailing\MailSenderProxy[]
	 */	 
	public function getProxyList()
	{
		return $this->lst;
	}
	
	
	
	/**
	 * Get active strategy proxy
	 *
	 * @return \Nettools\Mailing\MailSenderProxy
	 */
	public function getActiveProxy()
	{
		return $this->active;
	}
}


?>