<?php
/**
 * Attachment
 *
 * @author Pierre - dev@nettools.ovh
 * @license MIT
 */

// namespace
namespace Nettools\Mailing\MailBuilder;


use \Nettools\Mailing\MailerEngine\Headers;




/** 
 * Class to deal with attachments
 */
class Attachment extends MixedRelated {

// [----- PROTECTED -----

    /** @var string Filename to display (not to be misunderstood with the path to the file attached) */
	protected $_filename = NULL;


	/**
     * Get attachments cache
     *
     * @see \Nettools\Mailing\MailBuilder\Builder::getAttachmentsCache
     * @return \Nettools\Core\Containers\Cache Cache used for attachments
     */
	protected function _getCache()
	{
		return Builder::getAttachmentsCache();
	}

// ----- PROTECTED -----]



// [----- PUBLIC -----

	/**
     * Constructor
     *
     * @param string $data Path to file or data string content
     * @param string $filename Name of file (used to display a filename for the attachement in the client mail application)
     * @param string $file_type Mime type of file
     * @param bool $noCache Indicates whether the attachments cache must be ignored or used 
     * @param bool $isFile Indicates whether 'file' parameter is a file path or a data string
	 */
	public function __construct($data, $filename, $file_type, $noCache = true, $isFile = true)
	{
		parent::__construct($data, $file_type, $noCache, $isFile);
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
	

	/** 
     * Get headers for this part ; abstract method to implemented in child classes
     *
     * @return Headers Mandatory headers for this part
     */
	public function getHeaders()
	{
		return new Headers([	'Content-Type' 				=> $this->getContentType() . ";\r\n name=\"" . $this->_filename . "\"",
				 				'Content-Transfer-Encoding'	=> 'base64',
								'Content-Disposition'		=> "attachment;\r\n filename=\"" . $this->_filename . "\""
						   ]);
	}
}


?>