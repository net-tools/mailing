<?php

// namespace
namespace Nettools\Mailing\MailSenders;


use \Nettools\Mailing\MailSender;



// strategy to output the email in a folder (EML file)
class EmlFile_MailSender extends MailSender
{
	// [----- PROTECTED -----
	
	protected $_emlSent = array();
	
	// ----- PROTECTED -----]
	
	
	const PATH = 'path';
	
	
	// send an email
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
			
			return FALSE; // ok
		}
		else
			return "Folder not available : '" . $this->params[self::PATH] . "'";
	}
	
	
	// get a list of emails sent during this session
	function getEmlFiles()
	{
		return $this->_emlSent;
	}
}




?>