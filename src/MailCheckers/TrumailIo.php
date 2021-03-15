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
 * Class to handle mail existence check with trumail.io
 */
class TrumailIo extends Checker
{
	const URL = 'https://api.trumail.io/v2/lookups/json';
	
	
	
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
								'query' => ['email' => $email]
							]);
		
		// http status code
		if ( $response->getStatusCode() != 200 )
			throw new Exception("HTTP error " . $response->getStatusCode() . ' ' . $response->getReasonPhrase() . " when checking email");

		// read response
		if ( $json = (string)($response->getBody()) )
			if ( $json = json_decode($json) )
				return $json->deliverable;		
		
		throw new Exception("API error for email '$email' in " . __CLASS__ );
	}
}
?>