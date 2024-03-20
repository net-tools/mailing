<?php
/**
 * MailReaderEngine
 *
 * @author Pierre - dev@nettools.ovh
 * @license MIT
 */



// namespace
namespace Nettools\Mailing;






/**
 * Class to parse an email content 
 */
class MailReaderEngine
{
	/**
	 * Clear temp files used for embeddings and attachments
	 * 
	 * @param MailPieces\MailContent $mail Mail object to process
	 */
	static function clean(\Nettools\Mailing\MailPieces\MailContent $mail)
	{
		// traiter par récursion
		if ( $mail instanceof \Nettools\Mailing\MailPieces\MailMultipart )
		{
			$partsl = $mail->getCount();
			for ( $i = 0 ; $i < $partsl ; $i++ )
				self::clean($mail->getPart($i));
		}
		
		
		// if cleaning required
		if ( in_array(get_class($mail), array('Nettools\Mailing\MailPieces\MailAttachment', 'Nettools\Mailing\MailPieces\MailEmbedding')) )
		{
			$f = $mail->getFile();
			if ( file_exists($f) )
				unlink($f);
		}
	}
	
	
	
	/**
	 * Decode a header
	 * 
	 * If `$value` is null, we return the first value (the value before ';') ; if `$value` is a string we return the 
	 * parameter named according to it's value. 
	 * For example, in : 
	 *
	 *    text/plain; charset="UTF-8"; format=flowed
	 *
	 * we have the first value (text/plain) and two parameters (charset and format)
	 *
	 * @param string $header Header to parse
	 * @param string|NULL $value Parameter to return ; to get the full header value, pass NULL as value
	 * @return string Returns the header value, or the value for the parameter named as `$value` content
	 */
	static function decodeHeader($header, $value = NULL)
	{
		if ( !$header )
			return NULL;
			
		// split header parts (e.g. text/plain; charset="UTF-8"; format=flowed)
		$regs = preg_split('/;[\s]+/', $header);
		
		// check splitting ok
		if ( count($regs) == 0 )
			return NULL;
			
		// if we want the first part
		if ( is_null($value) )
			return trim($regs[0]);
		
		// if we want a parameter
		foreach ( $regs as $part )
		{
			// if found, returning the parameter value, with no enclosing quotes
			$p = strpos($part, $value . '=');
			if ( $p === 0 )
				return str_replace('"', '', substr(strstr($part, '='),1));
		}
		
		return NULL;
	}
	
	
	
	/**
	* Decode body with content-transfer-encoding (usually, quoted printable or base64)
	* 
	* @param string $body Content to decode
	* @param string $encoding Content-Transfer-Encoding for the `$body`
	* @return string Body decoded 
	* @throws MailReaderError
	*/
	static function decodeBody($body, $encoding)
	{
		// if encoding not specified, do nothing
		if ( !$encoding )
			return $body;
			
		switch ( $encoding )
		{
            // if 7bit or 8bit encoding, do nothing ; this is just to tell the SMTP server how to handle the charset
			case '7bit':
			case '8bit':
				return $body;
			
			case 'quoted-printable':
				return quoted_printable_decode($body);
				break;
				
			case 'base64':
				return base64_decode(/*str_replace('_', '/', str_replace('-', '+', */$body);
		}
		
		
		throw new MailReaderError("Content-Transfer-Encoding '$encoding' not supported");
	}
	
	
	
	/** 
	 * Decode body according to a charset (may be read from Content-Type header)
	 * 
	 * @param string $body Body string to decode
	 * @param string $charset Charset to use to decode
	 * @return string Body decoded
     * @throws MailReaderError
	 */
	static function decodeCharset($body, $charset)
	{
		if ( !$charset ) 
			return $body;
		

		// convert to utf8
		if ( strtolower($charset) != 'utf-8' ) 		
		{	
			$s = iconv(strtolower($charset), 'utf-8', $body);
			if ( $s === FALSE )
				throw new MailReaderError("Decoding from charset '$charset' to UTF-8 error.");
			else
				return $s;
		}
		else
			return $body;
	}
	
	
	
	/** 
	 * Decode body content according to it's content-type to UTF-8
	 *
	 * @param string $body Body to decode
	 * @param string $ct Content-Type header from which the charset will be extracted and use for decoding
	 * @return string The body with charset decoded to UTF-8 
	 * @throws MailReaderError
	 */
	static function decodeCharsetFromContentTypeHeader($body, $ct)
	{
		// get charset from content-type header
		$charset = self::decodeHeader($ct, 'charset');				

		// decode
		return self::decodeCharset($body, $charset);
	}
	
	
	
