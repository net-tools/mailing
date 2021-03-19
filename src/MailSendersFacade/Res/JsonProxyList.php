<?php
/**
 * JsonProxyList
 *
 * @author Pierre - dev@nettools.ovh
 * @license MIT
 */



// namespace
namespace Nettools\Mailing\MailSendersFacade\Res;



use \Nettools\Mailing\MailSendersFacade\Factories\ProxyCreator;




/**
 * Listing all email sending strategies (list in `$list` string, parameters for all strategies in a json-encoded string)
 */
class JsonProxyList extends ProxyList {

	/**
	 * Constructor
	 *
	 * @param string[] $list List of mailsenders strategies as a string array ["SMTP:aws", "PHPMail", "SMTP:gmail"]
	 * @param string $json Json-formatted string describing the `$list` items : {"SMTP:aws":{"className":"SMTP","key1":"value1","k2":"value2"}, "PHPMail":{"className":"PHPMail"}}
	 * @param string $active Name of active mailsender strategy (ex. 'SMTP:aws')
	 * @param \Nettools\Mailing\MailSendersFacade\Factories\ProxyCreator $creator Strategy used to create a MailSenderProxy of suitable class
	 */
	public function __construct(array $list, $json, $active, ProxyCreator $creator)
	{
		// decode $jsonparams structure
		$json = json_decode($json);
		if ( is_null($json) )
			$json = (object)[];
		
		
		// preparing list before calling inherited constructor
		$lst = [];		
	
		// for all names, look for the proxy definition json string in the `$json` parameter
		foreach ( $list as $name )
		{
			$item = (object)[
					'name'		=> $name,
					'params'	=> property_exists($json, $name) ? $json->$name : (object)[]
				];

			
			// looking for className key
			if ( property_exists($json, $name) && property_exists($json->$name, 'className') )
				$item->className = $json->$name->className;
			
			$lst[] = $item;
		}
		
		
		// calling inherited constructor
		parent::__construct($lst, $active, $creator);
	}
	
}


?>