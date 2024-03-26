<?php

// namespace
namespace Nettools\Mailing\MailSenderHelpers;


// clauses use
use \Nettools\Mailing\MailParts\Content;





/**
 * Base to class to deal with attachments and embeddings
 */
abstract class Composite implements MailSenderHelperIntf
{
	
	protected $component = NULL;
	protected $itemsPool = NULL;
	protected $items = NULL;


	
	/** 
	 * Constructor
	 * 
	 * @param \Nettools\Mailing\MailSenderHelpers\MailSenderHelperIntf $component Underlying object
	 */
	function __construct(MailSenderHelperIntf $component)
	{
		$this->component = $component;
			
		// create pool to deal smartly with instances between many call of render method
		$this->itemsPool = new \Nettools\Core\Containers\Pool(array($this, '_poolFactoryMethod'));
		$this->items = array();
	}
	
	
	/**
	 * Factory method
	 * 
	 * @return \Nettools\Mailing\MailParts\MixedContent
	 */
	abstract function _poolFactoryMethod();
	
	
	
	/** 
	 * Getter for ToOverride
	 *
	 * @return NULL|string Returns NULL if no override, a string with email address otherwise
	 */
	public function getToOverride() { return $this->component->getToOverride();}
	
	
	
	/**
	 * Setter for ToOverride
	 * 
	 * @param strig $o Email address to send all emails to (for debugging purpose)
	 * return \Nettools\Mailing\MailSenderHelpers\MailSenderHelperIntf Returns the calling object for chaining
	 */
	public function setToOverride($o) { return $this->component->setToOverride($o); return $this; }
	
	
	
	/**
	 * Accessor for test mode
	 *
	 * @return bool
	 */
	public function getTestMode() { return $this->component->getTestMode();}
	
	
	
	/**
	 * Get raw mail string before any rendering actions
	 *
	 * @return string
	 */
	public function getRawMail() { return $this->component->getRawMail(); }
	
	
	
	/**
	 * Update raw mail string
	 * 
	 * @param string $m
	 * return \Nettools\Mailing\MailSenderHelpers\MailSenderHelperIntf Returns the calling object for chaining
	 */
	public function setRawMail($m) { return $this->component->setRawMail($m); return $this; }
	

	
	/**
	 * Destruct object
	 */
	public function destroy()
	{
		$this->component->destroy();
	}
	
	
	
	/** 
	 * Testing required parameters
	 *
	 * @throws \Nettools\Mailing\MailSenderHelpers\Exception
	 */
	public function ready()
	{
		if ( empty($this->component) )
			throw new \Nettools\Mailing\MailSenderHelpers\Exception('Underlying object unset');

		if ( empty($this->itemsPool) ) 
			throw new \Nettools\Mailing\MailSenderHelpers\Exception('Items pool not initialized');
			
		
		// call underlying object ready method, which will throw an exception if something wrong
		$this->component->ready();
	}
	

	
	/**
	 * Set attachements count, and update pool as necessary
	 *
	 * @param int $c
	 */
	public function setItemsCount($c)
	{
		// if we ask for more items that we have in pool, increasing it
		if ( count($this->items) < $c )
		{
			for ( $i = count($this->items) ; $i < $c ; $i++ )
				// ask for an object thanks to pool->get
				$this->items[] = $this->itemsPool->get();
		}
		
		// if we ask for less items that we have in pool, replacing those unneeded back in pool
		elseif ( $c < count($this->items) )
		{
			$attcount = count($this->items);
			for ( $i = $c ; $i < $attcount ; $i++ )
				$this->itemsPool->release($this->items[$i]);
				
			// trimming array
			array_splice($this->items, 0, $c);
		}
	}
	
	
	
	/**
	 * Get an item
	 * 
	 * @param int $index
	 * @return \Nettools\Mailing\MailParts\MixedContent
	 * @throws \Nettools\Mailing\MailSenderHelpers\Exception
	 */
	public function getItem($index = 0)
	{
		if ( $index < count($this->items) )
			return $this->items[$index];
		else
			throw new \Nettools\Mailing\MailSenderHelpers\Exception("Index value is incorrect ($index)");
	}

	
	
	/** 
	 * Rendering email
	 *
	 * @param mixed $data
	 * @return \Nettools\Mailing\MailParts\Multipart
	 * @throws \Nettools\Mailing\MailSenderHelpers\Exception
	 */
	public function render($data)
	{
		$this->ready();
		
		return $this->component->render($data);
	}

	
	
	/**
	 * Send the email
	 * 
	 * @param \Nettools\Mailing\MailPars\Content $mail
	 * @param string $to Email recipient
	 * @param string $subject Specific email subject ; if NULL, the default value passed to the constructor will be used
	 */
	public function send(Content $mail, $to, $subject = NULL)
	{
		$this->component->send($mail, $to, $subject);
	}

	
	
	/**
	 * Closing queue
	 */
	public function closeQueue()
	{
		return $this->component->closeQueue();
	}


	
	/** 
	 * Getting queue count
	 *
	 * @return int 
	 */
	public function getQueueCount()
	{
		return $this->component->getQueueCount();
	}
}

?>