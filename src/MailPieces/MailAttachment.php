<?php
/**
 * MailAttachment
 *
 * @author Pierre - dev@nettools.ovh
 * @license MIT
 */

// namespace
namespace Nettools\Mailing\MailPieces;


use \Nettools\Mailing\Mailer;



/** 
 * Class to deal with attachments
 */
class MailAttachment extends MailMixedContent {

// [----- PROTECTED -----

    /** @var string Filename to display (not to be misunderstood with the path to the file attached) */
	protected $_filename = NULL;


	/**
     * Get attachments cache
     *
     * @see \Nettools\Mailing\Mailer::getAttachmentsCache
     * @return \Nettools\Core\Containers\Cache Cache used for attachments
     */
	protected function _getCache()
	{
		return Mailer::getAttachmentsCache();
	}

// ----- PROTECTED -----]



// [----- PUBLIC -----

	/**
     * Constructor
     *
     * @param string $file Path to file
     * @param string $filename Name of file (used to display a filename for the attachement in the client mail application)
     * @param string $file_type Mime type of file
     * @param bool $ignoreCache Indicates whether the attachments cache must be ignored or used 
     * @param bool $isFile Indicates whether 'file' parameter is a file path or a data string
	 */
	public function __construct($file, $filename, $file_type, $ignoreCache = false, $isFile = true)
	{
		parent::__construct($file, $file_type, $ignoreCache, $isFile);
		$this->_filename = $filename;
	}
	
	
	/** 
     * Get Filename accessor
     * 
     * @return string Filename to display
     */
	public function getFileName() { return $this->_filename; }

    
    /** 
     * Set Filename accessor
     * 
     * @param string $f Filename to display
     */
	public function setFileName($f) { $this->_filename = $f; }
	

	public function getHeaders()
	{
		return 	"Content-Type: " . $this->getContentType() . ";\r\n name=\"" . $this->_filename . "\"\r\n" .
				"Content-Transfer-Encoding: base64\r\n" .
				"Content-Disposition: attachment;\r\n filename=\"" . $this->_filename . "\"";
	}
}


?>