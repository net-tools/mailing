<?php

// namespace
namespace Nettools\Mailing\MailPieces;

// clauses use
use \Nettools\Mailing\MailPieces\MailContent;





// classe de base : contenu Text/Plain
// des accents peuvent figurer, ils seront encodés lors du rendu final avec transfer encoding = quoted printable
class MailTextPlainContent extends MailContent {

// [----- MEMBRES PROTEGES -----

	protected $_text;

// ----- MEMBRES PROTEGES -----]



// [----- METHODES PUBLIQUES -----

	// constructeur
	public function __construct($text)
	{
		parent::__construct("text/plain");
		$this->_text = $text;
	}
	
	
	// accesseur
	public function getText() { return $this->_text; }
	public function setText($t) { $this->_text = $t; }
	
	
	// en-tete
	public function getHeaders()
	{
/*		return 	"Content-Type: " . $this->getContentType() . "; charset=ISO-8859-1\r\n" .
				"Content-Transfer-Encoding: quoted-printable";*/
		return 	"Content-Type: " . $this->getContentType() . "; charset=UTF-8\r\n" .
				"Content-Transfer-Encoding: quoted-printable";
	}
	
	
	// contenu
	public function getContent()
	{
		// format avec encoding quoted printable (ascii>127 encodés), mais laisser les sauts de ligne
		return trim(str_replace("=0A", "\n", str_replace("=0D", "\r", imap_8bit($this->_text)))) /*. "\r\n\r\n"*/;
	}
}


?>