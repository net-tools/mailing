<?php

// namespace
namespace Nettools\Mailing\MailPieces;

// clauses use
use \Nettools\Mailing\MailPieces\MailContent;




// classe pour partie du mail (alternative textplain/texthtml, pièces-jointes ou images incorporées)
class MailMultipart extends MailContent {

// [----- MEMBRES PROTEGES -----

	protected $_separator = NULL;
	protected $_parts = NULL;
	protected $_type = NULL;
	
// ----- MEMBRES PROTEGES -----]


// [----- METHODES STATIQUES -----

	// constucteurs statiques
	public static function from ($type, MailContent $p1, MailContent $p2)
	{
		return new MailMultipart($type, array($p1, $p2));
	}


	public static function fromArray ($type, MailContent $p1, $parts)
	{
		return new MailMultipart($type, array_merge(array($p1), $parts));
	}


	public static function fromSingleArray ($type, $parts)
	{
		return new MailMultipart($type, $parts);
	}

// ----- METHODES STATIQUES -----]


// [----- METHODES PUBLIQUES -----

	// accesseurs	
	public function getSeparator() { return $this->_separator; }
	public function getCount() { return count($this->_parts); }
	public function getPart($i) { return $this->_parts[$i]; }
	public function setPart($i, $p) { $this->_parts[$i] = $p; }
	public function getType() { return $this->_type; }
	
	
	// constructeur : plusieurs parties supplémentaires à la première
	public function __construct($type, $parts)
	{
		parent::__construct("multipart/$type");
	
		$this->_type = $type;
		$this->_separator = "am63-${type}-" . sha1(uniqid());
		$this->_parts = $parts;
	}
	
	
	// en-tete
	public function getHeaders()
	{
		return 	"Content-Type: " . $this->getContentType() . ";\r\n   boundary=\"" . $this->_separator . "\"";
	}
	
	
	// contenu
	public function getContent()
	{
		$res =	"--" . $this->_separator . "\r\n" .
				$this->_parts[0]->toString();
				
		for ( $cpt = 1; $cpt < count($this->_parts) ; $cpt++ )
			$res .=	"--" . $this->_separator . "\r\n" .
					$this->_parts[$cpt]->toString();
					
		return $res . "--" . $this->_separator . "--" /*"--\r\n\r\n"*/;
	}

// ----- METHODES PUBLIQUES -----]
}

?>