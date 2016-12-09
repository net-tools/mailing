<?php
/**
 * MailMixedContent
 *
 * @author Pierre - dev@net-tools.ovh
 * @license MIT
 */



// namespace
namespace Nettools\Mailing\MailPieces;



/**
 * Base class for embeddings and attachments
 */ 
abstract class MailMixedContent extends MailContent {

// [----- PROTECTED -----

    /** @var string Path to file to attach or embed */
	protected $_file = NULL;
    
    
    /** @var bool Indicates whether the cache should be used or not */
	protected $_ignoreCache = NULL;

    
	/** 
     * Abstract method to get the cache object to query
     *
     * @return \Nettools\Core\Containers\Cache Returns a Cache instance
     */
	abstract protected function _getCache();

    
	/**
     * Get the key for this item in the cache
     * 
     * @return string Returns the cache key for this part
     */
	protected function _getCacheID()
	{
		return $this->_file;
	}
	
	
// ----- PROTECTED -----]



// [----- PUBLIC -----

	/**
     * Constructor
     *
     * @param string $file Path to file to attach/embed
     * @param string $file_type Mime type of file to embed
     * @param bool $ignoreCache Indicates whether the cache must be ignored or used 
     */
	public function __construct($file, $file_type, $ignoreCache = false)
	{
		parent::__construct($file_type);
	
		$this->_file = $file;
		$this->_ignoreCache = $ignoreCache;
	}
	
	
	/**
     * Get File accessor
     * 
     * @return string Path to file
     */
	public function getFile() { return $this->_file; }

    
	/**
     * Set File accessor
     * 
     * @param string $f Path to file
     */
    public function setFile($f) { $this->_file = $f; }

    
	/**
     * Get IgnoreCache accessor
     * 
     * @return bool True if the cache must not be used, false otherwise
     */
    public function getIgnoreCache() { return $this->_ignoreCache; }
	
    
    /**
     * Set IgnoreCache accessor
     * 
     * @param bool Set this parameter to TRUE to ignore the cache
     */
	public function setIgnoreCache($i) { $this->_ignoreCache = $i; }
	

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