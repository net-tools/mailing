<?php

// namespace
namespace Nettools\Mailing;


use \Nettools\Mailing\MailPieces\MailTextPlainContent;
use \Nettools\Mailing\MailPieces\MailTextHtmlContent;
use \Nettools\Mailing\MailPieces\MailMultipart;
use \Nettools\Mailing\MailPieces\MailContent;
use \Nettools\Mailing\MailPieces\MailAttachment;
use \Nettools\Mailing\MailPieces\MailEmbedding;




// class to parse an EML file et get a MailContent object
class EmlReader
{
	// last error encountered
	static public $lastError = NULL;
	
	
	// set error message
	static protected function _error($msg)
	{
		self::$lastError = $msg;
		return NULL;
	}
	
	
	// clear temp files used for embeddings and attachments
	static function destroy(MailContent $mail)
	{
		// traiter par récursion
		if ( $mail instanceof \Nettools\Mailing\MailPieces\MailMultipart )
		{
			$partsl = $mail->getCount();
			for ( $i = 0 ; $i < $partsl ; $i++ )
				self::destroy($mail->getPart($i));
		}
		
		
		// if cleaning required
		if ( in_array(get_class($mail), array('Nettools\Mailing\MailPieces\MailAttachment', 'Nettools\Mailing\MailPieces\MailEmbedding')) )
		{
			$f = $mail->getFile();
			if ( file_exists($f) )
				unlink($f);
		}
	}
	
	
	// decode a header ; if VALUE is null, we return the first value (the value before ';') ; if value is a string we return the parameter named $value
    // for example : (text/plain; charset="UTF-8"; format=flowed), we have the first value (text/plain) and two parameters (charset and format)
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
	
	
	// decode body with content-transfer-encoding (usually, quoted printable or base64)
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
		
		return self::_error("Content-Transfer-Encoding '$encoding' not supported");
	}
	
	
	// decode charset
	static function decodeCharset($body, $charset)
	{
		if ( !$charset ) 
			return $body;

		// convert to utf8
		if ( strtolower($charset) != 'utf-8' ) 		
		{	
			$s = iconv(strtolower($charset), 'utf-8', $body);
			if ( $s === FALSE )
				return self::_error("Decoding from charset '$charset' to UTF-8 error.");
			else
				return $s;
		}
		else
			return $body;
	}
	
	
	// decode body content according to it's content-type
	static function decodeCharsetFromContentTypeHeader($body, $ct)
	{
		// get charset from content-type header
		$charset = self::decodeHeader($ct, 'charset');				

		// decode
		return self::decodeCharset($body, $charset);
	}
	
	
	// decode part content and we get a MailTextPlainContent, MailHtmlPlainContent, MailAttachment or MailEmbedding (choice based on the content-disposition)
	static function decodeContent($body, $headers, $contentType)
	{
		// get content-disposition header
		$contentDisposition = self::decodeHeader($headers['Content-Disposition']);
		
		// if content-id, force content-disposition to 'inline' (case when content-disposition:inline header missing)
		if ( self::decodeHeader($headers['Content-ID']) )
			$contentDisposition = 'inline';
			
				
		// if no content-disposition, we don't have an attachment/embdedding, but we have a text/plain or text/html content
		if ( !$contentDisposition )
		{
			switch ( $contentType )
			{
				case 'text/plain' :
					return new MailTextPlainContent($body);
				case 'text/html' :
					return new MailTextHtmlContent($body);
			}
			
			return self::_error("Content-type '$contentType' not supported.");
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
					return new MailAttachment($fname, basename($fname), $contentType, true);
				else
				{
					// if embedding, extract content-ID
					$cid = self::decodeHeader($headers['Content-ID']);
					if ( !$cid )
						return self::_error('Content-ID not found.');
						
					return new MailEmbedding($fname, $contentType, trim(str_replace(array('<', '>', '"'), '', $cid)), true);
				}
			}
			else
				return self::_error("Body with Content-Disposition '$contentDisposition' not supported.");
	}
	
	
	// decode email from it's top level content-type
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
				if (!$decodedBody )
					return NULL;
					
				// maybe we need to decode the charset to utf-8
				$decodedBody = self::decodeCharsetFromContentTypeHeader($decodedBody, $ct);
				if (!$decodedBody )
					return NULL;

				// get the MailContent object with extracted/converted body, headers and contenttype
				return self::decodeContent($decodedBody, $headers, $contentType);


			case 'multipart/alternative' : 
			case 'multipart/mixed' : 
			case 'multipart/related' : 
				
				// if multipart, we must read the boundary parameter
				$boundary = self::decodeHeader($ct, 'boundary');
				if ( !$boundary )
					return self::_error("Error when extracting boundary from '$contentType'.");
				
				
                // decoding the multipart ; when splitting, we get 3 values (even if the multipart has 2 parts, a content and a attachment, for example), because
                // the body of the multipart begin with the boundary (=separator for splitting). We ignore this empty value.
                // we know that the last boundary separator ends with '--', and that after each separator line, there's a carriage return. We delete only this
                // newline (except if last separator ending with --). We know that the newline can be \n or \r\n
				$parts = preg_split("/--${boundary}(--)?[\\r]?[\\n]?/", $body);
				if ( count($parts) < 3 )
					return self::_error("Decoding of '$contentType' is impossible because of the unsupported parts number (1).");

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
						
					// decode this part ; detect an error and break process if an error occured
					$partObject = EmlReader::fromString($part);
					if ( !$partObject )
						return NULL;
						
					$parts[$k] = $partObject;
				}

				if ( count($parts) < 2 )
					return self::_error("Decoding of '$contentType' is impossible because of the unsupported parts number (2).");
					
				return MailMultipart::fromSingleArray(substr(strstr($contentType, '/'), 1), $parts);
					
					
			// default case, decode with the transfer-encoding
			default:
				$decodedBody = self::decodeBody($body, self::decodeHeader($headers['Content-Transfer-Encoding']));
				if ( !$decodedBody )
					return NULL;
					
				// decoding content
				return self::decodeContent($decodedBody, $headers, $contentType);
		}
	}
	
	
	// get headers to a string, and set the linefeed (parameter by reference), by detecting linefeed characters in headers
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
		
		// returing only headers, breaking on the two newlines separation between headers and content
		return strstr($eml, $sep, true);
	}
	
	
	// parse a string
	static function fromString($data)
	{
		// decode headers and linefeed
		$linefeed = NULL;
		$headers = self::getHeaders($data, $linefeed);
		$body = substr($data, strlen($headers . $linefeed . $linefeed));
		$headers = trim($headers);
		if ( !$headers )
			return self::_error('Headers cannot be extracted.');
		
		// convert headers string to array
		$headers = Mailer::headersToArray($headers);

		
		// handle content according to it's content-type
		if ( !$headers['Content-Type'] )
			return self::_error('Header \'Content-Type\' missing.');
	
		return self::fromContentType($headers['Content-Type'], $headers, $body);
	}
	
	
	// parse from a file
	static function fromFile($file)
	{
		if ( file_exists($file) )
			return self::fromString(file_get_contents($file));
		else
			return self::_error('File not found.');
	}
}

?>