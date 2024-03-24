<?php
/**
 * Headers
 *
 * @author Pierre - dev@nettools.ovh
 * @license MIT
 */

// namespace
namespace Nettools\Mailing\MailPieces;





/** 
 * Class to deal with headers
 */
class Headers {

	protected $_data;
	
	
	
	/**
	 * Constructor
	 *
	 * @param string[] Associative array of headers
	 */
	function __construct(array $headers = [])
	{
		$this->_data = $headers;
	}
	
	
	
	/**
	 * Set a header (if already in `$_data`, value is replaced)
	 *
	 * @param string $name
	 * @param string $value
	 * @param bool $encode If set to `true`, the value is encoded with `mb_encode_mimeheader`
	 * @return Headers Return $this for chaining calls
	 */
	function set($name, $value, $encode = false)
	{
		if ( !is_null($value) && $name )
			$this->_data[$name] = $encode ? mb_encode_mimeheader($value) : $value;
		
		
		return $this;
	}
	
	
	
	/**
	 * Set and encode a header (if already in `$_data`, value is replaced)
	 *
	 * @param string $name
	 * @param string $value
	 * @return Headers Return $this for chaining calls
	 */
	function setEncoded($name, $value)
	{
		return $this->set($name, $value, true);
	}
	
	
	
	/**
	 * Set and encode a recipient header (only friendly name part is encoded)
	 *
	 * @param string $name
	 * @param string $value
	 * @return Headers Return $this for chaining calls
	 */
	function setEncodedRecipient($name, $value)
	{
		if ( !is_null($value) )
			// if email address in format "friendlyname <address>"
			if ( preg_match("/(.*)<(.*)>/", $value, $regs) )
			{
				$friendly = trim($regs[1]);
				$addr = trim($regs[2]);

				$this->set($name, mb_encode_mimeheader($friendly) . " <$addr>");
			}
		
			// email address has no friendly name part, setting the value without any encoding
			else
				$this->set($name, $value);		
		
		
		return $this;
	}
	
	
	
	/**
	 * Encode a recipient header
	 *
	 * @param string $name
	 * @return Headers Return $this for chaining calls
	 */
	function encodeRecipient($name)
	{
		return $this->setEncodedRecipient($name, $this->get($name));
	}
	
	
	
	/**
	 * Add headers
	 *
	 * @param string[] $headers Associative array of headers to merge with `$_data`
	 * @return Headers Return $this for chaining calls
	 */
	function merge(array $headers)
	{
		$this->_data = array_merge($this->_data, $headers);
		
		
		return $this;
	}
	
	
	
	/**
	 * Add headers from another object
	 *
	 * @param Headers $headers Other objet of class Headers
	 * @return Headers Return $this for chaining calls
	 */
	function mergeWith(Headers $headers)
	{
		$this->_data = array_merge($this->_data, $headers->toArray());
		
		return $this;
	}
	
	
	
	/**
	 * Remove a header 
	 *
	 * @param string $name
	 * @return Headers Return $this for chaining calls
	 */
	function remove($name)
	{
		if ( !$name || (count($this->_data) == 0) )
			return;
		
		if ( array_key_exists($name, $this->_data) )
			unset($this->_data[$name]);
		
		
		return $this;
	}
	
	
	
	/**
	 * Get decoded value for a header ; is not present, NULL is returned
	 *
	 * @param string $name
	 * @return string|NULL
	 */
	function getDecoded($name)
	{
		$v = $this->get($name);
		if ( !is_null($v) )
			return mb_decode_mimeheader($v);
		else
			return NULL;
	}
	
	
	
	/**
	 * Get value for a header ; is not present, NULL is returned
	 *
	 * @param string $name
	 * @return string|NULL
	 */
	function get($name)
	{
		return $name && array_key_exists($name, $this->_data) ? $this->_data[$name] : null;
	}
	
	
	
	/**
	 * Magic getter
	 *
	 * @param string $name
	 * @return string|NULL
	 */
	function __get($name)
	{
		return $this->get($name);
	}
	
	
	
	/**
	 * Magic setter
	 *
	 * @param string $name
	 * @param string $value
	 */
	function __set($name, $value)
	{
		return $this->set($name, $value);
	}
	
	
	
	/**
	 * Get headers as an associative array
	 *
	 * @return string[]
	 */
	function toArray()
	{
		return $this->_data;
	}
	
	
	
	/**
	 * Transform object to a string with proper formatting
	 * 
	 * @return string Return a string of headers
	 */
	function toString()
	{
		return self::array2string($this->_data);
	}
	
	
	
	/**
	 * Create an Headers object from a string 
	 *
	 * @param string $headers
	 * @return Headers
	 */
	static function fromString($headers)
	{
		return new Headers(self::string2array($headers));
	}
	
	
	
	/**
	 * Create an Headers object from another object (clone)
	 *
	 * @param Headers $headers
	 * @return Headers
	 */
	static function fromObject($headers)
	{
		return new Headers($headers->toArray());
	}
	
	
	
	/**
	 * Get an associative array from a string 
	 *
	 * @param string $headers
	 * @return string[]
	 */
	static function string2array($headers)
	{
		// if no header, return empty array
		if ( !$headers )
			return [];
			
			
        // unfolding of headers : some headers may span over multiple lines ; in that case, following lines of the header spanned begin with at least a space or tab
		$pheaders = array();
		$headers = explode("\n", str_replace("\r\n", "\n", $headers));
		$last = NULL;
		foreach ( $headers as $line )
		{
			// if begin of line is a space or tab, this is a folded header ; concatenate it to the previous header line read
			if ( preg_match("/^[ ]|\t/", $line) && $last )
				$pheaders[$last] .= "\r\n" . $line;
			else
			{
				// default case : explode header name/value
				$line = explode(':', $line, 2);
				$last = trim($line[0]);
				$pheaders[$last] = trim($line[1]);
			}
		}
		
		return $pheaders;
	}
	
	
	
	/**
	 * Get a header string from an associative array 
	 *
	 * @param string[] $headers
	 * @return string
	 */
	static function array2string(array $headers)
	{
		// empty array : empty string returned
		if ( count($headers) == 0 )
			return "";
			
		
		$ret = [];
		foreach ( $headers as $kh=>$h )
			$ret[$kh] = "$kh: $h";
			
		return implode("\r\n", array_values($ret));
	}
}


?>