	/** 
	 * Decode part content 
	 * 
	 * We get some MailPieces\MailContent classes, depending on Content-Type and Content-disposition headers :
	 * 
	 * - MailPieces\MailTextPlainContent
	 * - MailPieces\MailHtmlPlainContent
	 * - MailPieces\MailAttachment
	 * - MailPieces\MailEmbedding
	 *
	 * @param string $body Body to decode
	 * @param string[] $headers Headers array
	 * @param string $contentType Content-Type header value
	 * @return MailPieces\MailContent Returns the part decoded to a MailContent instance 
	 * @throws MailReaderError	 
	 */
	static function decodeContent($body, $headers, $contentType)
	{
		// get content-disposition header
		if ( array_key_exists('Content-Disposition', $headers) )
			$contentDisposition = self::decodeHeader($headers['Content-Disposition']);
		else
			$contentDisposition = null;
		
		
		// if content-id, force content-disposition to 'inline' (case when content-disposition:inline header missing)
		if ( array_key_exists('Content-ID', $headers) && self::decodeHeader($headers['Content-ID']) )
			$contentDisposition = 'inline';
			
				
		// if no content-disposition, we don't have an attachment/embdedding, but we have a text/plain or text/html content
		if ( !$contentDisposition )
		{
			switch ( $contentType )
			{
				case 'text/plain' :
					return new \Nettools\Mailing\MailPieces\MailTextPlainContent($body);
				case 'text/html' :
					return new \Nettools\Mailing\MailPieces\MailTextHtmlContent($body);
			}
			
			
			throw new MailReaderError("Content-type '$contentType' not supported.");
		}
		else
			// if attachment or embedding
			if ( in_array($contentDisposition, ['attachment', 'inline']) )
			{
				// create a temp file and write the attachment/embedding
				$fname = tempnam(/*$_SERVER['DOCUMENT_ROOT']*/sys_get_temp_dir(), $contentDisposition);
				$f = fopen($fname, 'w');
				fwrite($f, $body);
				fclose($f);
				
				if ( $contentDisposition == 'attachment' )
					return new \Nettools\Mailing\MailPieces\MailAttachment($fname, basename($fname), $contentType, true);
				else
				{
					// if embedding, extract content-ID
					$cid = self::decodeHeader($headers['Content-ID']);
					if ( !$cid )
						throw new MailReaderError('Content-ID not found.');
						
					return new \Nettools\Mailing\MailPieces\MailEmbedding($fname, $contentType, trim(str_replace(array('<', '>', '"'), '', $cid)), true);
				}
			}
			else
				throw new MailReaderError("Body with Content-Disposition '$contentDisposition' not supported.");
	}
	
	
	
