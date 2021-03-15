<?php
/**
 * EmailValidator
 *
 * @author Pierre - dev@nettools.ovh
 * @license MIT
 */


// namespace
namespace Nettools\Mailing\MailCheckers;




/** 
 * Class to handle mail existence check with Email-Validator.net
 */
class EmailValidator extends Checker
{
	const URL = 'https://api.email-validator.net/api/verify';
	
	
	
	/**
	 * Check that a given email exists
	 * 
	 * @param string $email
	 * @return bool Returns true if the email can be delivered, false otherwise
	 * @throws \Nettools\Mailing\MailCheckers\Exception Thrown if API does not return a valid response
	 */
	function check($email)
	{
		// https://api.email-validator.net/api/verify?EmailAddress=support@byteplant.com&APIKey=your API ke
		
		// request
		$response = $this->http->request('GET', self::URL, 
						 	[ 
								'query' 	=> ['EmailAddress' => $email, 'APIKey' => $this->apikey, 'Timeout' => 5]
							]);
		
		// http status code
		if ( $response->getStatusCode() != 200 )
			throw new Exception("HTTP error " . $response->getStatusCode() . ' ' . $response->getReasonPhrase() . " when checking email");

		/*
		{
		  "status":200,"ratelimit_remain":99,"ratelimit_seconds":299,"info":"OK - Valid Address","details":"The mail address is valid.","freemail":true
		}
		*/
		
		// read response
		if ( $json = (string)($response->getBody()) )
			if ( $json = json_decode($json) )
				if ( property_exists($json, 'status') )
					return ($json->status == 200);
		
		throw new Exception("API error for email '$email' in " . __CLASS__ );
	}
}
?>