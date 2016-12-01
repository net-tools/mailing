<?php

// namespace
namespace Nettools\Mailing;

// clauses use
use \Nettools\Mailing\Mailer;
use \Nettools\Mailing\MailPieces\MailContent;




// classe pour gérer des envois de mails en nombre
class MailSenderQueue 
{
	// *** DECL. PRIVEES ***
	
	private $_directory;
	private $_data;
	
	const SORTORDER_ASC = "asc";
	const SORTORDER_DESC = "desc";
	const SORT_COUNT = 'count';
	const SORT_DATE = 'date';
	const SORT_TITLE = 'title';
	const SORT_STATUS = 'status';
	const SORT_VOLUME = 'volume';
	
	const STATUS_TOSEND = -1;
	const STATUS_SENT = 0;
	const STATUS_ERROR = 1;
	
	
	// lire la configuration (sérialisée)
	private function _readData()
	{
		if ( file_exists($this->_directory . "MailSender.dat") )
			return unserialize(file_get_contents($this->_directory . "MailSender.dat"));
		else
			return array(
							'queues' => array()
						);
	}
	

	// écrire la configuration sérialisée
	private function _writeData()
	{
		$f = fopen($this->_directory . "MailSender.dat", "w");
		fwrite($f, serialize($this->_data));
		fclose($f);
	}
	
	
	// lire les infos pour un mail donné
	private function _mailFromQueue($qid, $index)
	{
		if ( file_exists($this->_directory . "$qid/$qid.$index.data")
			&&
			file_exists($this->_directory . "$qid/$qid.$index.headers")
			&&
			file_exists($this->_directory . "$qid/$qid.$index.mail")	)
			// lire le fichier de données
		{
			$ret = array(
							'data' => file_get_contents($this->_directory . "$qid/$qid.$index.data"),
							'headers' => file_get_contents($this->_directory . "$qid/$qid.$index.headers"),
							'email' => file_get_contents($this->_directory . "$qid/$qid.$index.mail")
						);

			// vérifier qu'on a toutes les données
			if ( $ret['data'] && $ret['headers'] && $ret['email'] )
				return $ret;
			else
				return FALSE;
		}
		else
			return FALSE;
	}
	
	
	// envoyer un mail donné ; éventuellement, modifier à qui on l'envoie, par rapport à ce qui est indiqué dans le mail
	private function _sendFromQueue(Mailer $mailer, $q, $qid, $index, $bcc = NULL, $to = NULL, $suppl_headers = "")
	{
		// lire les infos du mail (fichiers de données)
		if ( $mail = $this->_mailFromQueue($qid, $index) )
		{
			// traiter BCC
			if ( !is_null($bcc) )
				$mail['headers'] = Mailer::addHeader($mail['headers'], "Bcc: $bcc");
				
			// si headers supplémentaires
			if ( $suppl_headers )
				$mail['headers'] = Mailer::addHeaders($mail['headers'], $suppl_headers);

			// désérialiser les données de ce mail
			$data = unserialize($mail['data']);
			
			// déterminer destinataire ; soit fourni en paramètre (pour outrepasser l'indication actuelle) ou dans les DATA
			$to or $to = $data['to'];
			if ( $data === FALSE )
				return "Erreur &agrave; l'exploitation des donn&eacute;es pour le mail '$index' de la file '" . $q['title'] . "'";
			else
				// envoyer le mail ; FALSE si OK, chaine si erreur
				$ret = $mailer->sendmail_raw($to, $data['subject'], $mail['email'], $mail['headers']);
		}
		else
			return "Fichiers de donn&eacute;es absents (tout ou partie) pour le mail '$i' de la file '" . $q['title'] . "'";


		// si on arrive ici, c'est qu'on a tenté l'envoi ; $RET = FALSE si ok, chaine si erreur
		// consigner l'envoi
		$data['status'] = $ret ? self::STATUS_ERROR : self::STATUS_SENT;

		$f = fopen($this->_directory . "$qid/$qid.$index.data", "w");
		fwrite($f, serialize($data));
		fclose($f);
			
		if ( $ret )
			return "Echec envoi pour '$to' (" . $q['title'] .") : $ret";
		else
			return FALSE;
	}
	
	
	// créer une file
	private function _createQueue($qtitle, $qbatchcount = 50)
	{
		$id = uniqid();
		$q = array(
					'count' => 0,
					'sendOffset' => 0,
					'batchCount' => $qbatchcount,
					'date' => time(),
					'lastBatchDate' => NULL,
					'sendLog' => array(),
					'title' => $qtitle,
					'locked' => false,
					'volume' => 0
				);
				
		$this->_data['queues'][$id] = $q;
		
		// créer sous-dossier pour la file
		if ( !file_exists($this->_directory . $id) )
			mkdir($this->_directory . $id);
		
		return $id;
	}
	
	
	// ajouter un mail brut à la file
	private function _push($qid, $rawmail, $headers, $data)
	{
		// obtenir l'ID du mail (compteur incrémenté)
		$mid = $this->_data['queues'][$qid]['count'];
		
		// écrire le contenu du mail
		$f = fopen($this->_directory . "$qid/$qid.$mid.mail", "w");
		fwrite($f, $rawmail);
		fclose($f);

		// incrémenter volume file			
		$this->_data['queues'][$qid]['volume'] += filesize($this->_directory . "$qid/$qid.$mid.mail");
		
		// écrire dans un fichier séparé les en-têtes, et ajouter un champ perso pour identifier la file
		$f = fopen($this->_directory . "$qid/$qid.$mid.headers", "w");
		fwrite($f, Mailer::addHeader($headers, "X-ComIncludeMailer-MailSenderQueue: $qid"));
		fclose($f);
		
		// écrire dans un fichier séparé et sérialisé les données d'envoi (sujet, destinataire)
		$f = fopen($this->_directory . "$qid/$qid.$mid.data", "w");
		fwrite($f, $data);
		fclose($f);
		
		// incrémenter le compteur
		$this->_data['queues'][$qid]['count']++;
		return false; // pas d'erreur
	}
	
	
	// *** /DECL. PRIVEES ***


