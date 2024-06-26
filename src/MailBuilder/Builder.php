<?php
/**
 * Builder
 *
 * @author Pierre - dev@nettools.ovh
 * @license MIT
 */



// namespace
namespace Nettools\Mailing\MailBuilder;


// clauses use
use \Nettools\Core\Helpers\EncodingHelper;





/**
 * Helper class with static low-level function to create an email 
 */
final class Builder {

	/* Default html template */
	const TEMPLATE_PLACEHOLDER = '%content%';
	const DEFAULT_TEMPLATE = self::TEMPLATE_PLACEHOLDER;
	
	
	
	
	/**
	 * Create a email with a text/plain part and a text/html part
	 *
	 * @param string $plain Plain text part
	 * @param string $html HTML text part
	 * @return Multipart Returns a multipart/alternative part
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
	 * @return Multipart Returns a multipart/alternative part
	 */
	public static function addTextHtmlFromHtml ($html, $htmltemplate = self::DEFAULT_TEMPLATE)
	{
		$html = str_replace(self::TEMPLATE_PLACEHOLDER, $html, $htmltemplate);
		return self::addTextHtml(self::html2plain($html), $html);
	}
	
	
	
	/**
	 * Create a email with a text/plain part ; the text/html  part is built from the text/plain part
	 *
	 * @param string $plain Plain text part
	 * @param string $htmltemplate Template for html part ; use `%content%` in the template to set the placeholder for content
	 * @return Multipart Returns a multipart/alternative part
	 */
	public static function addTextHtmlFromText ($plain, $htmltemplate = self::DEFAULT_TEMPLATE)
	{
		return self::addTextHtml(
								str_replace(self::TEMPLATE_PLACEHOLDER, $plain, self::html2plain($htmltemplate)), 
								str_replace(self::TEMPLATE_PLACEHOLDER, self::plain2html($plain), $htmltemplate)
							);
	}
	
	
	
	/**
	 * Create a multipart/alternative part
	 * 
	 * The text/plain and text/html part are in fact "childs" of a multipart/alternative part
	 *
	 * @param Content $alt1 Part 1
	 * @param Content $alt2 Part 2
	 * @return Multipart Returns a multipart/alternative part
	 */
	public static function addAlternativeObject (Content $alt1, Content $alt2)
	{
		return Multipart::from("alternative", $alt1, $alt2);
	}
	
	
	
	/**
	 * Create a text/plain part
	 * 
	 * @return TextPlainContent The plain text part
	 */
	public static function createText ($text)
	{
		return new TextPlainContent($text);
	}
	
	
	
	/**
	 * Create a text/html part
	 * 
	 * @return TextHtmlContent The HTML part
	 */
	public static function createHtml ($html)
	{
		return new TextHtmlContent($html);
	}
	
	
	
	/**
	 * Create an embedding object
	 * 
	 * @param string $embed File path to the file to embed or data string
	 * @param string $embedtype Mime type of the embedding
	 * @param string $cid Content-ID for embedding
     * @param bool $ignoreCache Indicates whether the attachments cache must be ignored or used 
	 * @param bool $isFile True if $embed is a file path, false if it's a data string
	 * @return Embedding Returns a embedding part
	 */
	public static function createEmbedding($embed, $embedtype, $cid, $ignoreCache = false, $isFile = true)
	{
		return new Embedding($embed, $embedtype, $cid, $ignoreCache, $isFile);
	}
	
	
	
	/**
	 * Create an attachment object
	 * 
	 * @param string $file File path to the file to attach or data string
	 * @param string $filename File name used in the email (will appear in the email client of the recipient)
	 * @param string $filetype Mime type of the attachment
     * @param bool $ignoreCache Indicates whether the attachments cache must be ignored or used 
	 * @param bool $isFile True if $embed is a file path, false if it's a data string
	 * @return Attachment Returns a embedding part
	 */
	public static function createAttachment($file, $filename, $filetype, $ignoreCache = false, $isFile = true)
	{
		return new Attachment($file, $filename, $filetype, $ignoreCache, $isFile);
	}
	
	
	
	/**
	 * Adds several attachments to an email
	 * 
	 * @param Content $mail Email object
	 * @param string[][] $files Array of array about files to attach ; provide `file`, `filename` and `filetype` values for each file
     * @param bool $ignoreCache Indicates whether the attachments cache must be ignored or used 
	 * @param bool $isFile True if 'file' value in $files array is a file path, false if it's a data string
	 * @return Multipart Returns a multipart
	 */
	public static function addAttachments (Content $mail, array $files, $ignoreCache = false, $isFile = true)
	{
		$att = array();
		foreach ( $files as $f )
			$att[] = self::createAttachment($f['file'], $f['filename'], $f['filetype'], $ignoreCache, $isFile);
			
		return self::addAttachmentObjects($mail, $att);
	}
	
	
	
	/**
	 * Add an attachment to an email
	 * 
	 * @param Content $mail Email object
	 * @param string $file Filepath to file to attach
	 * @param string $filename Filename to display to the user
	 * @param string $filetype Mime type of the attachment
     * @param bool $ignoreCache Indicates whether the attachments cache must be ignored or used 
	 * @param bool $isFile True if 'file' value in $files array is a file path, false if it's a data string
	 * @return Multipart Returns a multipart
	 */
	public static function addAttachment (Content $mail, $file, $filename, $filetype, $ignoreCache = false, $isFile = true)
	{
		return self::addAttachmentObject($mail, self::createAttachment($file, $filename, $filetype, $ignoreCache, $isFile));
	}

	
	
