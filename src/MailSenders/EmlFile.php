<?php
/**
 * EmlFile
 *
 * @author Pierre - dev@nettools.ovh
 * @license MIT
 */




// namespace
namespace Nettools\Mailing\MailSenders;





/** 
 * Strategy to output the email in a folder (EML file)
 * 
 * We expect that the constructor receive a 'path' option containing a path to the working storage folder for sent emails.
 */
class EmlFile extends MailSender
{
	// [----- PROTECTED -----
	
    /** @var string[] Array of path to emails sent (path to storage folder) */
	protected $_emlSent = array();
	
	// ----- PROTECTED -----]
	
	
    /** @var string Constant for parameter name for the path to the storage folder */
	const PATH = 'path';
	
	
	/**
	 * send an email
	 *
     * @param string $to Recipient
     * @param string $subject Subject ; must be encoded if necessary
     * @param string $mail String containing the email data
     * @param string $headers Email headers
	 * @throws \Nettools\Mailing\Exception
	 */
	function doSend($to, $subject, $mail, $headers)
	{
		if ( $this->params[self::PATH] )
		{
			// add slash
			$path = $this->params[self::PATH];
			if ( substr($path, -1) != '/' )
				$path = $path . '/';
			
			
			// create temp file named with the recipient, @ replaced by '_AT_'
			$fname = $this->params[self::PATH] . str_replace('@', '_AT_', $to) . ".eml";
			$f = fopen($fname, 'w');
			fputs($f, $headers);
			fputs($f, "\r\n");
			fputs($f, "Delivered-To: $to");
			fputs($f, "\r\n\r\n");
			fputs($f, $mail);
			fclose($f);
			
			$this->_emlSent[] = $fname;
		}
		else
			throw new \Nettools\Mailing\Exception("Folder not available : '" . $this->params[self::PATH] . "'");
	}
	
	
	/**
     * Get a list of emails sent during this session
     *
     * @return string[] Array of file paths
     */
	function getEmlFiles()
	{
		return $this->_emlSent;
	}
}




?>