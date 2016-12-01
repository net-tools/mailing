<?php

// namespace
namespace Nettools\Mailing\MailSenders;

// clauses use
use \Nettools\Mailing\MailSender;



// stratégie pour envoi du mail sur fichier temporaire dans un dossier spécifique PARAMS['path']
class Virtual_MailSender extends MailSender
{
	// [----- MEMBRES PROTEGES -----
	
	protected $_sent = array();
	
	// ----- MEMBRES PROTEGES -----]
	
	
	// envoyer un mail
	function doSend($to, $subject, $mail, $headers)
	{
		$m = $headers;
		$m .= "\r\n";
		$m .= "Delivered-To: $to";
		$m .= "\r\n\r\n";
		$m .= $mail;
			
		// ajouter cet envoi
		$this->_sent[] = $m;
		return FALSE; // ok
	}
	
	
	// nettoyer
	function destruct()
	{
		$this->_sent = array();
	}
	
	
	// obtenir liste des envois
	function getSent()
	{
		return $this->_sent;
	}
}




?>