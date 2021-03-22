<?php

// namespace
namespace Nettools\Mailing\MailSenderHelpers;


// clauses use
use \Nettools\Mailing\Mailer;




/**
 * Helper class to send an email whose content is rendered with Twig
 */
class Twig extends MailSenderHelper
{
	protected $_twigTemplate = NULL;
	
	
	
	/**
	 * Constructor
	 *
	 * Optionnal parameters for `$params` are :
	 *   - those of MailSenderHelper, plus :
	 *   - cache : If set, path to twig cache as a string
	 *
	 * @param \Nettools\Mailing\Mailer $mailer
	 * @param string $mail Mail content as a string
	 * @param string $mailContentType May be 'text/plain' or 'text/html'
	 * @param string $from Sender address
	 * @param string $subject
	 * @param string[] $params Associative array with optionnal parameters
	 * @throws \Nettools\Mailing\MailSenderHelpers\Exception
	 */
	function __construct(Mailer $mailer, $mail, $mailContentType, $from, $subject, array $params = [])
	{
		// calling parent constructor
		parent::__construct($mailer, $mail, $mailContentType, $from, $subject, $params);
			
		
		// cache path
		$cache = array_key_exists('cache', $params) ? $params['cache'] : NULL;
		if ( is_null($cache) )
			$cache = sys_get_temp_dir();
		
		try
		{
			$twig = uniqid();
			$loader = new \Twig\Loader\ArrayLoader([$twig => $mail]);
			$twigenv = new \Twig\Environment($loader, array(
				'cache' => $cache,
				'strict_variables' => true,
				'auto_reload'=>true
			));


			$this->_twigTemplate = $twigenv->load($twig);
		}
		catch(\Exception $e)
		{
			throw new \Nettools\Mailing\MailSenderHelpers\Exception('Twig loading issue : ' . $e->getMessage());
		}
	}
	
	

	/** 
	 * Testing required parameters
	 *
	 * @throws \Nettools\Mailing\MailSenderHelpers\Exception
	 */
	public function ready()
	{
		parent::ready();
		
		
		if ( !$this->_twigTemplate )
			throw new \Nettools\Mailing\MailSenderHelpers\Exception('Twig template construction failed during constructor call of MailSenderHelpers\\Twig');
	}

	

	/**
	 * Render email
	 *
	 * @param mixed $data Data used in twig template rendering
	 * @return \Nettools\Mailing\MailPieces\MailContent
	 * @throws \Nettools\Mailing\MailSenderHelpers\Exception
	 */
	public function render($data)
	{
		try
		{
			// using twig template and render with $data
			$compiled = $this->_twigTemplate->render($data);
			
			return $this->_createMailContent($compiled);
		}
		catch(\Throwable $e)
		{
			throw new \Nettools\Mailing\MailSenderHelpers\Exception('Twig rendering issue : ' . $e->getMessage());
		}
	}
}


?>