	// initialiser le gestionnaire avec le dossier de stockage des mails
	function __construct($directory)
	{
		if ( substr($directory, -1) != '/' )
			$directory = $directory . '/';
		
		$this->_directory = $directory;
		$this->_data = $this->_readData();
	}
	
	
	// créer une nouvelle file de mails
	function createQueue($qtitle, $qbatchcount = 50)
	{
		$id = $this->_createQueue($qtitle, $qbatchcount);
		
		// ecrire la configuration
		$this->_writeData();
		
		return $id;
	}
	
	
	// obtenir une file d'envoi
	function getQueue($qid)
	{
		return $this->_data['queues'][$qid];
	}
	
	
	// obtenir les files d'envoi
	function getQueues($sort, $sortorder = self::SORTORDER_ASC)
	{
		$ret = $this->_data['queues'];
		
		// créer fonction callback de tri
		$inf = ($sortorder == self::SORTORDER_ASC ) ? '-1':'1';
		$sup = ($sortorder == self::SORTORDER_ASC ) ? '1':'-1';
		
		// si tri selon un champ existant réellement tel quel
		if ( $sort != self::SORT_STATUS )
			$fun = create_function('$a, $b',"if ( \$a['$sort'] < \$b['$sort'] ) return $inf; " .
											"else if ( \$a['$sort'] == \$b['$sort'] ) return 0;  " .
											"else return $sup;");
		else
			// cas particulier du tri sur le statut, déterminé en comparant count=sendOffset
			$fun = create_function('$a, $b',"\$st_a = \$a['count'] - \$a['sendOffset']; " .
											"\$st_b = \$b['count'] - \$b['sendOffset']; " .
											"if ( \$st_a > \$st_b ) return $inf; " .
											"else if ( \$st_a == \$st_b ) return 0; " .
											"else return $sup;");
		
		// trier le tableau (passé par référence)
		uasort($ret, $fun);
		
		return $ret;
	}
	
	
	// ajouter un mail à la file
	function push($qid, MailContent $mail, $from, $to, $subject)
	{
		// ajouter les en-têtes techniques obligatoires
		Mailer::render($mail);
		
		return $this->_push(
						$qid, 
						$mail->getContent(), 
						Mailer::addHeader($mail->getFullHeaders(), "From: $from"), 
						serialize(array(
										'to'=>$to,
										'subject'=>$subject,
										'status'=>self::STATUS_TOSEND
									))
					);
	}
	
	
	// renommer une file d'envoi (sauvegarde config de la file sur disque)
	function renameQueue($qid, $value)
	{
		// ecrire la configuration après avoir renommé
		$this->_data['queues'][$qid]['title'] = $value;
		$this->_writeData();
	}
	
	
	// déverrouiller une file d'envoi (sauvegarde config de la file sur disque)
	function unlockQueue($qid)
	{
		// ecrire la configuration après avoir modifié
		$this->_data['queues'][$qid]['locked'] = false;
		$this->_writeData();
	}
	
	
	// rechercher un envoi
	function searchQueue($qid, $mail)
	{
		$q = $this->_data['queues'][$qid];

		// vérifier existence file
		if ( is_null($q) )
			return FALSE;
			
		for ( $i = 0 ; $i < $q['count'] ; $i++ )
			if ( file_exists($this->_directory . "$qid/$qid.$i.data") && ($data = file_get_contents($this->_directory . "$qid/$qid.$i.data")) )
			{
				$data = unserialize($data);

				// si trouvé mail demandé
				if ( $data['to'] == $mail )
					return $i;
			}
			
		return FALSE;
	}
	
	
	// télécharger un fichier EML pour un envoi
	function emlFromQueue($qid, $index)
	{
		$q = $this->_data['queues'][$qid];

		// vérifier existence file
		if ( is_null($q) )
			return FALSE;

		// lire le mail
		if ( $mail = $this->_mailFromQueue($qid, $index) )
		{
			$data = unserialize($mail['data']);
			$eml = $mail['headers'] . "\r\n" .
					"To: " . $data['to'] . "\r\n" .
					"Subject: " . $data['subject'] . "\r\n" .
					"\r\n" .
					$mail['email'];
			return $eml;
		}
			
		return FALSE;
	}
	
	
	// achever une file d'envoi (sauvegarde config de la file sur disque)
	function closeQueue($qid)
	{
		// ecrire la configuration
		$this->_writeData();
	}
	
	
	// renvoyer un mail donné d'une file
	function resendFromQueue(Mailer $mailer, $qid, $index, $bcc = NULL, $to = NULL)
	{
		$q = $this->_data['queues'][$qid];

		// vérifier existence file
		if ( is_null($q) )
			return "La file '$qid' n'existe pas";
			
		return $this->_sendFromQueue($mailer, $q, $qid, $index, $bcc, $to);
	}
	
	
	// constituer une nouvelle file avec les envois en erreur d'une file
	function newQueueFromErrors($qid, $title)
	{
		// créer nouvelle file
		$q = $this->_data['queues'][$qid];
		$nid = $this->_createQueue($title, $q['batchCount']);
		
		// parcourir les mails de l'ancienne file
		for ( $i = 0 ; $i < $q['count'] ; $i++ )
		{
			if ( $mail = $this->_mailFromQueue($qid, $i) )
			{
				// si courrier en erreur, le considérer
				$data = unserialize($mail['data']);
				if ( $data['status'] == self::STATUS_ERROR )
				{
					$data['status'] = self::STATUS_TOSEND;
					$this->_push($nid, $mail['email'], $mail['headers'], serialize($data));
				}
			}
		}
		
		// ecrire la configuration
		$this->_writeData();
		
		return $nid;
	}
	
	
	// envoyer un lot de courriers, et rajouter éventuellement des en-têtes sur chaque envoi (par ex. pour identifier campagne)
	function sendQueue(Mailer $mailer, $qid, $suppl_headers = "")
	{
		$q = $this->_data['queues'][$qid];
		
		// vérifier existence file
		if ( is_null($q) )
			return "La file '$qid' n'existe pas";
		
		
		// y-a-t-il encore des mails à envoyer ?
		if ( $q['sendOffset'] < $q['count'] )
		{
			$ret = array();
			
			// traiter un lot complet ou jusqu'à arriver au bout de la file
			$max = min(array($q['sendOffset'] + $q['batchCount'], $q['count']));
			for ( $i = $q['sendOffset'] ; $i < $max ; $i++ )
			{
				$r = $this->_sendFromQueue($mailer, $q, $qid, $i, NULL, NULL, $suppl_headers);
				if ( $r )
					$ret[] = $r;
			}
			
			
			// quand fini de traiter un lot, incrémenter offset directement dans propriétés, car $q est une copie !!!
			$this->_data['queues'][$qid]['sendOffset'] += min($q['batchCount'], $q['count'] - $q['sendOffset']);
			
			// mémoriser log
			$this->_data['queues'][$qid]['sendLog'] = array_merge($this->_data['queues'][$qid]['sendLog'], $ret);
			
			// écrire la date d'aujourd'hui pour tracer la date du dernier lot traité
			$this->_data['queues'][$qid]['lastBatchDate'] = time();
			
			// verrouiller si terminé
			if ( $this->_data['queues'][$qid]['sendOffset'] == $this->_data['queues'][$qid]['count'] )
				$this->_data['queues'][$qid]['locked'] = true;

			// ecrire la configuration
			$this->_writeData();

			// pas d'erreur ?
			return count($ret) ? "Il y a eu des erreurs pendant le traitement de la file '" . $q['title'] . "'" : false;
		}
		else
			return "La file '" . $q['title'] . "' est vide";
	}
	
	
	// obtenir la liste des destinataires d'une file
	function recipientsFromQueue($qid)
	{
		$q = $this->_data['queues'][$qid];
		
		// vérifier existence file
		if ( is_null($q) )
			return FALSE;
		
		$ret = array();
			
		for ( $i = 0 ; $i < $q['count'] ; $i++ )
			if ( file_exists($this->_directory . "$qid/$qid.$i.data") && ($data = file_get_contents($this->_directory . "$qid/$qid.$i.data")) )
			{
				$data = unserialize($data);
				$ret[] = array('to'=>$data['to'], 'id'=>$i, 'status'=>$data['status']);
			}
		
		
		return $ret;
	}
	
	
	// indiquer un mail en erreur, a posteriori
	function recipientError($qid, $index)
	{
		$q = $this->_data['queues'][$qid];

		// vérifier existence file
		if ( is_null($q) )
			return "La file '$qid' n'existe pas";


		// lire les infos du mail (fichiers de données)
		$mail = $this->_mailFromQueue($qid, $index);
		if ( !$mail) 
			return "Fichiers de donn&eacute;es absents (tout ou partie) pour le mail '$index' de la file '" . $q['title'] . "'";

		// décoder les données liées au mail
		$data = unserialize($mail['data']);
		if ( $data === FALSE )
			return "Erreur &agrave; l'exploitation des donn&eacute;es pour le mail '$index' de la file '" . $q['title'] . "'";

		$data['status'] = self::STATUS_ERROR;
	
		// réécrire les données avec le statut modifié
		$f = fopen($this->_directory . "$qid/$qid.$index.data", "w");
		fwrite($f, serialize($data));
		fclose($f);
		
		// consigner mise en erreur dans le log de la file
		$this->_data['queues'][$qid]['sendLog'] = array_merge($this->_data['queues'][$qid]['sendLog'], array("Echec pour '" . $data['to'] . "' (" . $q['title'] . ") : envoi mis en erreur manuellement"));
		$this->_writeData();
		
		// pas d'erreur
		return FALSE;
	}
	
	
	// effacer le log d'une file
	function clearLog($qid)
	{
		$q = $this->_data['queues'][$qid];

		// vérifier existence file
		if ( is_null($q) )
			return "La file '$qid' n'existe pas";
			
		$this->_data['queues'][$qid]['sendLog'] = array();
		$this->_writeData();
		
		return FALSE;
	}
	
	
	// purger une file d'envoi
	function purgeQueue($qid)
	{
		$files = glob($this->_directory . "$qid/$qid.*");
		if ( is_array($files) )
			foreach ( $files as $f )
				unlink($f);
				
		unset($this->_data['queues'][$qid]);
		
		if ( file_exists($this->_directory . $qid) )
			rmdir($this->_directory . $qid);

		// ecrire la configuration
		$this->_writeData();
	}
	
	
	// purger toutes les files d'envoi
	function purgeAllQueues()
	{
		// prendre la liste des files
		foreach ( $this->_data['queues'] as $qid => $q )
		{
			$files = glob($this->_directory . "$qid/*");
			if ( is_array($files) )
				foreach ( $files as $f )
					unlink($f);
					
			if ( file_exists($this->_directory . $qid) )
				rmdir($this->_directory . $qid);
		}
				
		$this->_data['queues'] = array();

		// ecrire la configuration
		$this->_writeData();
	}
}
?>