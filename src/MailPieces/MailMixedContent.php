<?php

// namespace
namespace Nettools\Mailing\MailPieces;



// base class for embeddings and attachments
abstract class MailMixedContent extends MailContent {

// [----- PROTECTED -----

	protected $_file = NULL;
	protected $_ignoreCache = NULL;


	// get cache
	abstract protected function _getCache();

	// get the key for this item in the cache
	protected function _getCacheID()
	{
		return $this->_file;
	}
	
	
// ----- PROTECTED -----]



// [----- PUBLIC -----

	// constructor
	public function __construct($file, $file_type, $ignoreCache = false)
	{
		parent::__construct($file_type);
	
		$this->_file = $file;
		$this->_ignoreCache = $ignoreCache;
	}
	
	
	// accessors
	public function getFile() { return $this->_file; }
	public function setFile($f) { $this->_file = $f; }
	public function getIgnoreCache() { return $this->_ignoreCache; }
	public function setIgnoreCache($i) { $this->_ignoreCache = $i; }
	
	
	// get content for this part
	public function getContent()
	{
		// see if the content is already cached (if we send many emails with the same attachment, this is the case !)
		if ( !$this->_ignoreCache && ($content = $this->_getCache()->get($this->_getCacheID())) )
			return $content;


		// if not, read the file, base64encode it, and store it in the cache (unless instructed not to do so)
		$content = trim(chunk_split(base64_encode(file_get_contents($this->_file)))) /*. "\r\n\r\n"*/;
		
		if( !$this->_ignoreCache )
			$this->_getCache()->register($this->_getCacheID(), $content);
			
		return $content;					
	}
}

?>