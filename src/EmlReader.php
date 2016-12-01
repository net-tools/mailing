<?php

// namespace
namespace Nettools\Mailing;


// clauses use
use \Nettools\Mailing\Mailer;
use \Nettools\Mailing\MailPieces\MailTextPlainContent;
use \Nettools\Mailing\MailPieces\MailTextHtmlContent;
use \Nettools\Mailing\MailPieces\MailMultipart;
use \Nettools\Mailing\MailPieces\MailContent;
use \Nettools\Mailing\MailPieces\MailAttachment;
use \Nettools\Mailing\MailPieces\MailEmbedding;




// classe pour convertir un fichier EML en MailPieces
class EmlReader
{
	// dernière erreur de décryptage rencontrée
	static public $lastError = NULL;
	
	
	// consigner erreur
	static protected function _error($msg)
	{
		self::$lastError = $msg;
		return NULL;
	}
	
	
	// nettoyer les fichiers temporaires utilisés pour les pj et embeddings
	static function destroy(MailContent $mail)
	{
		// traiter par récursion
		if ( $mail instanceof \Nettools\Mailing\MailPieces\MailMultipart )
		{
			$partsl = $mail->getCount();
			for ( $i = 0 ; $i < $partsl ; $i++ )
				self::destroy($mail->getPart($i));
		}
		
		
		// si nettoyage nécessaire
		if ( in_array(get_class($mail), array('Nettools\Mailing\MailPieces\MailAttachment', 'Nettools\Mailing\MailPieces\MailEmbedding')) )
		{
			$f = $mail->getFile();
			if ( file_exists($f) )
				unlink($f);
		}
	}
	
	
	// décoder un en-tête ; si value = NULL, on renvoie la première valeur (avant ';') ; si value est une chaîne
	// on renvoie la valeur de ce paramètre (ex. charset)
	static function decodeHeader($header, $value = NULL)
	{
		if ( !$header )
			return NULL;
			
		// obtenir parties d'un en-tete (text/plain; charset="UTF-8"; format=flowed)
		$regs = preg_split('/;[\s]+/', $header);
		
		// vérifier découpage
		if ( count($regs) == 0 )
			return NULL;
			
		// si on veut la première valeur
		if ( is_null($value) )
			return trim($regs[0]);
		
		// si on veut une valeur en particulier
		foreach ( $regs as $part )
		{
			// si trouvé valeur ; renvoyer le reste de la chaine, éventuellement expurgée des "
			$p = strpos($part, $value . '=');
			if ( $p === 0 )
				return str_replace('"', '', substr(strstr($part, '='),1));
		}
		
		return NULL;
	}
	
	
	// décoder un body
	static function decodeBody($body, $encoding)
	{
		// si aucune info Content-Transfer-Encoding, renvoyer tel quel le body
		if ( !$encoding )
			return $body;
			
		switch ( $encoding )
		{
			// les encoding 7bit ou 8bit n'encodent rien, c'est juste pour préciser au SMTP le type de données qui va suivre, et garantir
			// éventuellement l'absence d'un caractère avec un 8eme bit activé
			case '7bit':
			case '8bit':
				return $body;
			
			case 'quoted-printable':
				return quoted_printable_decode($body);
				break;
				
			case 'base64':
				return base64_decode(/*str_replace('_', '/', str_replace('-', '+', */$body);
		}
		
		return self::_error('Content-Transfer-Encoding non pris en charge');
	}
	
	
	// décoder charset
	static function decodeCharset($body, $charset)
	{
		if ( !$charset ) 
			return $body;

		// convertir
		if ( strtolower($charset) != 'utf-8' ) 		
		{	
			$s = iconv(strtolower($charset), 'utf-8', $body);
			if ( $s === FALSE )
				return self::_error("Décodage de '$charset' impossible.");
			else
				return $s;
		}
		else
			return $body;
	}
	
	
	// décoder le body en fonciton du charset indiqué dans le content-type
	static function decodeCharsetFromContentTypeHeader($body, $ct)
	{
		// obtenir charset depuis le content-type
		$charset = self::decodeHeader($ct, 'charset');				

		// décoder
		return self::decodeCharset($body, $charset);
	}
	
	
	// décoder le body et en fournir un MailTextPlainContent, MailHtmlPlainContent, MailAttachment, MailEmbedding, selon content-disposition
	static function decodeContent($body, $headers, $contentType)
	{
		// prendre le header content-disposition (première partie seulement, avant ';' éventuel)
		$contentDisposition = self::decodeHeader($headers['Content-Disposition']);
		
		// si content-id, forcer content-disposition à inline (cas où content-disposition:inline non présent)
		if ( self::decodeHeader($headers['Content-ID']) )
			$contentDisposition = 'inline';
			
				
		// si pas de content-disposition, ce n'est pas une pj, mais une partie text/plain ou text/html
		if ( !$contentDisposition )
		{
			switch ( $contentType )
			{
				case 'text/plain' :
					return new MailTextPlainContent($body);
				case 'text/html' :
					return new MailTextHtmlContent($body);
			}
			
			return self::_error("Content-type '$contentType' non pris en charge.");
		}
		else
			// si PJ ou image incorporée
			if ( in_array($contentDisposition, ['attachment', 'inline']) )
			{
				// créer un fichier temporaire et écrire dedans le body
				$fname = tempnam(/*$_SERVER['DOCUMENT_ROOT']*/sys_get_temp_dir(), $contentDisposition);
				$f = fopen($fname, 'w');
				fwrite($f, $body);
				fclose($f);
				
				if ( $contentDisposition == 'attachment' )
					return new MailAttachment($fname, basename($fname), $contentType, true);
				else
				{
					// si image incorporée, extraire Content-ID
					$cid = self::decodeHeader($headers['Content-ID']);
					if ( !$cid )
						return self::_error('Content-ID introuvable.');
						
					return new MailEmbedding($fname, $contentType, trim(str_replace(array('<', '>', '"'), '', $cid)), true);
				}
			}
			else
				return self::_error("Body avec Content-Disposition '$contentDisposition' non pris en charge");
	}
	
	
	// construire d'après content-type
	static function fromContentType($ct, $headers, $body)
	{
		// text/plain; charset="utf-8"; format=flowed
		// obtenir content-type (text/plain)
		// prendre avant spécification éventuelle charset ou boundary (text/plain) ; par construction, le content-type est tj le premier
		$contentType = self::decodeHeader($ct);
		
		
		// traiter selon content-type
		switch ( $contentType )
		{
			case 'text/plain' : 
			case 'text/html' : 
				// décoder transfer-encoding
				$decodedBody = self::decodeBody($body, self::decodeHeader($headers['Content-Transfer-Encoding']));
				if (!$decodedBody )
					return NULL;
					
				// décoder vers utf-8 si nécessaire, en fonction du charset indiqué
				$decodedBody = self::decodeCharsetFromContentTypeHeader($decodedBody, $ct);
				if (!$decodedBody )
					return NULL;

				// obtenir objet
				return self::decodeContent($decodedBody, $headers, $contentType);


			case 'multipart/alternative' : 
			case 'multipart/mixed' : 
			case 'multipart/related' : 
				
				// lire boundary
				$boundary = self::decodeHeader($ct, 'boundary');
				if ( !$boundary )
					return self::_error("Décodage boundary de '$contentType' impossible.");
				
				
				// découper les 2 parties ; on obtient 3 valeurs, car le body commence direct par le séparateur ; on ignore cette valeur vide
				// on tient compte du fait que le dernier séparateur se termine par '--', et qu'à la fin de chaque
				// ligne de séparation, il y a un retour à la ligne ; on n'enlève que celui-là qui est par construction
				// toujours présent (sauf si dernier séparateur --) ; on tient compte du fait qu'il peut s'agir de 
				// CRLF ou LF
				$parts = preg_split("/--${boundary}(--)?[\\r]?[\\n]?/", $body);
				if ( count($parts) < 3 )
					return self::_error("Décodage '$contentType' impossible lié au nombre de parties (1).");

				// oublier la première partie vide
				$parts = array_slice($parts, 1);
				
				// pour toutes les parties : décoder (cas où plusieurs PJ, par exemple)
				foreach ( $parts as $k=>$part )
				{
					// si partie pas viable, on a fini l'exploitation, c'est la ligne vierge après dernier séparateur
					if ( trim($part) == '' )
					{
						unset($parts[$k]);
						break;
					}
						
					// décoder cette partie ; détecter erreur pour arrêter prématurément
					$partObject = EmlReader::fromString($part);
					if ( !$partObject )
						return NULL;
						
					$parts[$k] = $partObject;
				}

				if ( count($parts) < 2 )
					return self::_error("Décodage '$contentType' impossible lié au nombre de parties (2).");
					
				return MailMultipart::fromSingleArray(substr(strstr($contentType, '/'), 1), $parts);
					
					
			// autre cas : décoder selon transfer encoding
			default:
				$decodedBody = self::decodeBody($body, self::decodeHeader($headers['Content-Transfer-Encoding']));
				if ( !$decodedBody )
					return NULL;
					
				// puis créer le contenu souhaité (text/plain, text/html ou PJ/inline)
				return self::decodeContent($decodedBody, $headers, $contentType);
		}
	}
	
	
	// obtenir en-têtes sous forme de chaine et indiquer le caractère séparateur CRLF ou LF
	static function getHeaders($eml, &$linefeed)
	{
		// obtenir en-têtes du mail ; par définition, 2 sauts de lignes consécutifs séparent ces en-têtes du reste.
		// au cas où il y ait CRLFCRLF dans le corps du mail, mais LFLF pour les en-têtes, il faut prendre le premier
		// séparateur trouvé en lisant le fichier ; on ne peut donc pas faire un strstr(CRLFCRLF) et strstr(LFLF)
		// et prendre l'un d'eux
		$p_crlf = strpos($eml, "\r\n\r\n");
		$p_lf = strpos($eml, "\n\n");
		
		
		// cas simples : pas trouvé l'un des séparateurs, c'est forcément l'autre
		if ( $p_crlf === FALSE )
			$linefeed = "\n";
		else
		if ( $p_lf === FALSE )
			$linefeed = "\r\n";
		
		// cas où les deux séparateurs sont trouvés, prendre le premier
		else
			$linefeed = ($p_crlf < $p_lf ) ? "\r\n" : "\n";
			
		
		$sep = $linefeed . $linefeed;
		
		// découper headers et body
		return strstr($eml, $sep, true);
	}
	
	
	// construire d'après une chaine	
	static function fromString($data)
	{
		// décoder en-tête et obtenir caractère linefeed
		$linefeed = NULL;
		$headers = self::getHeaders($data, $linefeed);
		$body = substr($data, strlen($headers . $linefeed . $linefeed));
		$headers = trim($headers);
		if ( !$headers )
			return self::_error('En-têtes indéchiffrables.');
		
		// en-tête en tableau
		$headers = Mailer::headersToArray($headers);

		
		// traiter différemment le body en fonction de l'en-tete Content-Type
		if ( !$headers['Content-Type'] )
			return self::_error('En-tête \'Content-Type\' pas trouvé');
	

		// lire content-type
		return self::fromContentType($headers['Content-Type'], $headers, $body);
	}
	
	
	// construire d'après un fichier
	static function fromFile($file)
	{
		if ( file_exists($file) )
			return self::fromString(file_get_contents($file));
		else
			return self::_error('Fichier inexistant');
	}
}

?>