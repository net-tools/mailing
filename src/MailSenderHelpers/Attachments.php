<?php

// namespace
namespace Nettools\Mailing\MailSenderHelpers;

// clauses use
use \Nettools\Mailing\Mailer;




/** 
 * Helper class to deal with attachments
 */
class Attachments extends Composite
{
	/**
	 * Factory method to create an attachment
	 * 
	 * @return \Nettools\Mailing\MailPieces\MailAttachment
	 */
	function _poolFactoryMethod()
	{
		return Mailer::createAttachment('', '', '');
	}

	
	
	/**
	 * Set the amount of attachments
	 * 
	 * @param int $c
	 */
	public function setAttachmentsCount($c)
	{
		$this->setItemsCount($c);
	}
	
	
	
	/**
	 * Setting an attachment details
	 *
	 * @param string $f File path to content to attach
	 * @param string $fname Filename of attachment
	 * @param string $ftype Content-type
	 * @param int $index Index of mail attachment to set
	 * @param bool $ignoreCache
	 * @return Attachements
	 */
	public function setAttachment($f, $fname, $ftype, $index = 0, $ignoreCache = false)
	{
		if ( $pj = $this->getItem($index) )
		{
			$pj->setFile($f);
			$pj->setFileName($fname);
			$pj->setContentType($ftype);
			$pj->setIgnoreCache($ignoreCache);
		}
		
		return $this; // chaining
	}

	
	
	/** 
	 * Set all attachements in on call
	 * 
	 * @param array $attachments Array of associative arrays with keys : file, filename, contentType, ignoreCache
	 */
	public function setAttachments($attachments)
	{
		$this->setAttachmentsCount(count($attachments));
		
		for ( $i = 0 ; $i < count($attachments) ; $i++ )
		{
			$a = $attachments[$i];
			$this->setAttachment($a['file'], $a['filename'], $a['contentType'], $i, $a['ignoreCache']);
		}
		
		return $this; // chaining
	}

	
	
	/**
	 * Render email 
	 *
	 * @param mixed $data
	 * @return \Nettools\Mailing\MailPieces\MailMultipart
	 * @throws \Nettools\Mailing\MailSenderHelpers\Exception
	 */
	public function render($data)
	{
		$value = parent::render($data);

		return Mailer::addAttachmentObjects($value, $this->items);
	}
}

?>