	/**
	 * Decode email from it's top level content-type
	 * 
	 * @param string $ct Content-Type header value
	 * @param string[] $headers Headers array
	 * @param string $body Body to decode
	 * @return MailPieces\MailContent
  	 * @throws MailReaderError
	 */
	static function fromContentType($ct, $headers, $body)
	{
		// get content-type (text/plain, text/html, multipart/*, etc.)
		$contentType = self::decodeHeader($ct);
		
		
		// handle recursively depending on content-type
		switch ( $contentType )
		{
			case 'text/plain' : 
			case 'text/html' : 
				// decode body text content depending on its transfer-encoding header
				$decodedBody = self::decodeBody($body, self::decodeHeader($headers['Content-Transfer-Encoding']));
/*				if (!$decodedBody )
					return NULL;*/
					
				// maybe we need to decode the charset to utf-8
				$decodedBody = self::decodeCharsetFromContentTypeHeader($decodedBody, $ct);
/*				if (!$decodedBody )
					return NULL;*/

				// get the MailContent object with extracted/converted body, headers and contenttype
				return self::decodeContent($decodedBody, $headers, $contentType);


			case 'multipart/alternative' : 
			case 'multipart/mixed' : 
			case 'multipart/related' : 
				
				// if multipart, we must read the boundary parameter
				$boundary = self::decodeHeader($ct, 'boundary');
				if ( !$boundary )
					throw new MailReaderError("Error when extracting boundary from '$contentType'.");
				
				
                // decoding the multipart ; when splitting, we get 3 values (even if the multipart has 2 parts, a content and a attachment, for example), because
                // the body of the multipart begin with the boundary (=separator for splitting). We ignore this empty value.
                // we know that the last boundary separator ends with '--', and that after each separator line, there's a carriage return. We delete only this
                // newline (except if last separator ending with --). We know that the newline can be \n or \r\n
				$parts = preg_split("/--{$boundary}(--)?[\\r]?[\\n]?/", $body);
				if ( count($parts) < 3 )
					throw new MailReaderError("Decoding of '$contentType' is impossible because of the unsupported parts number (1).");

				// skip first empty
				$parts = array_slice($parts, 1);
				
				// for all parts (may be 2 or more, for example if we have several attachments)
				foreach ( $parts as $k=>$part )
				{
					// if part empty, we are done (we are dealing with the empty line after last separator)
					if ( trim($part) == '' )
					{
						unset($parts[$k]);
						break;
					}
						
					// decode this part
					$partObject = MailReader::fromString($part)->email;
					/*if ( !$partObject )
						return NULL;*/
						
					$parts[$k] = $partObject;
				}

				if ( count($parts) < 2 )
					throw new MailReaderError("Decoding of '$contentType' is impossible because of the unsupported parts number (2).");
					
				return \Nettools\Mailing\MailPieces\MailMultipart::fromSingleArray(substr(strstr($contentType, '/'), 1), $parts);
					
					
			// default case, decode with the transfer-encoding
			default:
				$decodedBody = self::decodeBody($body, self::decodeHeader($headers['Content-Transfer-Encoding']));
				/*if ( !$decodedBody )
					return NULL;*/
					
				// decoding content
				return self::decodeContent($decodedBody, $headers, $contentType);
		}
	}
	
	
	
	/**
	* Extract headers to a string, and set the linefeed (parameter by reference), by detecting linefeed characters in headers
	*
	* @param string $eml Email raw content
	* @param string $linefeed Will be set with the linefeed character detected
	* @return string Headers string extracted
	*/
	static function getHeaders($eml, &$linefeed)
	{
		// get headers lines ; 2 consecutive newlines separate the headers from the mail content. Detect type of newline
        // we try to detect the presence of \r\n and \n (both types mixed), but we take the first matching
		$p_crlf = strpos($eml, "\r\n\r\n");
		$p_lf = strpos($eml, "\n\n");
		
		
		// simple cases : one of the newlines characters found
		if ( $p_crlf === FALSE )
			$linefeed = "\n";
		else
		if ( $p_lf === FALSE )
			$linefeed = "\r\n";
		
		// case where the two newlines characters have been found, we take the first one which occur in the email
		else
			$linefeed = ($p_crlf < $p_lf ) ? "\r\n" : "\n";
			
		
		$sep = $linefeed . $linefeed;
		
		// returning only headers, breaking on the two newlines separation between headers and content
		return strstr($eml, $sep, true);
	}
	
	
	
	/** 
	 * Decode header data that may have been encoded with mb_encode_mimeheader (such as `to`, `from`, `subject` etc.)
	 *
	 * @param string[] $headers
	 * @return string[]
	 */
	static function decodeHeaders(array $headers)
	{
		$ret = [];
		
		foreach ( $headers as $k=>$h )
			$ret[$k] = mb_decode_mimeheader($h);
		
		return $ret;
	}
	
	
	
	/**
	* Decode email from a string
	* 
	* @param string $data Email string to decode
	* @return object Returns an object litteral with properties `email` and `headers` (of type MailPieces\MailContent and string[])
	* @throws MailReaderError
	*/
	static function fromString($data)
	{
		// decode headers and linefeed
		$linefeed = NULL;
		$headers = self::getHeaders($data, $linefeed);
		$body = substr($data, strlen($headers . $linefeed . $linefeed));
		$headers = trim($headers);
		if ( !$headers )
			throw new MailReaderError('Headers cannot be extracted.');
		
		// convert headers string to array
		$headers = Mailer::headersToArray($headers);

		
		// handle content according to it's content-type
		if ( !array_key_exists('Content-Type', $headers) )
			throw new MailReaderError('Header \'Content-Type\' missing.');
	
		
		return (object)[ 'email' => self::fromContentType($headers['Content-Type'], $headers, $body), 'headers' => $headers ];
	}	
}

?>