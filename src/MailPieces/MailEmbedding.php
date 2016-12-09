<?php
/**
 * MailEmbedding
 *
 * @author Pierre - dev@net-tools.ovh
 * @license MIT
 */


// namespace
namespace Nettools\Mailing\MailPieces;


use \Nettools\Core\Helpers\FileHelper;
use \Nettools\Mailing\Mailer;




/**
 * Class to deal with embeddings
 */
class MailEmbedding extends MailMixedContent {

// [----- PROTECTED -----

    /** @var string Content-ID for this embedding part */
	protected $_cid = NULL;


	/** 
     * Get embeddings cache
     *
     * @see Mailer::getEmbeddingsCache
     *
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
     */
     public function __construct($file, $file_type, $cid, $ignoreCache = false)
	{
		// if file_type not provided, guess it from the filename
		parent::__construct($file, is_null($file_type) ? FileHelper::guessMimeType($file, "image/jpeg") : $file_type, $ignoreCache);
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
	
	

	public function getHeaders()
	{
		return 	"Content-Type: " . $this->getContentType() . "\r\n" .
				"Content-Transfer-Encoding: base64\r\n" .
				"Content-Disposition: inline;\r\n   filename=\"" . $this->_cid . "\"\r\n" .
				"Content-ID: <" . $this->_cid . ">";
	}
}


?>