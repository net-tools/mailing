<?php
/**
 * Multipart
 *
 * @author Pierre - dev@nettools.ovh
 * @license MIT
 */



// namespace
namespace Nettools\Mailing\MailParts;


use \Nettools\Mailing\MailerEngine\Headers;





/**
 * Base class for handling 2 or more email parts 
 */
class Multipart extends Content {

// [----- PROTECTED -----

    /** @var string Boundary separator (please refer to email RFC https://tools.ietf.org/html/rfc2822) */
	protected $_separator = NULL;
    
    /** @var Content[] Array of parts */
	protected $_parts = NULL;
    
    /** @var string Mime type for this part */
	protected $_type = NULL;
	
// ----- PROTECTED -----]


// [----- STATIC -----

	/** 
     * Static factory to create a Multipart from two parts
     *
     * Allowed types are :
     * 
     * - mixed (attachments)
     * - alternative (text/plain or text/html)
     * - related (embeddings)
     * 
     * @param string $type Type of the multipart
     * @param Content $p1 First part
     * @param Content $p2 Second part
     * @return Multipart New object created from parameters
     */
	public static function from ($type, Content $p1, Content $p2)
	{
		return new Multipart($type, array($p1, $p2));
	}


	/** 
     * Static factory to create a Multipart from an array of parts merged with one first part (case for text/html and attachments)
     *
     * Allowed types are :
     *
     * - mixed (attachments)
     * - alternative (text/plain or text/html)
     * - related (embeddings)
     * 
     * @param string $type Type of the multipart
     * @param Content $p1 First part
     * @param Content[] $parts Array of parts
     * @return MailMultipart New object created from parameters
     */
	public static function fromArray ($type, Content $p1, array $parts)
	{
		return new Multipart($type, array_merge(array($p1), $parts));
	}


	/** 
     * Static factory to create a Multipart from an array of parts
     *
     * Allowed types are :
     *
     * - mixed (attachments)
     * - alternative (text/plain or text/html)
     * - related (embeddings)
     *
     * @param string $type Type of the multipart
     * @param Content[] $parts Array of parts
     * @return Multipart New object created from parameters
     */
	public static function fromSingleArray ($type, array $parts)
	{
		return new Multipart($type, $parts);
	}

// ----- STATIC -----]


// [----- PUBLIC -----

	/** 
     * Get Separator accessor
     * 
     * @return string The boundary separator 
     */
	public function getSeparator() { return $this->_separator; }
    
    
    /**
     * Get the number of parts
     * 
     * @return int The number of parts
     */
	public function getCount() { return count($this->_parts); }
    
    
    /** 
     * Get a part 
     * 
     * @param int $i Part index
     * @return Content The part at index $i
     */
	public function getPart($i) { return $this->_parts[$i]; }

    
    /** 
     * Set a part 
     * 
     * @param int $i Part index
     * @param Content $p The part to set at index $i
     */
    public function setPart($i, $p) { $this->_parts[$i] = $p; }

    
    /**
     * Get the type of this part
     *
     * @return string Returns mixed, alternative or related
     */
	public function getType() { return $this->_type; }
	
	
	/**
     * Constructor
     *
     * Allowed types are :
     *
     * - mixed (attachments)
     * - alternative (text/plain or text/html)
     * - related (embeddings)
     *
     * @param string $type Type of the multipart
     * @param Content[] $parts Array of parts
     */
	public function __construct($type, array $parts)
	{
		parent::__construct("multipart/$type");
	
		$this->_type = $type;
		$this->_separator = "---" . md5(uniqid());
		$this->_parts = $parts;
	}
	
	
	/** 
     * Get headers for this part ; abstract method to implemented in child classes
     *
     * @return Headers Mandatory headers for this part
     */
	public function getHeaders()
	{
		return new Headers([ 'Content-Type' => $this->getContentType() . ";\r\n boundary=\"$this->_separator\"" ]);
	}
	
	
	/**
     * Get content (parts are merged)
     *
     * @return string The content of the multipart
     */
	public function getContent()
	{
		$res =	"--" . $this->_separator . "\r\n" .
				$this->_parts[0]->toString();
				
		for ( $cpt = 1; $cpt < count($this->_parts) ; $cpt++ )
			$res .=	"--" . $this->_separator . "\r\n" .
					$this->_parts[$cpt]->toString();
					
		return $res . "--" . $this->_separator . "--" /*"--\r\n\r\n"*/;
	}

}

?>