	/**
	 * Add an attachment object to an email
	 * 
	 * @param Content $mail Email object
	 * @param Attachment $obj Attachment object
	 * @return Multipart Returns a multipart
	 */
	public static function addAttachmentObject (Content $mail, Attachment $obj)
	{
		return Multipart::from("mixed", $mail, $obj);
	}

	
	
	/**
	 * Add several attachment objects to an email
	 * 
	 * @param Content $mail Email object
	 * @param Attachment[] $objs Attachment objects
	 * @return Multipart Returns a multipart
	 */
	public static function addAttachmentObjects (Content $mail, array $objs)
	{
		return Multipart::fromArray("mixed", $mail, $objs);
	}

	
	
	/**
	 * Add an embedding to an email
	 * 
	 * @param Content $mail Email object
	 * @param string $embed Filepath to file to embed
	 * @param string $embedtype Mime type of the embedding
	 * @param string $cid Embedding CID
     * @param bool $ignoreCache Indicates whether the attachments cache must be ignored or used 
	 * @param bool $isFile True if $embed is a file path, false if it's a data string
	 * @return Multipart Returns a multipart
	 */
	public static function addEmbedding (Content $mail, $embed, $embedtype, $cid, $ignoreCache = false, $isFile = true)
	{
		return self::addEmbeddingObject($mail, self::createEmbedding($embed, $embedtype, $cid, $ignoreCache, $isFile));
	}

	
	
	/**
	 * Add an embedding object to an email
	 * 
	 * @param Content $mail Email object
	 * @param Embedding $obj Embedding object
	 * @return Multipart Returns a multipart
	 */
	public static function addEmbeddingObject (Content $mail, Embedding $obj)
	{
		return Multipart::from("related", $mail, $obj);
	}

	
	
	/**
	 * Adds several embeddings to an email
	 * 
	 * @param Content $mail Email object
	 * @param string[][] $files Array of array about files to embed ; provide `file`, `cid` and `filetype` values for each file
     * @param bool $ignoreCache Indicates whether the attachments cache must be ignored or used 
	 * @param bool $isFile True if 'file' value in $files array is a file path, false if it's a data string
	 * @return Multipart Returns a multipart
	 */
	public static function addEmbeddings (Content $mail, array $embeds, $ignoreCache = false, $isFile = true)
	{
		$emb = array();
		foreach ( $embeds as $e )
			$emb[] = self::createEmbedding($e['file'], $e['filetype'], $e['cid'], $ignoreCache, $isFile);

		return Multipart::fromArray("related", $mail, $emb);
	}

	
	
	/**
	 * Add several embedding objects to an email
	 * 
	 * @param Content $mail Email object
	 * @param Embedding[] $objs Embedding objects
	 * @return Multipart Returns a multipart
	 */
	public static function addEmbeddingObjects (Content $mail, array $objs)
	{
		return Multipart::fromArray("related", $mail, $objs);
	}
	
	

	/**
	 * Patch the email after it has been constructed.
	 * 
	 * May be used to add tracking data to links after building process.
	 * Callback `$fun` should have the following signature :
	 *
	 * - $code : will contain the email part 
	 * - $ctype : will be set with the email part content-type
	 * - $data : `$data` parameter of patch method ; useful to transmit work data to callback
	 * 
	 * @param Content $mail Email to process
	 * @param callable $fun Callback (see method summary for it's parameters)
	 * @param mixed $data Data to pass to the callback
	 * @return Content Returns the $mail parameters, with it's content updated
	 */
    public static function patch(Content $mail, $fun, $data)
	{
		if ( $mail instanceof Multipart )
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
		
		else if ( $mail instanceof TextPlainContent )
			$mail->setText(call_user_func($fun, $mail->getText(), $mail->getContentType(), $data));
		
		else if ( $mail instanceof TextHtmlContent )
			$mail->setHtml(call_user_func($fun, $mail->getHtml(), $mail->getContentType(), $data));
			
			
		return $mail;
	}
	
	
	
	/**
	 * Minfy html code
	 * 
	 * @param string $html HTML text to minify
	 * @return string Returns a string with no newlines, tabs and removes duplicate spaces
	 */
	public static function htmlMinify($html)
	{
		$p = preg_replace('#\r\n#', ' ', $html);
		$p = preg_replace('#\n#', ' ', $p);
		$p = preg_replace('#\t#', ' ', $p);
		$p = preg_replace('#[ ]{2,}#', ' ', $p);
		
		return $p;
	}
	
	
				
	/** 
	 * Convert an html string to plain text, removing tags
	 * 
	 * @param string $html HTML string
	 * @return string Returns plain text
	 */
	public static function html2plain($html)
	{
		// decode html entities
		$p = EncodingHelper::fr_entities_decode($html);
		
		// extract H1 titles : will be transformed to uppercased titles with 2 empty lines
		$p =  preg_replace_callback(
				/* ungreedy regexp */
				'~<h1[^>]*>([^<]*)</h1>~', 
		
				/* replacement callback */
				function($matches)
				{
					return "\r\n" . strtoupper($matches[1]) ."\r\n\r\n";
				},
				
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
	
	
	
	/**
	 * Convert a plain text string to html, ** replaced by B tags, == by red tags
	 *
	 * @param string Plain text to convert to HTML formatting
	 * @return string HTML formatted text
	 */
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
}
?>