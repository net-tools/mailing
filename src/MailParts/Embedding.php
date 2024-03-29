<?php
/**
 * Embedding
 *
 * @author Pierre - dev@nettools.ovh
 * @license MIT
 */


// namespace
namespace Nettools\Mailing\MailParts;


use \Nettools\Core\Helpers\FileHelper;
use \Nettools\Mailing\Mailer;
use \Nettools\Mailing\MailerEngine\Headers;




/**
 * Class to deal with embeddings
 */
class Embedding extends MixedRelated {

// [----- PROTECTED -----

    /** @var string Content-ID for this embedding part */
	protected $_cid = NULL;


	/** 
     * Get embeddings cache
     *
     * @see Mailer::getEmbeddingsCache
     * @return \Nettools\Core\Containers\Cache The cache used for embeddings
     */ 
	protected function _getCache()
	{
		return Mailer::getEmbeddingsCache();
	}

// ----- PROTECTED -----]



// [----- PUBLIC -----

	/**
     * Constructor
     * 
     * @param string $file Path to file to embed
     * @param string $file_type Mime type of file to embed
     * @param string $cid Content-ID to associate with the embedding
     * @param bool $ignoreCache Indicates whether the embeddings cache must be ignored or used 
     * @param bool $isFile Indicates whether 'file' parameter is a file path or a data string
	 */
     public function __construct($file, $file_type, $cid, $ignoreCache = false, $isFile = true)
	{
		// if file_type not provided, guess it from the filename
		parent::__construct($file, $file_type, $ignoreCache, $isFile);
		$this->_cid = $cid;
	}
	
	
	/**
     * Get CID accessor
     * 
     * @return string The Content-ID associated with this embedding
     */
	public function getCid() { return $this->_cid; }
    
    
    /**
     * Set CID accessor 
     * 
     * @param string $c Content-ID to associate with this embedding
     */
	public function setCid($c) { $this->_cid = $c; }
	


	/** 
     * Get headers for this part ; abstract method to implemented in child classes
     *
     * @return Headers Mandatory headers for this part
     */
	public function getHeaders()
	{
		return new Headers([	'Content-Type'					=> $this->getContentType(),
								'Content-Transfer-Encoding'		=> 'base64',
								'Content-Disposition'			=> "inline;\r\n filename=\"" . $this->_cid . "\"",
								'Content-ID'					=> "<$this->_cid>"
						]);
	}
}


?>