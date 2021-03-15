<?php
/**
 * Checker
 *
 * @author Pierre - dev@nettools.ovh
 * @license MIT
 */


// namespace
namespace Nettools\Mailing\MailCheckers;




/** 
 * Class to handle mail existence check with Bouncer
 */
class Bouncer extends Checker
{
	const URL = 'https://api.usebouncer.com/v1/email/verify';
	
	
	
	/**
	 * Check that a given email exists
	 * 
	 * @param string $email
	 * @return bool Returns true if the email can be delivered, false otherwise
	 * @throws \Nettools\Mailing\MailCheckers\Exception Thrown if trumail.io API does not return a valid response
	 */
	function check ($email)
	{
		// request
		$response = $this->http->request('GET', self::URL, 
						 	[ 
								'query' 	=> ['email' => $email],
								'headers'	=> ['x-api-key' => $this->api_key]
							]);
		
		// http status code
		if ( $response->getStatusCode() != 200 )
			throw new Exception("HTTP error " . $response->getStatusCode() . ' ' . $response->getReasonPhrase() . " when checking email");

		/*
		{
		  "email": "john@usebouncer.com",
		  "status": "deliverable/undeliverable",
		  "reason": "accepted_email/rejected_email",
		  "domain": {
			"name": "usebouncer.com",
			"acceptAll": "no",
			"disposable": "no",
			"free": "no"
		  },
		  "account": {
			"role": "no",
			"disabled": "no",
			"fullMailbox": "no"
		  }
		}
		*/
		
		// read response
		if ( $json = (string)($response->getBody()) )
			if ( $json = json_decode($json) )
				if ( property_exists($json, 'status') )
					return ($json->status == 'deliverable');
		
		throw new Exception("API error for email '$email' in " . __CLASS__ );
	}
}
?>