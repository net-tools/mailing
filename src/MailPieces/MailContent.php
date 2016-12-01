<?php

// namespace
namespace Nettools\Mailing\MailPieces;

// clauses use
use \Nettools\Mailing\Mailer;




// classe de base : un contenu avec un Content/Type
abstract class MailContent {

// [----- MEMBRES PROTEGES -----

	protected $_content_type = NULL;
	protected $_custom_headers = "";

// ----- MEMBRES PROTEGES -----]



// [----- METHODES PUBLIQUES -----

	// constructeur
	public function __construct($content_type)
	{
		$this->_content_type = $content_type;
	}
	
	
	// accesseur
	public function getContentType() { return $this->_content_type; }
	public function setContentType($c) { $this->_content_type = $c; }
	
	
	// équivalent textuel ; utilisé dans MailMultipart::getContent()
	public function toString()
	{
		return $this->getFullHeaders() . "\r\n\r\n" . $this->getContent() . "\r\n\r\n";
	}
	
	
	// rajouter des en-têtes perso
	public function setCustomHeaders($h)
	{
		$this->_custom_headers = $h;
	}


	// ajouter un en-tête perso
	public function addCustomHeader($h)
	{
		$this->_custom_headers = Mailer::addHeader($this->_custom_headers, $h);
	}


	// obtenir les en-têtes perso
	public function getCustomHeaders()
	{
		return $this->_custom_headers;
	}


	// obtenir les en-têtes du contenu ; à implémenter dans les classes filles
	abstract public function getHeaders();
	
	
	// obtenir les en-têtes du contenu + en-tetes perso
	public function getFullHeaders()
	{
		return Mailer::addHeader($this->getHeaders(), $this->getCustomHeaders());
	}
	
	
	// obtenir le contenu lui-même ; à implémenter dans les classes filles
	abstract public function getContent();
}


?>