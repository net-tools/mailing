<?php
/**
 * Mailer
 *
 * @author Pierre - dev@net-tools.ovh
 * @license MIT
 */



// namespace
namespace Nettools\Mailing;


// clauses use
use \Nettools\Mailing\MailPieces\MailAttachment;
use \Nettools\Mailing\MailPieces\MailContent;
use \Nettools\Mailing\MailPieces\MailEmbedding;
use \Nettools\Mailing\MailPieces\MailMixedContent;
use \Nettools\Mailing\MailPieces\MailMultipart;
use \Nettools\Mailing\MailPieces\MailTextHtmlContent;
use \Nettools\Mailing\MailPieces\MailTextPlainContent;
use \Nettools\Core\Helpers\EncodingHelper;
use \Nettools\Core\Containers\Cache;
use \Nettools\Core\Helpers\FileHelper;




/*
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

	/** @var MailSender Email sending strategy */
	protected $mailsender = NULL;
	
// ----- PROTECTED -----]



// [----- STATIC -----
	
	/** @var \Nettools\Core\Containers\Cache Cache for attachments */
	protected static $cacheAttachments = NULL;

	/** @var \Nettools\Core\Containers\Cache Cache for embeddings */
	protected static $cacheEmbeddings = NULL;
	
	/** @var Mailer Default mailer instance (singleton pattern) ; uses PHPMail_MailSender class strategy */
	protected static $defaultMailer = NULL;
	
	
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
			self::$defaultMailer = new Mailer(MailSender::PHPMAIL, NULL);
		
		return self::$defaultMailer;
	}


	/**
	 * Get cache for attachments
	 *
	 * @return \Nettools\Core\Containers\Cache Cache for attachments 
	 */
	public static function getAttachmentsCache()
	{
		if ( is_null(self::$cacheAttachments) )
			self::$cacheAttachments = new \Nettools\Core\Containers\Cache();
			
		return self::$cacheAttachments;
	}
	
	
	/**
	 * Get cache for embeddings
	 *
	 * @return \Nettools\Core\Containers\Cache Cache for embeddings 
	 */
	public static function getEmbeddingsCache()
	{
		if ( is_null(self::$cacheEmbeddings) )
			self::$cacheEmbeddings = new \Nettools\Core\Containers\Cache();
			
		return self::$cacheEmbeddings;
	}
	
	
	/**
	 * Create a email with a text/plain part and a text/html part
	 *
	 * @param string $plain Plain text part
	 * @param string $html HTML text part
	 * @return MailPieces\MailMultipart Returns a multipart/alternative part
	 */
	public static function addTextHtml ($plain, $html)
	{
		return self::addAlternativeObject(self::createText($plain), self::createHtml($html));
	}
	
	
	/**
	 * Create a email with a text/html part ; the text/plain part is built from the text/html part
	 *
	 * @param string $html HTML text part
	 * @param string $htmltemplate Template for html part ; use `%content%` in the template to set the placeholder for content
	 * @return MailPieces\MailMultipart Returns a multipart/alternative part
	 */
	public static function addTextHtmlFromHtml ($html, $htmltemplate = "%content%")
	{
		$html = str_replace("%content%", $html, $htmltemplate);
		return self::addTextHtml(self::html2plain($html), $html);
	}
	
	
	/**
	 * Create a email with a text/plain part ; the text/html  part is built from the text/plain part
	 *
	 * @param string $plain Plain text part
	 * @param string $htmltemplate Template for html part ; use `%content%` in the template to set the placeholder for content
	 * @return MailPieces\MailMultipart Returns a multipart/alternative part
	 */
	public static function addTextHtmlFromText ($plain, $htmltemplate = "%content%")
	{
		return self::addTextHtml(
								str_replace("%content%", $plain, self::html2plain($htmltemplate)), 
								str_replace("%content%", self::plain2html($plain), $htmltemplate)
							);
	}
	
	
	/**
	 * Create a multipart/alternative part
	 * 
	 * The text/plain and text/html part are in fact "childs" of a multipart/alternative part
	 *
	 * @param MailPieces\MailContent $alt1 Part 1
	 * @param MailPieces\MailContent $alt2 Part 2
	 * @return MailPieces\MailMultipart Returns a multipart/alternative part
	 */
	public static function addAlternativeObject (MailContent $alt1, MailContent $alt2)
	{
		return MailMultipart::from("alternative", $alt1, $alt2);
	}
	
	
	/**
	 * Create a text/plain part
	 * 
	 * @return MailPieces\MailTextPlainContent The plain text part
	 */
	public static function createText ($text)
	{
		return new MailTextPlainContent($text);
	}
	
	
	/**
	 * Create a text/html part
	 * 
	 * @return MailPieces\MailTextHtmlContent The HTML part
	 */
	public static function createHtml ($html)
	{
		return new MailTextHtmlContent($html);
	}
	
	
	/**
	 * Create an embedding object
	 * 
	 * @param string $embed File path to the file to embed
	 * @param string $embedtype Mime type of the embedding
	 * @param string $cid Content-ID for embedding
	 * @return MailPieces\MailEmbedding Returns a embedding part
	 */
	public static function createEmbedding($embed, $embedtype, $cid)
	{
		return new MailEmbedding($embed, $embedtype, $cid);
	}
	
	
	/**
	 * Create an attachment object
	 * 
	 * @param string $file File path to the file to attach
	 * @param string $filename File name used in the email (will appear in the email client of the recipient)
	 * @param string $filetype Mime type of the attachment
	 * @return MailPieces\MailAttachment Returns a embedding part
	 */
	public static function createAttachment($file, $filename, $filetype)
	{
		return new MailAttachment($file, $filename, $filetype);
	}
	
	
	/**
	 * Adds several attachments to an email
	 * 
	 * @param MailPieces\MailContent $mail Email object
	 * @param string[][] $files Array of array about files to attach ; provide `file`, `filename` and `filetype` values for each file
	 * @return MailPieces\MailMultipart Returns a multipart
	 */
	public static function addAttachments (MailContent $mail, $files)
	{
		$att = array();
		foreach ( $files as $f )
			$att[] = self::createAttachment($f['file'], $f['filename'], $f['filetype']);
			
		return self::addAttachmentObjects($mail, $att);
	}
	
	
	/**
	 * Add an attachment to an email
	 * 
	 * @param MailPieces\MailContent $mail Email object
	 * @param string $file Filepath to file to attach
	 * @param string $filename Filename to display to the user
	 * @param string $filetype Mime type of the attachment
	 * @return MailPieces\MailMultipart Returns a multipart
	 */
	public static function addAttachment (MailContent $mail, $file, $filename, $filetype)
	{
		return self::addAttachmentObject($mail, self::createAttachment($file, $filename, $filetype));
	}

	
	/**
	 * Add an attachment object to an email
	 * 
	 * @param MailPieces\MailContent $mail Email object
	 * @param MailPieces\MailAttachment $obj Attachment object
	 * @return MailPieces\MailMultipart Returns a multipart
	 */
	public static function addAttachmentObject (MailContent $mail, MailAttachment $obj)
	{
		return MailMultipart::from("mixed", $mail, $obj);
	}

	
	/**
	 * Add several attachment objects to an email
	 * 
	 * @param MailPieces\MailContent $mail Email object
	 * @param MailPieces\MailAttachment[] $objs Attachment objects
	 * @return MailPieces\MailMultipart Returns a multipart
	 */
	public static function addAttachmentObjects (MailContent $mail, $objs)
	{
		return MailMultipart::fromArray("mixed", $mail, $objs);
	}

	
	/**
	 * Add an embedding to an email
	 * 
	 * @param MailPieces\MailContent $mail Email object
	 * @param string $embed Filepath to file to embed
	 * @param string $embedtype Mime type of the embedding
	 * @param string $cid Embedding CID
	 * @return MailPieces\MailMultipart Returns a multipart
	 */
	public static function addEmbedding (MailContent $mail, $embed, $embedtype, $cid)
	{
		return self::addEmbeddingObject($mail, self::createEmbedding($embed, $embedtype, $cid));
	}

	
	/**
	 * Add an embedding object to an email
	 * 
	 * @param MailPieces\MailContent $mail Email object
	 * @param MailPieces\MailEmbedding $obj Embedding object
	 * @return MailPieces\MailMultipart Returns a multipart
	 */
	public static function addEmbeddingObject (MailContent $mail, MailEmbedding $obj)
	{
		return MailMultipart::from("related", $mail, $obj);
	}

	
	/**
	 * Adds several embeddings to an email
	 * 
	 * @param MailPieces\MailContent $mail Email object
	 * @param string[][] $files Array of array about files to embed ; provide `file`, `cid` and `filetype` values for each file
	 * @return MailPieces\MailMultipart Returns a multipart
	 */
	public static function addEmbeddings (MailContent $mail, $embeds)
	{
		$emb = array();
		foreach ( $embeds as $e )
			$emb[] = self::createEmbedding($e['file'], $e['filetype'], $e['cid']);

		return MailMultipart::fromArray("related", $mail, $emb);
	}

	
	/**
	 * Add several embedding objects to an email
	 * 
	 * @param MailPieces\MailContent $mail Email object
	 * @param MailPieces\MailEmbedding[] $objs Embedding objects
	 * @return MailPieces\MailMultipart Returns a multipart
	 */
	public static function addEmbeddingObjects (MailContent $mail, $objs)
	{
		return MailMultipart::fromArray("related", $mail, $objs);
	}
	
	
	/**
	 * Transform a headers string to an associative array
	 * 
	 * @param string $headers String of headers
	 * @return string[] Return an array of headers
	 */
	public static function headersToArray($headers)
	{
		// if no header, return empty array
		if ( !$headers )
			return array();
			
			
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
	
	
	// transform an array of headers to a string
	public static function arrayToHeaders($headers)
	{
		// empty array : empty string returned
		if ( count($headers) == 0 )
			return "";
			
		foreach ( $headers as $kh=>$h )
			$headers[$kh] = "$kh: $h";
			
		return implode("\r\n", array_values($headers));
	}
	
	
	// get header value
	public static function getHeader($headers, $hkey)
	{
		$pheaders = self::headersToArray($headers);
		return $pheaders[$hkey];
	}
	
	
	// remove a header
	public static function removeHeader($headers, $hkey)
	{
		if ( !$headers )
			return "";
		
		if ( $hkey )
		{
			$pheaders = self::headersToArray($headers);
			if ( array_key_exists($hkey, $pheaders) )
			{
				unset($pheaders[$hkey]);
				return self::arrayToHeaders($pheaders);
			}
			else
				// if key(header name) does not exists, return unchanged headers
				return $headers;
		}
		else
			return $headers;
	}

	
	// add a new header
	public static function addHeader($headers, $h)
	{
		if ( $h )
			if ( $headers )
			{
				// get headers to an associative array
				$pheaders = self::headersToArray($headers);
								
				// key/value for header
				$hkey = trim(strstr($h, ':', true));
				$hvalue = trim(substr(strstr($h, ':'), 1));
				
				// register header
				$pheaders[$hkey] = $hvalue;
				
				// array to string
				foreach ( $pheaders as $hk=>$h )
					$pheaders[$hk] = "$hk: $h";
		
				// return string of headers
				return implode("\r\n", array_values($pheaders));
			}
			else
				return $h;
		else
			return $headers;
	}


	// add several headers to existing headers string
	public static function addHeaders($headers, $hs)
	{
		$hsarray = self::headersToArray($hs);
		
		foreach ( $hsarray as $hk=>$hval )
			$headers = self::addHeader($headers, "$hk: $hval");
			
		return $headers;
	}


	// encode a subject to UTF8 + BASE64
	public static function encodeSubject($sub)
	{
		return '=?utf-8?B?'.base64_encode($sub).'?=';
	}
	
	
	// patch the email after it has been constructed ; may be used to add tracking data to links after building process
    // callback $FUN should have the following signature ($code, $ctype, $data), where $CODE will contain the email part 
    // content, $CTYPE will be set with the email part content-type, and $DATA is the $DATA parameter of patch
	public static function patch(MailContent $mail, $fun, $data)
	{
		if ( $mail instanceof MailMultipart )
			switch ( $mail->getType() )
			{
				// if embeddings or attachements part, the text part which may be patch is in the part at index 0
				case 'mixed':
				case 'related':
					self::patch($mail->getPart(0), $fun, $data);
					break;
				
				// if alternative part, we may patch either part at index 0 or 1
				case 'alternative':
					self::patch($mail->getPart(0), $fun, $data);
					self::patch($mail->getPart(1), $fun, $data);
					break;	
			}
		
		else if ( $mail instanceof MailTextPlainContent )
			$mail->setText(call_user_func($fun, $mail->getText(), $mail->getContentType(), $data));
		
		else if ( $mail instanceof MailTextHtmlContent )
			$mail->setHtml(call_user_func($fun, $mail->getHtml(), $mail->getContentType(), $data));
			
			
		return $mail;
	}
	
	
	// minfy html code
	public static function htmlMinify($html)
	{
		$p = preg_replace('#\r\n#', ' ', $html);
		$p = preg_replace('#\n#', ' ', $p);
		$p = preg_replace('#\t#', ' ', $p);
		$p = preg_replace('#[ ]{2,}#', ' ', $p);
		
		return $p;
	}
	
				
	// convert an html string to plain text
	public static function html2plain($html)
	{
		// decode html entities
		$p = EncodingHelper::fr_entities_decode($html);
		
		// extract H1 titles : will be transformed to uppercased titles with 2 empty lines
		$p =  preg_replace_callback(
				/* ungreedy regexp */
				'~<h1[^>]*>([^<]*)</h1>~', 
		
				/* replacement callback */
				create_function(
					'$matches',
					'return "\r\n" . strtoupper($matches[1]) ."\r\n\r\n";'
				),
				
				$p
			);
		
		
		// handle newlines after some block level tags
		$p = preg_replace(array("~</div>~", "~</p>~", "~</ul>~"), "$0\r\n\r\n", $p);
		$p = preg_replace(array("~</li>~"), "$0\r\n", $p);
		
		
		// handle lists by adding - at the start of lines
		$p = preg_replace("~<li[^>]*>~", "<li>- ", $p);
		
		// remove links around images
		$p = preg_replace("~<a[^>]*>[ \r\n\t]*<img[^>]*>[ \r\n\t]*</a>~", '', $p);		
		
		// handle links : text and href are preserved 
		// ".*" in regexp does not match newlines, so we use (.|[\r\n])*?
		// "?", after a quantifier makes the regexp NOT GREEDY
		$p = preg_replace( 
				'~<a[^>]*href="([^"]*)"[^>]*>((.|[\r\n])*?)</a>~',

				"$2 ( $1 )",

				$p
			);
			
			
		// BR handling
		$p = str_replace(array("<br>", "<br/>", "<br />"), "\r\n", $p);
		
		
		// remove all tags
		$p = strip_tags($p);
		
		// remove tabs (replaced by one space)
		$p = str_replace("\t", " ", $p);
		
		// handle unbreakable spaces characters
		$p = str_replace("\xc2\xa0", " ", $p);
		
		// remove spaces at the beginning of lines
		$p = preg_replace("~\n[ ]+~", "\n", $p);
		
		// no more than 2 consecutive newlines
		$p = preg_replace("~(\r\n){3,}~", "\r\n\r\n", $p);
		
		// remove spaces at begin/end
		return trim($p);
	}
	
	
	// convert a plain text string to html
	public static function plain2html($plain)
	{
		// encode entities
		$plain = EncodingHelper::fr_entities_encode($plain);
		
		// handle < and > in plain text
		$plain = str_replace("<", "&lt;", str_replace(">", "&gt;", $plain));
		
		// if '**' set a B tag
		$plain = preg_replace('~\*\*([^*]*)\*\*~', '<b>$1</b>', $plain);
		
		// if '==' set a red color
		$plain = preg_replace('~==([^=]*)==~', '<b style="color:#DD0000;">$1</b>', $plain);
		
		// create links
		$plain = preg_replace(
				'!(http(?:s)?://[a-zA-Z0-9./_%+~-]*)(\?|\#)?[a-zA-Z0-9._?#&/=%+-;]*!',
		
				'<a href="$0">$1</a>',
		
				$plain
			);

		
		// handle newlines
		return self::htmlMinify(str_replace("\n", "<br>", str_replace("\r\n", "\n", $plain)));
	}	


	// add required technical headers (such as MIME version)
	public static function render(MailContent $mail)
	{
		$mail->addCustomHeader("MIME-Version: 1.0");
		return $mail;
	}
	
	
// ----- STATIC -----]



// [----- PUBLIC -----

	// constructor, with an email sending strategy name, and an array of parameters for the strategy
	public function __construct($mailsender_name, $params = NULL)
	{
		$this->setMailSender($mailsender_name, $params);
	}
	

	// set the email sending strategy ; returns TRUE if init OK, FALSE otherwise ; to know the error, get strategy object with getMailSender and call getMessage()
	public function setMailSender($mailsender_name, $params = NULL)
	{
		$this->mailsender = MailSender::factory($mailsender_name, $params);
		
		return $this->mailsender->ready();
	}
	
	
	// close email sending strategy (e.g. closing SMTP connections)
	public function destruct()
	{
		return $this->getMailSender()->destruct();
	}
	

	// get current email sending strategy, or create a default one
	public function getMailSender()
	{
		if ( is_null($this->mailsender) )
			$this->mailsender = MailSender::factory(MailSender::PHPMAIL);

		return $this->mailsender;
	}
	
	
	// simple method call to send an email with content (either plain text or html) and optionnal attachments
	public function expressSendmail($content, $from, $to, $subject, $attachments = array(), $destruct = false)
	{
		// detect content-type
		if ( preg_match('<(a|strong|em|b|table|div|span|p)>', $content) )
			$mailcontent = self::addTextHtmlFromHtml($content);
		else
			$mailcontent = self::addTextHtmlFromText($content);
			
			
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
			$mailcontent = self::addAttachments($mailcontent, $atts);
		}
		
		
		// send the email
		return $this->sendmail($mailcontent, $from, $to, $subject, $destruct);
	}
	
	
	// send an email built with static building method of Mailer
	public function sendmail(MailContent $mail, $from, $to, $subject, $destruct = false)
	{
		// add required technical headers
		self::render($mail);
		
		return $this->sendmail_raw($to, $subject, $mail->getContent(), 
								self::addHeader($mail->getFullHeaders(),"From: $from"), $destruct);
	}
	
	
	// send raw mail
	public function sendmail_raw($to, $subject, $mail, $headers, $destruct = false)
	{
		// if recipients is not an array, converting it to an array of recipients
		if ( !is_array($to) )
			$to = $to ? explode(',', $to) : array();
						
		$st = array();
		foreach ( $to as $recipient )
			if ( $s = $this->getMailSender()->send($recipient, $subject, $mail, $headers) )
				$st[] = $s;

		if ( $destruct )
			$this->destruct();

		// return FALSE if ok, a string if an error occured
		return count($st) ? implode("\n", $st) : false;
	}

// ----- PUBLIC -----]
	
}
?>