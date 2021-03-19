<?php
/**
 * ProxyList
 *
 * @author Pierre - dev@nettools.ovh
 * @license MIT
 */



// namespace
namespace Nettools\Mailing\MailSendersFacade\Res;



use \Nettools\Mailing\MailSendersFacade\Factories\ProxyCreator;




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
	 * @param object[] $list Array of mailsenders strategies as object litterals with above structure
	 * @param string $active Name of active mailsender strategy (ex. 'SMTP:aws')
	 * @param \Nettools\Mailing\MailSendersFacade\Factories\ProxyCreator $creator Strategy used to create a MailSenderProxy of suitable class
	 */
	public function __construct($list, $active, ProxyCreator $creator)
	{
		$this->lst = [];
		
		// creating MailSenderProxy instances based on `$list` array data
		foreach ( $list as $item )
			$this->lst[] = $creator->create(property_exists($item, 'className') ? $item->className : $item->name, $item->name, property_exists($item, 'params')?$item->params:(object)[]);

		
		// search for strategy with name $active
		foreach ( $this->lst as $msp )
			if ( $msp->name == $active )
			{
				$this->active = $msp;
				break;
			}
	}
	
	

	/**
	 * Get a list of mail sender strategies proxy
	 * 
	 * @return \Nettools\Mailing\MailSendersFacade\Proxies\Proxy[]
	 */	 
	public function getList()
	{
		return $this->lst;
	}
	
	
	
	/**
	 * Get active mailsender strategy proxy
	 *
	 * @return \Nettools\Mailing\MailSendersFacade\Proxies\Proxy
	 */
	public function getActive()
	{
		return $this->active;
	}
}


?>