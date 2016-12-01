<?php

// namespace
namespace Nettools\Mailing\MailSenders;

// clauses use
use \Nettools\Mailing\MailSender;



// stratégie pour envoi du mail sur fichier temporaire dans un dossier spécifique PARAMS['path']
class EmlFile_MailSender extends MailSender
{
	// [----- MEMBRES PROTEGES -----
	
	protected $_emlSent = array();
	
	// ----- MEMBRES PROTEGES -----]
	
	
	const PATH = 'path';
	
	
	// envoyer un mail
	function doSend($to, $subject, $mail, $headers)
	{
		if ( $this->params[self::PATH] )
		{
			// ajouter slash terminal
			$path = $this->params[self::PATH];
			if ( substr($path, -1) != '/' )
				$path = $path . '/';
			
			
			// créer le fichier temporaire en le nommant avec le nom du destinataire (@ interdit, remplacé par "AT")
			$fname = $this->params[self::PATH] . str_replace('@', '_AT_', $to) . ".eml";
			$f = fopen($fname, 'w');
			fputs($f, $headers);
			fputs($f, "\r\n");
			fputs($f, "Delivered-To: $to");
			fputs($f, "\r\n\r\n");
			fputs($f, $mail);
			fclose($f);
			
			// ajouter ce fichier dans la liste des envois
			$this->_emlSent[] = $fname;
			
			return FALSE; // ok
		}
		else
			return "Dossier temporaire inaccessible '" . $this->params[self::PATH] . "'";
	}
	
	
	// obtenir liste des fichiers EML
	function getEmlFiles()
	{
		return $this->_emlSent;
	}
}




?>