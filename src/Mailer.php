<?php

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
use \Nettools\Mailing\MailSender;
use \Nettools\Core\Helpers\EncodingHelper;
use \Nettools\Core\Containers\Cache;
use \Nettools\Core\Helpers\FileHelper;



// classe pour préparation d'un mail et envoi
final class Mailer {
// [----- MEMBRES PROTEGES -----

	// stratégie pour envoi des mails
	protected $mailsender = NULL;
	
// ----- MEMBRES PROTEGES -----]



// [----- MEMBRES STATIQUES -----
	
	// cache pour PJ et images incorporées
	protected static $cacheAttachments = NULL;
	protected static $cacheEmbeddings = NULL;
	
	// instance singleton
	protected static $defaultMailer = NULL;
	
	
	// obtenir un mailer par défaut
	public static function getDefault()
	{
		if ( is_null(self::$defaultMailer) )
			self::$defaultMailer = new Mailer(MailSender::PHPMAIL, NULL);
		
		return self::$defaultMailer;
	}


	// obtenir le cache des PJ
	public static function getAttachmentsCache()
	{
		if ( is_null(self::$cacheAttachments) )
			self::$cacheAttachments = new \Nettools\Core\Containers\Cache();
			
		return self::$cacheAttachments;
	}
	
	
	// obtenir le cache des images incorporées
	public static function getEmbeddingsCache()
	{
		if ( is_null(self::$cacheEmbeddings) )
			self::$cacheEmbeddings = new \Nettools\Core\Containers\Cache();
			
		return self::$cacheEmbeddings;
	}
	
	
	// ajouter un mail standard text/plain avec alternative html
	public static function addTextHtml ($plain, $html)
	{
		return self::addAlternativeObject(self::createText($plain), self::createHtml($html));
	}
	
	
	// ajouter un mail standard text/plain avec alternative html ; la partie text/plain est convertie de la partie html
	public static function addTextHtmlFromHtml ($html, $htmltemplate = "%content%")
	{
		$html = str_replace("%content%", $html, $htmltemplate);
		return self::addTextHtml(self::html2plain($html), $html);
	}
	
	
	// ajouter un mail standard text/plain avec alternative html ; la partie text/html est convertie de la partie plain
	public static function addTextHtmlFromText ($plain, $htmltemplate = "%content%")
	{
		return self::addTextHtml(
								str_replace("%content%", $plain, self::html2plain($htmltemplate)), 
								str_replace("%content%", self::plain2html($plain), $htmltemplate)
							);
	}
	
	
	// ajouter un mail standard text/plain avec alternative html
	public static function addAlternativeObject (MailContent $alt1, MailContent $alt2)
	{
		return MailMultipart::from("alternative", $alt1, $alt2);
	}
	
	
	// créer un text/plain
	public static function createText ($text)
	{
		return new MailTextPlainContent($text);
	}
	
	
	// créer un text/html
	public static function createHtml ($html)
	{
		return new MailTextHtmlContent($html);
	}
	
	
	// créer objet incorporé
	public static function createEmbedding($embed, $embedtype, $cid)
	{
		return new MailEmbedding($embed, $embedtype, $cid);
	}
	
	
	// créer piece jointe
	public static function createAttachment($file, $filename, $filetype)
	{
		return new MailAttachment($file, $filename, $filetype);
	}
	
	
	// ajouter des pièce-jointes
	public static function addAttachments (MailContent $mail, $files)
	{
		$att = array();
		foreach ( $files as $f )
			$att[] = self::createAttachment($f['file'], $f['filename'], $f['filetype']);
			
		return self::addAttachmentObjects($mail, $att);
	}
	
	
	// ajouter pièce-jointe
	public static function addAttachment (MailContent $mail, $file, $filename, $filetype)
	{
		return self::addAttachmentObject($mail, self::createAttachment($file, $filename, $filetype));
	}

	
	// ajouter pièce-jointe
	public static function addAttachmentObject (MailContent $mail, MailAttachment $obj)
	{
		return MailMultipart::from("mixed", $mail, $obj);
	}

	
	// ajouter des pièces-jointes, déjà construites sous forme d'AttachmentObjet
	public static function addAttachmentObjects (MailContent $mail, $objs)
	{
		return MailMultipart::fromArray("mixed", $mail, $objs);
	}

	
	// ajouter un objet incorporé
	public static function addEmbedding (MailContent $mail, $embed, $embedtype, $cid)
	{
		return self::addEmbeddingObject($mail, self::createEmbedding($embed, $embedtype, $cid));
	}

	
	// ajouter un objet incorporé
	public static function addEmbeddingObject (MailContent $mail, MailEmbedding $obj)
	{
		return MailMultipart::from("related", $mail, $obj);
	}

	
	// ajouter des objets incorporés
	public static function addEmbeddings (MailContent $mail, $embeds)
	{
		$emb = array();
		foreach ( $embeds as $e )
			$emb[] = self::createEmbedding($e['file'], $e['filetype'], $e['cid']);

		return MailMultipart::fromArray("related", $mail, $emb);
	}

	
	// ajouter des pièces-jointes, déjà construites sous forme d'AttachmentObjet
	public static function addEmbeddingObjects (MailContent $mail, $objs)
	{
		return MailMultipart::fromArray("related", $mail, $objs);
	}
	
	
	// transformer chaine d'en-têtes en tableau associatif
	public static function headersToArray($headers)
	{
		// si chaine vide, renvoyer tableau vide
		if ( !$headers )
			return array();
			
			
		// opérer le unfolding des en-têtes ; en effet, certains en-têtes peuvent être sur plusieurs lignes. Dans ce cas, les lignes suivantes
		// débutent obligatoirement par au moins un espace/tabulation
		$pheaders = array();
		$headers = explode("\n", str_replace("\r\n", "\n", $headers));
		$last = NULL;
		foreach ( $headers as $line )
		{
			// si pas trouvé de séparateur en-tete: valeur, alors on est sur une ligne à rajouter à l'en-tete précédent
			// (cas où pour ne pas dépasser 78 caractères par ligne d'en-tete, on fait un CRLF + espace (folding)
			if ( preg_match("/^[ ]|\t/", $line) && $last )
				$pheaders[$last] .= "\r\n" . $line; // conserver le folding dans la valeur, afin de l'obtenir à nouveau lors de l'aplatissement
			else
			{
				// créer un nouvel en-tete clef=valeur
				$line = explode(':', $line, 2);
				$last = trim($line[0]);
				$pheaders[$last] = trim($line[1]);
			}
		}
		
		
		return $pheaders;
	}
	
	
	// aplatir un tableau d'en-tête en chaine
	public static function arrayToHeaders($headers)
	{
		// si tableau vide, renvoyer chaine vide
		if ( count($headers) == 0 )
			return "";
			
		foreach ( $headers as $kh=>$h )
			$headers[$kh] = "$kh: $h";
			
		return implode("\r\n", array_values($headers));
	}
	
	
	// obtenir un en-tête 
	public static function getHeader($headers, $hkey)
	{
		$pheaders = self::headersToArray($headers);
		return $pheaders[$hkey];
	}
	
	
	// supprimer un en-tête
	public static function removeHeader($headers, $hkey)
	{
		// si chaine vide, renvoyer à l'identique
		if ( !$headers )
			return "";
		
		// si clef fournie	
		if ( $hkey )
		{
			$pheaders = self::headersToArray($headers);
			if ( array_key_exists($hkey, $pheaders) )
			{
				unset($pheaders[$hkey]);
				return self::arrayToHeaders($pheaders);
			}
			else
				// si clef n'existe pas, renvoyer à l'identique
				return $headers;
		}
		else
			return $headers;
	}

	
	// rajouter un en-tête à des en-tetes déjà existants 
	public static function addHeader($headers, $h)
	{
		if ( $h )
			if ( $headers )
			{
				// en-têtes en tableau associatif
				$pheaders = self::headersToArray($headers);
								
				// prendre clef nouvel en-tête
				$hkey = trim(strstr($h, ':', true));
				$hvalue = trim(substr(strstr($h, ':'), 1));
				
				// indexer et ajouter
				$pheaders[$hkey] = $hvalue;
				
				// aplatir le tableau
				foreach ( $pheaders as $hk=>$h )
					$pheaders[$hk] = "$hk: $h";
		
				// renvoyer sous forme de chaine
				return implode("\r\n", array_values($pheaders));
			}
			else
				return $h;
		else
			return $headers;
	}


