<?php
/**
 * MixedRelated
 *
 * @author Pierre - dev@nettools.ovh
 * @license MIT
 */



// namespace
namespace Nettools\Mailing\MailBuilder;





/**
 * Base class for embeddings and attachments
 */ 
abstract class MixedRelated extends Content {

// [----- PROTECTED -----

    /** @var string Path to file to attach/embed or string content (`$_file` must be set to False) */
	protected $_data = NULL;
    
	
	/** @var string Cache key for content */
	protected $_cacheId = NULL;
    
    /** @var bool Indicates whether the result of `getContent` must be cached */
	protected $_noCache = true;	
	
	/** @var bool Indicates whether `$_file` is a filepath or a data string */
	protected $_isFile = true;

	
    
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
	protected function _getCacheId()
	{
		// if cacheId already computed or set by user
		if ( !is_null($this->_cacheId) )
			return $this->_cacheId;
		
		
		// if data is a file path return file path
		if ( $this->_isFile )
			$this->_cacheId = $this->_data;
		
		// if data is string content, use sha1
		else
			$this->_cacheId = hash('sha256', $this->_data);
		
		
		return $this->_cacheId;
	}
	
	
// ----- PROTECTED -----]



// [----- PUBLIC -----

	/**
     * Constructor
     *
     * @param string $data Path to file to attach/embed or data string content is `$isFile` = false
     * @param string $file_type Mime type of file to embed
     * @param bool $noCache Indicates whether the cache must be ignored or used 
     * @param bool $isFile Indicates whether `$content` parameter is a file path or a data string
     */
	public function __construct($data, $file_type, $noCache = true, $isFile = true)
	{
		parent::__construct($file_type);
	
		$this->_data = $data;
		$this->_noCache = $noCache;
		$this->_isFile = $isFile;
	}
	
	
	/**
     * Get data accessor
     * 
     * @return string Path to file or string content
     */
	public function getData() { return $this->_data; }

    
	/**
     * Set data accessor
     * 
     * @param string $f Path to file or string content
     */
    public function setData($f) { $this->_data = $f; }

    
	/**
     * Get NoCache accessor
     * 
     * @return bool True if the cache must not be used, false otherwise
     */
    public function getNoCache() { return $this->_noCache; }
	
    
    /**
     * Set NoCache accessor
     * 
     * @param bool $i Set this parameter to TRUE to ignore the cache
     */
	public function setNoCache($i) { $this->_noCache = $i; }

    
	/**
     * Get CacheId accessor
     * 
     * @return string Returns $_cacheId value (cache key used for content)
     */
    public function getCacheId() { return $this->_cacheId; }
	
    
    /**
     * Set CacheId accessor
     * 
     * @param string $id Set $_cacheId value to define a key for content caching
     */
	public function setCacheId($id) { $this->_cacheId = $id; }

    
	/**
     * Get IsFile accessor
     * 
     * @return bool True if 'file' property points to a real file path, false if it's a data string
     */
    public function getIsFile() { return $this->_isFile; }
	
    
    /**
     * Set IsFile accessor
     * 
     * @param bool $i Set this parameter to TRUE if 'file' property is a real file path, false if it's a data string
     */
	public function setIsFile($i) { $this->_isFile = $i; }
	

	
	/**
	 * Getting part content
	 *
	 * @return string
	 */
    public function getContent()
	{
		// see if the content is already cached (if we send many emails with the same attachment, this is the case !)
		if ( !$this->_noCache && ($content = $this->_getCache()->get($this->_getCacheId())) )
			return $content;


		// if not, read the file (or use data string), base64encode it
		$content = trim(chunk_split(base64_encode($this->_isFile?file_get_contents($this->_data):$this->_data)));
			
		// store encoded data in the cache unless cache is disabled
		if( !$this->_noCache )
			$this->_getCache()->register($this->_getCacheId(), $content);
			
		return $content;					
	}
}

?>