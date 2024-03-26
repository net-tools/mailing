<?php

// namespace
namespace Nettools\Mailing\MassMailing;


// clauses use
use \Nettools\Mailing\Mailer;




/**
 * Helper class for embedded images
 */
class Embeddings extends Composite
{
	/**
	 * Create a embedding object
	 * 
	 * @return \Nettools\Mailing\MailPart\Embedding
	 */
	function _poolFactoryMethod()
	{
		return Mailer::createEmbedding('', '', '');
	}

	
	
	/**
	 * Set the amount of embedded images
	 * 
	 * @param int $c
	 */
	public function setEmbeddingsCount($c)
	{
		$this->setItemsCount($c);
	}
	
	
	
	/**
	 * Set an embedded image data
	 *
	 * @param string $f File path of file to embed
	 * @param string $ftype Content type
	 * @param string $cid
	 * @param int $index Index of embbedding in mail
	 * @param bool $ignoreCache
	 * @return EmbeddingsMailSenderHelper
	 */
	public function setEmbedding($f, $ftype, $cid, $index = 0, $ignoreCache = false)
	{
		if ( $pj = $this->getItem($index) )
		{
			$pj->setFile($f);
			$pj->setContentType($ftype);
			$pj->setCid($cid);
			$pj->setIgnoreCache($ignoreCache);
		}
		
		return $this; // chaining
	}

	
	
	/** 
	 * Set embedded images with a single class
	 * 
	 * @param array $embeddings Array of associative arrays with keys : file, contentType, cid, ignoreCache
	 * @return Embeddings
	 */
	public function setEmbeddings($embeddings)
	{
		// définir le nb d'images
		$this->setEmbeddingsCount(count($embeddings));
		
		// ajouter un par un
		for ( $i = 0 ; $i < count($embeddings) ; $i++ )
		{
			$e = $embeddings[$i];
			$this->setEmbedding($e['file'], $e['contentType'], $e['cid'], $i, !empty($e['ignoreCache'])?$e['ignoreCache']:false);
		}
		
		return $this; // chaining
	}

	
	
	/**
	 * Render the email
	 *
	 * @param mixed $data
	 * @return \Nettools\Mailing\MailParts\Multipart
	 * @throws \Nettools\Mailing\MassMailing\Exception
	 */
	protected function _render($data)
	{
		// get a Content object and add on top an Embedding object
		return Mailer::addEmbeddingObjects(parent::_render($data), $this->items);
	}
}


?>