	// rajouter des en-têtes à des en-tetes déjà existants (gestion du saut de ligne)
	public static function addHeaders($headers, $hs)
	{
		$hsarray = self::headersToArray($hs);
		
		foreach ( $hsarray as $hk=>$hval )
			$headers = self::addHeader($headers, "$hk: $hval");
			
		return $headers;
	}


	// encore un objet de mail en UTF8 + BASE64
	public static function encodeSubject($sub)
	{
		return '=?utf-8?B?'.base64_encode($sub).'?=';
	}
	
	
	// traiter a posteriori le contenu des emails ; utiles pour rajouter un tracking dans les liens, par exemple
	// fun doit etre une fonction avec la signature ($code, $ctype, $data), avec $code = texte, $ctype = encoding type
	public static function patch(MailContent $mail, $fun, $data)
	{
		if ( $mail instanceof MailMultipart )
			switch ( $mail->getType() )
			{
				// si partie pieces-jointes ou images incorporées, la partie éventuellement textuelle est dans partie n°0
				case 'mixed':
				case 'related':
					self::patch($mail->getPart(0), $fun, $data);
					break;
				
				// si partie alternative, ce qui nous intéresse est dans la partie text ET html (n°0 et 1)
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
	
	
	// minifier le code HTML
	public static function htmlMinify($html)
	{
		$p = preg_replace('#\r\n#', ' ', $html);
		$p = preg_replace('#\n#', ' ', $p);
		$p = preg_replace('#\t#', ' ', $p);
		$p = preg_replace('#[ ]{2,}#', ' ', $p);
		
		return $p;
	}
	
				
	// convertir un mail formaté en HTML en texte 
	public static function html2plain($html)
	{
		// décoder les accents
		$p = EncodingHelper::fr_entities_decode($html);
		
		// traiter titre
		$p =  preg_replace_callback(
				/* considérer l'intérieur des tags H1 (? = not greedy) */
				'~<h1[^>]*>([^<]*)</h1>~', 
		
				/* fonction de remplacement : mise en majuscules du contenu du tag H1*/
				create_function(
					'$matches',
					'return "\r\n" . strtoupper($matches[1]) ."\r\n\r\n";'
				),
				
				$p
			);
		
		
		// traiter saut de lignes après certains tags
		$p = preg_replace(array("~</div>~", "~</p>~", "~</ul>~"), "$0\r\n\r\n", $p);
		$p = preg_replace(array("~</li>~"), "$0\r\n", $p);
		
		
		// traiter énumérations avec "- " en préfixe ; on rajoute un - au début de la ligne ; striptags enlèvera le tag
		$p = preg_replace("~<li[^>]*>~", "<li>- ", $p);
		
		// supprimer les liens autour d'images
		$p = preg_replace("~<a[^>]*>[ \r\n\t]*<img[^>]*>[ \r\n\t]*</a>~", '', $p);		
		
		// préserver url (car sinon, on fait sauter les tags, et donc le HREF est perdu !)
		// ".*" ne considère pas les sauts de ligne, on utilise donc (.|[\r\n])*?
		// le "?", placé après un quantificateur, rend l'expression régulière NOT GREEDY, donc on ne va bien 
		// que jusqu'au prochain "</a>"
		$p = preg_replace( 
				'~<a[^>]*href="([^"]*)"[^>]*>((.|[\r\n])*?)</a>~',

				"$2 ( $1 )",

				$p
			);
			
			
		// traiter sauts de lignes BR
		$p = str_replace(array("<br>", "<br/>", "<br />"), "\r\n", $p);
		
		
		// enlever les tags
		$p = strip_tags($p);
		
		// enlever les tabulations
		$p = str_replace("\t", " ", $p);
		
		// remplacer les espaces insécables
		$p = str_replace("\xc2\xa0", " ", $p);
		
		// traiter retours à la ligne windows
		//$p = str_replace("\r\n", "\n", $p);
		
		// enlever les espaces en tête de ligne
		$p = preg_replace("~\n[ ]+~", "\n", $p);
		
		// pas plus de 2 sauts de lignes consécutifs
		$p = preg_replace("~(\r\n){3,}~", "\r\n\r\n", $p);
		
		// enlever les tags
		return trim($p);
	}
	
	
	// convertit un message text/plain en text/html
	public static function plain2html($plain)
	{
		// encoder les accents et plus généralement toutes les entités HTML
		$plain = EncodingHelper::fr_entities_encode($plain);
		
		// traiter < et > dans le code plain
		$plain = str_replace("<", "&lt;", str_replace(">", "&gt;", $plain));
		
		// traiter mise en gras
		$plain = preg_replace('~\*\*([^*]*)\*\*~', '<b>$1</b>', $plain);
		
		// traiter mise en gras + rouge
		$plain = preg_replace('~==([^=]*)==~', '<b style="color:#DD0000;">$1</b>', $plain);
		
		// traiter les liens
		$plain = preg_replace(
				'!(http(?:s)?://[a-zA-Z0-9./_%+~-]*)(\?|\#)?[a-zA-Z0-9._?#&/=%+-;]*!',
		
				'<a href="$0">$1</a>',
		
				$plain
			);

		
		// traiter sauts de lignes
		return self::htmlMinify(str_replace("\n", "<br>", str_replace("\r\n", "\n", $plain)));
	}	


	// ajouter les en-tetes techniques obligatoires, tels que la version MIME, et plus généralement préparer le mail
	// pour être envoyé (ou mis en file d'envois)
	public static function render(MailContent $mail)
	{
		$mail->addCustomHeader("MIME-Version: 1.0");
		return $mail;
	}
	
	
// ----- MEMBRES STATIQUES -----]



// [----- MEMBRES PUBLICS -----

	// constructeur, avec stratégie à définir
	public function __construct($mailsender_name, $params = NULL)
	{
		$this->setMailSender($mailsender_name, $params);
	}
	

	// définir la stratégie ; renvoie TRUE si initialisation OK, FALSE sinon ; dans ce cas, il faut obtenir l'objet
	// stratégie par getMailSender et interroger getMessage()
	public function setMailSender($mailsender_name, $params = NULL)
	{
		$this->mailsender = MailSender::factory($mailsender_name, $params);
		
		return $this->mailsender->ready();
	}
	
	
	// nettoyer le processus d'envoi (utile pour fermer les connexions smtp laissées ouvertes)
	public function destruct()
	{
		return $this->getMailSender()->destruct();
	}
	

	// obtenir la stratégie active, ou en créer une par défaut
	public function getMailSender()
	{
		if ( is_null($this->mailsender) )
			$this->mailsender = MailSender::factory(MailSender::PHPMAIL);

		return $this->mailsender;
	}
	
	
	// envoyer rapidement un mail (plain ou html) et des pièces-jointes
	public function expressSendmail($content, $from, $to, $subject, $attachments = array(), $destruct = false)
	{
		// détecter présence html
		if ( preg_match('<(a|strong|em|b|table|div|span|p)>', $content) )
			$mailcontent = self::addTextHtmlFromHtml($content);
		else
			$mailcontent = self::addTextHtmlFromText($content);
			
			
		// si pj, préparer structure
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
		
		
		// envoyer le mail
		return $this->sendmail($mailcontent, $from, $to, $subject, $destruct);
	}
	
	
	// envoyer le mail construit
	public function sendmail(MailContent $mail, $from, $to, $subject, $destruct = false)
	{
		// ajouter les en-tête techniques obligatoires
		self::render($mail);
		
		return $this->sendmail_raw($to, $subject, $mail->getContent(), 
								self::addHeader($mail->getFullHeaders(),"From: $from"), $destruct);
	}
	
	
	// envoyer le mail sous une forme brute
	public function sendmail_raw($to, $subject, $mail, $headers, $destruct = false)
	{
		// si destinataire n'est pas sous forme de tableau, convertir
		if ( !is_array($to) )
			$to = $to ? explode(',', $to) : array();
						
		$st = array();
		foreach ( $to as $recipient )
			if ( $s = $this->getMailSender()->send($recipient, $subject, $mail, $headers) )
				$st[] = $s;

		if ( $destruct )
			$this->destruct();

		// send renvoie FALSE si ok, chaine si erreurs
		return count($st) ? implode("\n", $st) : false;
	}

// ----- MEMBRES PUBLICS -----]
	
}
?>