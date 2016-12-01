<?php

// namespace
namespace Nettools\Mailing\MailPieces;

// clauses use
use \Nettools\Mailing\MailPieces\MailContent;




// classe de base : pièce-jointe ou image incorporée
abstract class MailMixedContent extends MailContent {

// [----- MEMBRES PROTEGES -----

	protected $_file = NULL;
	protected $_ignoreCache = NULL;


	// obtenir le cache spécifique
	abstract protected function _getCache();

	// clef pour cache
	protected function _getCacheID()
	{
		return $this->_file;
	}
	
	
// ----- MEMBRES PROTEGES -----]



// [----- METHODES PUBLIQUES -----

	// constructeur
	public function __construct($file, $file_type, $ignoreCache = false)
	{
		parent::__construct($file_type);
	
		$this->_file = $file;
		$this->_ignoreCache = $ignoreCache;
	}
	
	
	// accesseurs
	public function getFile() { return $this->_file; }
	public function setFile($f) { $this->_file = $f; }
	public function getIgnoreCache() { return $this->_ignoreCache; }
	public function setIgnoreCache($i) { $this->_ignoreCache = $i; }
	
	
	// contenu
	public function getContent()
	{
		// regarder si ce fichier est dans le cache
		if ( !$this->_ignoreCache && ($content = $this->_getCache()->get($this->_getCacheID())) )
			return $content;


		// sinon, on va chercher le contenu du fichier, on l'encode, et on le stocke dans le cache (sauf ordre contraire)
		$content = trim(chunk_split(base64_encode(file_get_contents($this->_file)))) /*. "\r\n\r\n"*/;
		
		if( !$this->_ignoreCache )
			$this->_getCache()->register($this->_getCacheID(), $content);
			
		return $content;					
	}
}

?>