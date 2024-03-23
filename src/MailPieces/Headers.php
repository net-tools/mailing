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
	 * Add a header (if already in `$_data`, value is replaced)
	 *
	 * @param string $name
	 * @param string $value
	 */
	function add($name, $value)
	{
		$this->_data[$name] = $value;
	}
	
	
	
	/**
	 * Add headers
	 *
	 * @param string[] $headers Associative array of headers to merge with `$_data`
	 */
	function merge(array $headers)
	{
		$this->_data[$name] = array_merge($this->_data, $headers);
	}
	
	
	
	/**
	 * Remove a header 
	 *
	 * @param string $name
	 */
	function remove($name)
	{
		if ( count($this->_data) == 0 )
			return;
		
		if ( $name && array_key_exists($name, $this->_data) )
			unset($this->_data[$name]);
	}
	
	
	
	/**
	 * Get value for a header ; is not present, NULL is returned
	 *
	 * @param string $name
	 * @return string|NULL
	 */
	function get($name)
	{
		return array_key_exists($name, $this->_data) ? $this->_data[$name] : null;
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