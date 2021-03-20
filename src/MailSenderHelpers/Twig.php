<?php

// namespace
namespace Nettools\Mailing\MailSenderHelpers;

// clauses use
use \Nettools\Mailing\Mailer;




/**
 * Helper class to send email with Twig rendering
 */
class Twig extends MailSenderHelper
{
	protected $_twigTemplate = NULL;
	
	
	
	/**
	 * Constructor
	 *
	 * @param \Nettools\Mailing\Mailer $mailer
	 * @param string $mail Mail content as a string
	 * @param string $mailContentType May be 'text/plain' or 'text/html'
	 * @param string $from Sender address
	 * @param string $subject
	 * @param bool $testmode If true, email are sent to testing addresses
	 * @param string $template Template string of email ; if set, must include a `%content%` string that will be replaced by the actual mail content
	 * @param string $bcc If set, Email BCC address to send a copy to
	 * @param string $msender If set, a MailSenderQueue name to append emails to
	 * @param string $msender_params If set, parameters of `$msender` queue
	 * @param string[] $testmails If set, an array of email addresses to send emails to for testing purposes
	 * @param string $replyto If set, an email address to set as ReplyTo header
	 * @param string $cache Path to cache
	 * @throws \Nettools\Mailing\MailSenderHelpers\Exception
	 */
	function __construct(Mailer $mailer, $mail, $mailContentType, $from, $subject, $testmode, $template = NULL, $bcc = NULL, $msender = NULL, $msender_params = NULL, $testmails = NULL, $replyto = false, $cache = NULL)
	{
		// calling parent constructor
		parent::__construct($mailer, $mail, $mailContentType, $from, $sujet, $testmode, $template, $bcc, $msender, $msender_params, $testmails, $replyto);
			
		
		// cache path
		if ( is_null($cache) )
			$cache = sys_get_temp_dir();
		
		try
		{
			$twig = "$subject.twig";
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
			throw new \Nettools\Mailing\MailSenderHelpers\Exception('Twig template is empty');
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