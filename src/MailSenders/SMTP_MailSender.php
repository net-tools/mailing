<?php

// namespace
namespace Nettools\Mailing\MailSenders;

// clauses use
use \Nettools\Mailing\MailSender;
use \Nettools\Mailing\Mailer;



// stratégie pour envoi par SMTP mail()
// paramètres attendus : host, port, auth, username, password, persist
class SMTP_MailSender extends MailSender
{
	// [----- MEMBRES PROTEGES -----
	
	protected $smtp = NULL;
	protected $initerror = NULL;
	
	
	// envoyer le mail ; headers est un tableau
	protected function _doSend($to, $mail, $headers)
	{
		// envoyer
		$ret = $this->smtp->send($to, $headers, $mail);
		if ( $ret === TRUE )
			return FALSE;
		else
			return $ret->toString();
	}
	
	
	// ----- MEMBRES PROTEGES -----]

	// constructeur
	function __construct($params = NULL)
	{
		// appel constructeur parent
		parent::__construct($params);
		

		// vérifier qu'on a au moins l'hote
		if ( !isset($this->params['host']) )
		{
			$this->initerror = "Parametres SMTP non fournis, HOST manquant";
			return;
		}

		// valeurs par défaut
		$this->params['port'] or $this->params['port'] = '25';
		$this->params['auth'] or $this->params['auth'] = FALSE;
		$this->params['persist'] or $this->params['persist'] = FALSE;
		
		
		// tester existence librairie composer ; si inexistante, ne pas initialiser
		if ( 
				(strpos(get_include_path(), 'ppast/auth_sasl') === FALSE)
				||
				(strpos(get_include_path(), 'pear/mail') === FALSE)
				||
				(strpos(get_include_path(), 'pear/net_smtp') === FALSE)
			)
		{
			$this->smtp = null;
			$this->initerror = "Librairies Composer manquantes : pear/mail ou pear/net_smtp ou ppast/auth_sasl";
		}
		else
			// créer la classe de connexion SMTP
			$this->smtp = \Mail::factory('smtp', 
				array(
						  'host'         	=> $this->params['host'],
						  'port'         	=> $this->params['port'],
						  'auth'         	=> $this->params['auth'] ? TRUE:FALSE,
						  'username'     	=> $this->params['username'],
						  'password'     	=> $this->params['password'],
						  'persist'      	=> $this->params['persist'] ? TRUE:FALSE,
						  'socket_options'	=> array('ssl'=>array('verify_peer'=>false))
					)); 
	}
	
	
	// le gestionnaire est-il prêt ?
	function ready()
	{
		// tester 
		return parent::ready() && $this->smtp && ($this->smtp instanceof \Mail);
	}
	
	
	// destruction de la stratégie
	function destruct()
	{
		if ( $this->params['persist'] && $this->ready() )
		{
			$level = error_reporting(E_ERROR);		// incompatibilité php 5.4
			$this->smtp->disconnect();
			error_reporting($level);				// incompatibilité php 5.4
		}
	}
	
	
	// obtenir des infos sur l'erreur
	function getMessage()
	{
		if ( $this->smtp && $this->smtp instanceof \PEARError )
			return $this->smtp->toString();
		else
		if ( $this->initerror )
			return $this->initerror;
		else
			return NULL;
	}
	
		
	// envoyer un mail
	function doSend($to, $subject, $mail, $headers)
	{
		// PEAR SMTP::send attend les en-tete sous forme de tableau associatif !
		return $this->_doSend($to, $mail, Mailer::headersToArray($headers));
	}
}
?>