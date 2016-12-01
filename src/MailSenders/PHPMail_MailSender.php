<?php

// namespace
namespace Nettools\Mailing\MailSenders;

// clauses use
use \Nettools\Mailing\MailSender;



// stratégie pour envoi par PHP mail()
class PHPMail_MailSender extends MailSender
{
	// analyser les en-têtes, les modifier éventuellement ; contrairement aux autres stratégies d'envois, on ne rajoute pas To et Subject, 
	// puisque c'est phpmail qui s'en charge
	function handleHeaders_ToSubject($to, $subject, $mail, &$headers)
	{
	}
	

	// on ne traite pas Bcc, contrairement aux autres stratégies d'envois, car c'est phpmail qui s'en charge
	function handleBcc($to, $subject, $mail, &$headers)
	{
	}
	
	
	// envoyer un mail
	function doSend($to, $subject, $mail, $headers)
	{
		if ( mail($to, $subject, $mail, $headers) )
			return FALSE;
		else
			return "Erreur inconnue PHP:mail()";
	}
}
?>