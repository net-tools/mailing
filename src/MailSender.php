<?php

// namespace
namespace Nettools\Mailing;

// clauses use
use \Nettools\Mailing\Mailer;



// stratégie de base pour envoi des mails : PHP ou SMTP
// paramètres : callback_log pour consigner envoi OK
abstract class MailSender{

	// [----- DECL. STATIQUES -----
	
	const EMLFILE = "EmlFile_MailSender";
	const PHPMAIL = "PHPMail_MailSender";
	const SMTP = "SMTP_MailSender";
	const VIRTUAL = "Virtual_MailSender";
	
	const CALLBACK_LOG = "callback_log";
	const CALLBACK_LOG_DATA = "callback_log_data";

	
	// instancier une classe fille pour implémenter la stratégie
	static function factory($concreteSenderName, $params = NULL)
	{
		$cname = "\\Nettools\\Mailing\\MailSenders\\$concreteSenderName";
		return new $cname($params);
	}

	// ----- DECL. STATIQUES -----]


	// [----- DECL. PROTEGEES -----
	
	protected $params = NULL;
	
	
	// consigner un envoi de mail
	protected function _acknowledgeMailSent()
	{
		if ( $this->params[self::CALLBACK_LOG] )
			call_user_func_array($this->params[self::CALLBACK_LOG], $this->params[self::CALLBACK_LOG_DATA]);
	}

	// ----- DECL. PROTEGEES -----]
	
	// constructeur
	function __construct($params = NULL)
	{
		if ( is_null($params) )
			$params = array();
			
		$this->params = $params;
	}
	

	// envoyer un mail ; renvoie FALSE si OK, chaine si KO (avec message)
	abstract function doSend($to, $subject, $mail, $headers);
	

	// traiter le cas du BCC
	function handleBcc($to, $subject, $mail, &$headers)
	{
		// si BCC, traiter spécifiquement, car le protocole SMTP ne prend pas en charge BCC; il s'agit d'envoyer autant
		// de mails que nécessaires (TO valant un destinataire différent à chaque fois), en omettant BCC dans les en-tete
		// (sinon le destinataire verrait cet en-tete) ; quand on appelle php:mail(), mail() traite ces cas-là
		if ( $bcc = Mailer::getHeader($headers, 'Bcc') )
		{
			// supprimer l'en-tete BCC, qui n'est pas exploité par SMTP
			$headers = Mailer::removeHeader($headers, 'Bcc');
			
			// traiter tous les Bcc
			$bcc_to = explode(',', $bcc);
			foreach ( $bcc_to as $bcc )
				// envoyer avec BCC comme destinataire ; headers est privé de son champ BCC
				$this->doSend(trim($bcc), $subject, $mail, $headers);
		}
		
		return FALSE;
	}

	
	// préparer l'envoi et traiter les cas particuliers (comme BCC)
	function handleSend($to, $subject, $mail, $headers)
	{
		// traiter le BCC ; headers peut-être modifié au retour pour enlever le header Bcc:
		$this->handleBcc($to, $subject, $mail, $headers);
		
		// traiter normalement
		return $this->doSend($to, $subject, $mail, $headers);
	}
	
	
	// gérer le rajout de TO et SUBJECT pour les stratégies autres que PHPMail ; HEADERS modifié au retour
	function handleHeaders_ToSubject($to, $subject, $mail, &$headers)
	{
		$headers = Mailer::addHeader($headers, "To: $to");
		$headers = Mailer::addHeader($headers, "Subject: $subject");
	}
	
	
	// gérer les en-têtes priorité
	function handleHeaders_Priority($to, $subject, $mail, &$headers)
	{
		$headers = Mailer::addHeader($headers, "X-Priority: 1");
//		$headers = Mailer::addHeader($headers, "X-MSMail-Priority: High"); //nécessite X-MimeOLE qui indique que le message a été rédigé avec outlook
		$headers = Mailer::addHeader($headers, "Importance: High");
	}
	
	
	// traiter les modifications des en-têtes ; HEADERS peut être modifié au retour
	function handleHeaders($to, $subject, $mail, &$headers)
	{
		// traiter les en-têtes ; pour PHPMail, on ne fait rien, pour les autres, on rajoute To: et Subject:
		$this->handleHeaders_ToSubject($to, $subject, $mail, $headers);
		
		// traiter importance
		$this->handleHeaders_Priority($to, $subject, $mail, $headers);
	}
	
	
	// envoyer le mail ; renvoie FALSE si OK, chaine si KO (avec message)
	function send($to, $subject, $mail, $headers)
	{
		// si init OK
		if ( $this->ready() )
		{
			// traiter les en-têtes ; HEADERS peut être modifié au retour
			$this->handleHeaders($to, $subject, $mail, $headers);
			
			// envoyer
			$ret = $this->handleSend($to, $subject, $mail, $headers);
			
			// si envoi OK, consigner pour stats
			if ( $ret === FALSE )
				$this->_acknowledgeMailSent();
				
			return $ret;
		}
		else
			return __CLASS__ . ' pas pr&ecirc;t : \'' . $this->getMessage() . '\'';
	}	


	// le gestionnaire est-il prêt ?
	function ready() { return true; }
	
	
	// destruction de la stratégie
	function destruct() {}
	
	
	// obtenir l'erreur de préparation
	function getMessage() { return NULL; }
}


?>