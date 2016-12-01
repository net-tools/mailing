<?php

// namespace
namespace Nettools\Mailing\MailPieces;

// clauses use
use \Nettools\Mailing\MailPieces\MailContent;





// classe de base : contenu Text/html
// le code HTML doit être propre (entités html pour les accents) ; le rendu donnera des lignes de 70 caractères en quoted printable
class MailTextHtmlContent extends MailContent {

// [----- MEMBRES PROTEGES -----

	protected $_html;

// ----- MEMBRES PROTEGES -----]



// [----- METHODES PUBLIQUES -----

	// constructeur
	public function __construct($html)
	{
		parent::__construct("text/html");
		$this->_html = $html;
	}
	
	
	// accesseur
	public function getHtml() { return $this->_html; }
	public function setHtml($t) { $this->_html = $t; }
	
	
	// en-tete
	public function getHeaders()
	{
		return 	"Content-Type: " . $this->getContentType() . "; charset=UTF-8\r\n" .
				"Content-Transfer-Encoding: quoted-printable";
	}
	
	
	// contenu
	public function getContent()
	{
		return trim(str_replace("=0A", "\n", str_replace("=0D", "\r", imap_8bit($this->_html)))) /*. "\r\n\r\n"*/;
	}
}



?>