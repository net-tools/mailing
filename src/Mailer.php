<?php
/**
 * Mailer
 *
 * @author Pierre - dev@nettools.ovh
 * @license MIT
 */



// namespace
namespace Nettools\Mailing;


// clauses use
use \Nettools\Mailing\MailBuilder\Builder;
use \Nettools\Mailing\MailBuilder\Content;
use \Nettools\Mailing\MailSenders\MailSender;
use \Nettools\Core\Helpers\FileHelper;
use \Nettools\Mailing\MailerEngine\Headers;




/**
 * Class to prepare an email and send it through a sending strategy.
 *
 * Currently, the following strategies are available (sub-namespace MailSenders) :
 * 
 * - PHP Mail function
 * - SMTP protocol
 * - eml files stored in a folder
 * - array of strings (useful for debugging)
 *
 */
final class Mailer {
// [----- PROTECTED -----

	/** @var \Nettools\Mailing\MailerEngine\Engine Subclass to handle sending through strategy */
	protected $mailerEngine = NULL;
	
// ----- PROTECTED -----]



// [----- STATIC -----

	/** @var Mailer Default mailer instance (singleton pattern) ; uses MailSenders\PHPMail strategy */
	protected static $defaultMailer = NULL;
	
	
	const TEMPLATE = '%content%';
	
	
	/** 
	 * Get the default mailer (using PHP Mail function strategy)
	 * 
	 * To create a Mailer instance with another strategy, create the instance through it's constructor, not getDefault()
	 * 
	 * @return Mailer Returns a default instance, using PHP mail function strategy
	 */
	public static function getDefault()
	{
		if ( is_null(self::$defaultMailer) )
			self::$defaultMailer = new Mailer(new \Nettools\Mailing\MailSenders\PHPMail());
		
		return self::$defaultMailer;
	}
	
// ----- STATIC -----]
	


	
// [----- PUBLIC -----

	/**
	 * Constructor
	 * 
	 * @param \Nettools\Mailing\MailSenders\MailSender $mailsender Email sending strategy
	 */
	public function __construct(MailSender $mailsender)
	{
		$this->setMailSender($mailsender);
	}
	
	

	/** 
	 * Set the email sending strategy
	 * 
	 * @param \Nettools\Mailing\MailSenders\MailSender $mailsender Email sending strategy
	 * @return bool Returns TRUE if mail sending strategy is ready after its creation, or not 
	 */
	public function setMailSender(MailSender $mailsender)
	{
		$this->mailerEngine = new MailerEngine\Engine($mailsender);
		return $this->mailerEngine->ready();
	}
	
	
	
	/** 
	 * Close email sending strategy (e.g. closing SMTP connections)
	 */
	public function destroy()
	{
		return $this->mailerEngine->destroy();
	}
	
	

	/**
	 * Get underlying mailer engine
	 *
	 * @return \Nettools\Mailing\MailerEngine\Engine
	 */
	public function getMailerEngine()
	{
		return $this->mailerEngine;
	}
	
	
	
	/**
	 * Get a fluent interface to compose email content and sets recipients
	 * 
	 * @return \Nettools\Mailing\FluentEngine\ComposeEngine
	 */
	public function getFluentEngine()
	{
		return new \Nettools\Mailing\FluentEngine\ComposeEngine($this);
	}	
	
	
	
	/**
	 * Simple method call to send an email with content (either plain text or html) and optionnal attachments
	 *
	 * @param string $content String with content (HTML or plain text)
	 * @param string $from Email sender
	 * @param string $to Email recipient ; if multiple recipients, use a comma "," between addresses
	 * @param string $subject Email subject
	 * @param string[] $attachments Array of filepaths
	 * @param bool $destruct Set this parameter to TRUE to have the strategy destroyed after sending the email
	 * @throws \Nettools\Mailing\Exception
	 */
	public function expressSendmail($content, $from, $to, $subject, $attachments = array(), $destruct = false)
	{
		// detect content-type
		if ( preg_match('<(a|strong|em|b|table|div|span|p)>', $content) )
			$mailcontent = Builder::addTextHtmlFromHtml($content);
		else
			$mailcontent = Builder::addTextHtmlFromText($content);
			
			
		// if attachments, prepare attachments list
		if ( count($attachments) )
		{
			$atts = array_map(
						function($att)
						{
							return array(
											'file' 		=> $att,
											'filename'	=> basename($att),
											'filetype'	=> FileHelper::guessMimeType($att)
										);
						}
						, $attachments
					);
			$mailcontent = Builder::addAttachments($mailcontent, $atts);
		}
		
		
		// send the email
		$this->sendmail($mailcontent, $from, $to, $subject, $destruct);
	}
	
	
	
	/**
	 * Send an email built with static building method of Mailer
	 *
	 * @param MailBuilder\Content $mail Mail object to send
	 * @param string $from Email sender
	 * @param string|string[] $to Email recipient ; if multiple recipients, use a comma "," between addresses
	 * @param string $subject Email subject
	 * @param bool $destruct Set this parameter to TRUE to have the strategy destroyed after sending the email
	 * @throws \Nettools\Mailing\Exception
	 */
	public function sendmail(Content $mail, $from, $to, $subject, $destruct = false)
	{
		$this->sendmail_raw($to, $subject, $mail->getContent(), $mail->getAllHeaders()->set('From', $from), $destruct);
	}
	
	
	
	/**
	 * Send raw mail
	 *
	 * @param string|string[] $to Email recipient ; if multiple recipients, use a comma "," between addresses
	 * @param string $subject Email subject
	 * @param string $mail Email body as text
	 * @param \Nettools\Mailing\MailerEngine\Headers $headers Headers array
	 * @param bool $destruct Set this parameter to TRUE to have the strategy destroyed after sending the email
	 * @throws \Nettools\Mailing\Exception
	 */
	public function sendmail_raw($to, $subject, $mail, Headers $headers, $destruct = false)
	{
		if ( is_array($to) )
			$to = implode(',', $to);
		
		
		$this->mailerEngine->send($to, $subject, $mail, $headers);

		if ( $destruct )
			$this->destroy();
	}

// ----- PUBLIC -----]
	
}
?>