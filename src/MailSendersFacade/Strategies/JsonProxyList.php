<?php
/**
 * JsonProxyList
 *
 * @author Pierre - dev@nettools.ovh
 * @license MIT
 */



// namespace
namespace Nettools\Mailing\MailSendersFacade\Strategies;



/**
 * Listing all email sending strategies (list in `$list` string, parameters for all strategies in a json-encoded string)
 */
class JsonProxyList extends ProxyList {

	/**
	 * Constructor
	 *
	 * @param string $list List of mailsenders strategies (format is SMTP:aws;PHPMail;SMTP:gmail;...)
	 * @param string $json Json-formatted string describing the `$list` items : {"SMTP:aws":{"className":"SMTP","key1":"value1","k2":"value2"}, "PHPMail":{"className":"PHPMail"}}
	 * @param string $active Name of active mailsender strategy (ex. 'SMTP:aws')
	 */
	public function __construct($list, $json, $active)
	{
		if ( $list != '' )
			$list = explode(';', $list);
		else 
			$list = [];
		
		
		// decode $jsonparams structure
		$json = json_decode($json);
		if ( is_null($json) )
			$json = (object)[];
		
		
		// preparing list before calling inherited constructor
		$lst = [];		
	
		// for all names, look for the proxy definition json string in the `$json` parameter
		foreach ( $list as $name )
		{
			// if no parameters for strategy, name=className, params = []
			if ( !property_exists($json, $name) )
				$className = $name;
			else
				// if className key found, use it to grab the class name info, or use the name value instead
				if ( property_exists($json->$name, 'className') )
					$className = $json->$name->className;
				else
					$className = $name;
			
			
			$lst[] = (object)[
					'className'	=> $classname,
					'name'		=> $name,
					'params'	=> $json->$name
				];
		}
		
		
		// calling inherited constructor
		parent::__construct($lst, $active);
	}
	
}


?>