<?php
/**
 * MailReader
 *
 * @author Pierre - dev@nettools.ovh
 * @license MIT
 */



// namespace
namespace Nettools\Mailing;


use \Nettools\Mailing\MailPieces\MailContent;





/**
 * Class to parse an email content and get a MailContent object along with top-level headers
 */
class MailReader
{
	protected $email = null;
	protected $headers = [];
	
	
	
	/**
	 * Constructor
	 *
	 * @param MailPieces\MailContent $mail
	 * @param string[] $headers
	 */
	function __construct(MailContent $mail, array $headers)
	{
		$this->email = $email;
		$this->headers = $headers;
	}
	
	
	
	/**
	 * Clear temp files used for embeddings and attachments
	 */
	function clean()
	{
		MailReaderEngine::clean($this->email);
	}
	
	
	
	/**
	* Decode email from a string
	* 
	* @param string $data Email string to decode
	* @return MailReader
	* @throws MailReaderError
	*/
	static function fromString($data)
	{
		$o = MailReaderEngine::fromString($data);
		return new MailReader($o->email, MailReaderEngine::decodeHeaders($o->headers));
	}
	
	
	
	/**
	* Decode email from a file
	* 
	* @param string $file Path to email to read
	* @return MailReader
	* @throws MailReaderError
	*/
	static function fromFile($file)
	{
		return self::fromString($file);
	}
}

?>