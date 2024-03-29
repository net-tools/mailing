<?php
/**
 * MailReader
 *
 * @author Pierre - dev@nettools.ovh
 * @license MIT
 */



// namespace
namespace Nettools\Mailing;


use \Nettools\Mailing\MailParts\Content;
use \Nettools\Mailing\MailerEngine\Headers;





/**
 * Class to parse an email content and get a Content object along with top-level headers
 */
class MailReader
{
	public $email = null;
	public $headers = null;
	
	
	
	/**
	 * Constructor
	 *
	 * @param MailParts\Content $mail
	 * @param string[] $headers
	 */
	function __construct(Content $email, Headers $headers)
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
		if ( file_exists($file) )
			return self::fromString(file_get_contents($file));
		else
			throw new MailReaderError("File '$file' not found.");
	}
}

?>