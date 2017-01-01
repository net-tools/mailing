<?php
/**
 * MailSenderQueue
 *
 * @author Pierre - dev@net-tools.ovh
 * @license MIT
 */





// namespace
namespace Nettools\Mailing;


use \Nettools\Mailing\MailPieces\MailContent;




/** 
 * Class to handle mailing queues
 */
class MailSenderQueue 
{
	// *** PRIVATE ***
	
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
	
	
	// read queues config
	private function _readData()
	{
		if ( file_exists($this->_directory . "MailSender.dat") )
        {
            $data = unserialize(file_get_contents($this->_directory . "MailSender.dat"));
            $firstqueue = reset($data['queues']);
            
            // if a queue exists, verify we have a object litteral and not an associative array
            if ( $firstqueue )
                // if we must convert associative arrays to litteral objects
                if ( gettype($firstqueue) == 'array' )
                    $data['queues'] = array_map(function ($q){return (object) $q;}, $data['queues']);
            
            return $data;
        }
		else
			return array(
							'queues' => array()
						);
	}
	

	// write queues config
	private function _writeData()
	{
		$f = fopen($this->_directory . "MailSender.dat", "w");
		fwrite($f, serialize($this->_data));
		fclose($f);
	}
	
	
	// get an email from the queue
	private function _mailFromQueue($qid, $index)
	{
		if ( file_exists($this->_directory . "$qid/$qid.$index.data")
			&&
			file_exists($this->_directory . "$qid/$qid.$index.headers")
			&&
			file_exists($this->_directory . "$qid/$qid.$index.mail")	)
			// read data files 
		{
			$ret = array(
							'data' => file_get_contents($this->_directory . "$qid/$qid.$index.data"),
							'headers' => file_get_contents($this->_directory . "$qid/$qid.$index.headers"),
							'email' => file_get_contents($this->_directory . "$qid/$qid.$index.mail")
						);

			// check all required data are set
			if ( $ret['data'] && $ret['headers'] && $ret['email'] )
				return $ret;
			else
				return FALSE;
		}
		else
			return FALSE;
	}
	
	
	// send an email from the queue ; we may modify the recipient, bcc, and headers
	private function _sendFromQueue(Mailer $mailer, \stdClass $q, $qid, $index, $bcc = NULL, $to = NULL, $suppl_headers = "")
	{
		// read mail from the queue
		if ( $mail = $this->_mailFromQueue($qid, $index) )
		{
			// handle bcc 
			if ( !is_null($bcc) )
				$mail['headers'] = Mailer::addHeader($mail['headers'], "Bcc: $bcc");
				
			// if supplementary headers
			if ( $suppl_headers )
				$mail['headers'] = Mailer::addHeaders($mail['headers'], $suppl_headers);

			// unserialize data about the email (recipient, subject, etc.)
			$data = unserialize($mail['data']);
			
			// how is the recipient ? either the TO recipient in the email or the $TO parameter here (if we want to override the recipient)
			$to or $to = $data['to'];
			if ( $data === FALSE )
				return "Can't read data about email '$index' from queue '" . $q->title . "'";
			else
				// send the email ; false is returned if OK, an error string if something is wrong
				$ret = $mailer->sendmail_raw($to, $data['subject'], $mail['email'], $mail['headers']);
		}
		else
			return "Missing data files for the email '$i' from queue '" . $q->title . "'";


		// set sending status and write config
		$data['status'] = $ret ? self::STATUS_ERROR : self::STATUS_SENT;

		$f = fopen($this->_directory . "$qid/$qid.$index.data", "w");
		fwrite($f, serialize($data));
		fclose($f);
			
		if ( $ret )
			return "Can't send email to '$to' (" . $q->title .") : $ret";
		else
			return FALSE;
	}
	
	
	// create a queue
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
				
		$this->_data['queues'][$id] = (object) $q;
		
		// create a sub-folder for this queue
		if ( !file_exists($this->_directory . $id) )
			mkdir($this->_directory . $id);
		
		return $id;
	}
	
	
	// add new email to the queue
	private function _push($qid, $rawmail, $headers, $data)
	{
		// get ID for this email
		$mid = $this->_data['queues'][$qid]->count;
		
		// write email raw content
		$f = fopen($this->_directory . "$qid/$qid.$mid.mail", "w");
		fwrite($f, $rawmail);
		fclose($f);

		// increment queue size stats
		$this->_data['queues'][$qid]->volume += filesize($this->_directory . "$qid/$qid.$mid.mail");
		
		// write headers
		$f = fopen($this->_directory . "$qid/$qid.$mid.headers", "w");
		fwrite($f, Mailer::addHeader($headers, "X-ComIncludeMailer-MailSenderQueue: $qid"));
		fclose($f);
		
		// write data about the recipient, subject
		$f = fopen($this->_directory . "$qid/$qid.$mid.data", "w");
		fwrite($f, $data);
		fclose($f);
		
		// increment queue count
		$this->_data['queues'][$qid]->count++;
		return false; // no error
	}
	
	
	// *** /PRIVATE ***


	/**
     * Initialize queue with a root folder for all queues (each queue will have a subfolder)
     *
     * @param string $directory Root folder for queues storage
     */
	function __construct($directory)
	{
		if ( substr($directory, -1) != '/' )
			$directory = $directory . '/';
		
		$this->_directory = $directory;
		$this->_data = $this->_readData();
	}
	
	
	/**
     * Create a new queue
     * 
     * @param string $qtitle Name of queue to create
     * @param int $qbatchcount Number of emails to send at a time (batch sending)
     * @return string The unique ID of the queue
     */
	function createQueue($qtitle, $qbatchcount = 50)
	{
		$id = $this->_createQueue($qtitle, $qbatchcount);
		$this->_writeData();
		
		return $id;
	}
	
	
	/**
    * Get info about a queue.
    *
    * The litteral object returned have the following properties :
    *
    *    - count : number of emails in the queue
    *    - sendOffset : the index of the next email to send (used when batching sendings)
    *    - batchCount : the number of emails to send in one batch
    *    - date : the timestamp of queue creation
    *    - lastBatchDate : the timestamp of the last batch sending process
    *    - sendLog : an array of string for log
    *    - title : the queue title
    *    - locked : contains TRUE if queue has been sent completely, FALSE otherwise
    *    - volume : the size of the queue (in bytes)
    *
    * @param string $qid ID of the queue
    * @return object Litteral object describing the queue
    */
	function getQueue($qid)
	{
		return $this->_data['queues'][$qid];
	}
	
	
	/**
     * Get data about all queues
     *
     * @param string $sort One of the SORT_xxx constant defined here 
     * @param string $sortorder One of the SORTORDER_xxx constant defined here
     * @return object[] Returns an indexed array of queues litteral objects (indexes are the queues ID)
     * @see MailSenderQueue::getQueue
     */
	function getQueues($sort, $sortorder = self::SORTORDER_ASC)
	{
		$ret = $this->_data['queues'];
		
		$inf = ($sortorder == self::SORTORDER_ASC ) ? '-1':'1';
		$sup = ($sortorder == self::SORTORDER_ASC ) ? '1':'-1';
		
		// if sorting on an existing property
		if ( $sort != self::SORT_STATUS )
			$fun = create_function('$a, $b',"if ( \$a->$sort < \$b->$sort ) return $inf; " .
											"else if ( \$a->$sort == \$b->$sort ) return 0;  " .
											"else return $sup;");
		else
			// if sorting on the status
			$fun = create_function('$a, $b',"\$st_a = \$a->count - \$a->sendOffset; " .
											"\$st_b = \$b->count - \$b->sendOffset; " .
											"if ( \$st_a > \$st_b ) return $inf; " .
											"else if ( \$st_a == \$st_b ) return 0; " .
											"else return $sup;");
		
		// sort array
		uasort($ret, $fun);
		
		return $ret;
	}
	
	
	/**
     * Push an email to the queue
     * 
     * @param string $qid ID of the queue
     * @param MailPieces\MailContent $mail Email object
     * @param string $from Email address of the sender
     * @param string $to Email recipient
     * @param string $subject Email subject
     * @return bool Always return FALSE (meaning no error)
     */
	function push($qid, MailContent $mail, $from, $to, $subject)
	{
		// add required headers to the email (importance, etc.)
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
	
	
	/**
     * Rename a queue
     * 
     * @param string $qid ID of the queue
     * @param string $value New name
     */
	function renameQueue($qid, $value)
	{
		$this->_data['queues'][$qid]->title = $value;
		$this->_writeData();
	}
	
	
	/**
     * Unlock a queue (a queue is locked when all email have been sent)
     * 
     * @param string $qid ID of the queue
     */
	function unlockQueue($qid)
	{
		$this->_data['queues'][$qid]->locked = false;
		$this->_writeData();
	}
	
	
	/**
     * Search an email recipient in the queue
     * 
     * @param string $qid ID of the queue
     * @param string $mail Email of recipient
     * @return int 0-index of email found in the queue
     */
	function searchQueue($qid, $mail)
	{
		$q = $this->_data['queues'][$qid];

		// if queue exists
		if ( is_null($q) )
			return FALSE;
			
		for ( $i = 0 ; $i < $q->count ; $i++ )
			if ( file_exists($this->_directory . "$qid/$qid.$i.data") && ($data = file_get_contents($this->_directory . "$qid/$qid.$i.data")) )
			{
				$data = unserialize($data);

				// si recipient is found
				if ( $data['to'] == $mail )
					return $i;
			}
			
		return FALSE;
	}
	
	
	/**
     * Extract an email from the queue
     * 
     * @param string $qid ID of the queue
     * @param int 0-index of email in the queue
     * @return string Email raw text (headers and content)
     */
	function emlFromQueue($qid, $index)
	{
		$q = $this->_data['queues'][$qid];

		// check queue exists
		if ( is_null($q) )
			return FALSE;

		// read email from queue et get a string for an EML file
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
	
	
	/** 
     * Close a queue (all emails have been pushed in the queue)
     * 
     * @param string $qid ID of the queue
     */
	function closeQueue($qid)
	{
		$this->_writeData();
	}
	
	
	/** 
     * Resend an email from the queue
     * 
     * @param Mailer $mailer Mailer used for sending the email again
     * @param string $qid ID of the queue
     * @param int $index 0-index of the email to send in the queue
     * @param string|NULL $bcc Recipient in bcc, if necessary
     * @param string|NULL $to Recipient to send the email to, if we want to override the previous recipient
     * @return bool|string Return FALSE if email was sent (meaning no error), or a string with an error message
     */
	function resendFromQueue(Mailer $mailer, $qid, $index, $bcc = NULL, $to = NULL)
	{
		$q = $this->_data['queues'][$qid];

		if ( is_null($q) )
			return "Queue '$qid' does not exist";
			
		return $this->_sendFromQueue($mailer, $q, $qid, $index, $bcc, $to);
	}
	
	
	/**
    * Create a new queue with email whose status is error
    * 
    * @param string $qid ID of the queue to extract emails in error from
    * @param string $title Name of the new queue
    * @return string Returns the ID of the new queue created
    */
	function newQueueFromErrors($qid, $title)
	{
		$q = $this->_data['queues'][$qid];
		$nid = $this->_createQueue($title, $q->batchCount);
		
		// check all emails from the queue
		for ( $i = 0 ; $i < $q->count ; $i++ )
		{
			if ( $mail = $this->_mailFromQueue($qid, $i) )
			{
				// if email was not sent, pushing it to the new queue
				$data = unserialize($mail['data']);
				if ( $data['status'] == self::STATUS_ERROR )
				{
					$data['status'] = self::STATUS_TOSEND;
					$this->_push($nid, $mail['email'], $mail['headers'], serialize($data));
				}
			}
		}
		
		$this->_writeData();
		
		return $nid;
	}
	
	
	/** 
     * Send a batch of email through a Mailer instance, and optionnally add headers
     *
     * @param Mailer $mailer Mailer instance to send email through
     * @param string $qid ID of the queue to process
     * @param string $suppl_headers Optionnal supplementary headers
     * @return bool|string Returns FALSE if the process was OK, an string with an error message otherwise 
     */
	function sendQueue(Mailer $mailer, $qid, $suppl_headers = "")
	{
		$q = $this->_data['queues'][$qid];
		
		// check file exists
		if ( is_null($q) )
			return "La file '$qid' n'existe pas";
		
		
		// are the emails to sent ?
		if ( $q->sendOffset < $q->count )
		{
			$ret = array();
			
			// handle a batch of emails, until queue end is reached
			$max = min(array($q->sendOffset + $q->batchCount, $q->count));
			for ( $i = $q->sendOffset ; $i < $max ; $i++ )
			{
				$r = $this->_sendFromQueue($mailer, $q, $qid, $i, NULL, NULL, $suppl_headers);
				if ( $r )
					$ret[] = $r;
			}
			
			
			// increment offset
			$this->_data['queues'][$qid]->sendOffset += min($q->batchCount, $q->count - $q->sendOffset);
			
			// save log
			$this->_data['queues'][$qid]->sendLog = array_merge($this->_data['queues'][$qid]->sendLog, $ret);
			
			// write timestamp for last sent batch
			$this->_data['queues'][$qid]->lastBatchDate = time();
			
			// if all emails in queue have been sent, locking the queue
			if ( $this->_data['queues'][$qid]->sendOffset == $this->_data['queues'][$qid]->count )
				$this->_data['queues'][$qid]->locked = true;

			// write config
			$this->_writeData();

			// check errors
			return count($ret) ? "Errors occured during queue processing '" . $q->title . "'" : false;
		}
		else
			return "Queue '" . $q->title . "' is empty";
	}
	
	
	/**
     * Get recipients for a queue
     *
     * The litteral objects returned in the array have the following properties :
     *
     * - to : recipient
     * - id : 0-index of the email in the source queue
     * - status : one of the STATUS constants
     * 
     * @param string $qid ID of the queue to extract recipients from
     * @return object[] Return an array of litteral objects about recipients 
     */
	function recipientsFromQueue($qid)
	{
		$q = $this->_data['queues'][$qid];
		
		if ( is_null($q) )
			return FALSE;
		
		$ret = array();
			
		for ( $i = 0 ; $i < $q->count ; $i++ )
			if ( file_exists($this->_directory . "$qid/$qid.$i.data") && ($data = file_get_contents($this->_directory . "$qid/$qid.$i.data")) )
			{
				$data = unserialize($data);
				$ret[] = (object) array('to'=>$data['to'], 'id'=>$i, 'status'=>$data['status']);
			}
		
		
		return $ret;
	}
	
	
	/**
     * Set an email to error (after it has been sent)
     * 
     * Useful when the email recipient is later reported to be wrong
     *
     * @param string $qid ID of the queue
     * @param int 0-index of the email to set to error status
     */
	function recipientError($qid, $index)
	{
		$q = $this->_data['queues'][$qid];

		// check queue exists
		if ( is_null($q) )
			return "Queue '$qid' does not exist";


		// get email info
		$mail = $this->_mailFromQueue($qid, $index);
		if ( !$mail) 
			return "Missing data files for email '$index' from the queue '" . $q->title . "'";

		// unserialize email data
		$data = unserialize($mail['data']);
		if ( $data === FALSE )
			return "Can't read data about email '$index' from the queue '" . $q->title . "'";

	
		// modify status and write config
		$data['status'] = self::STATUS_ERROR;
		$f = fopen($this->_directory . "$qid/$qid.$index.data", "w");
		fwrite($f, serialize($data));
		fclose($f);
		
		// update log
		$this->_data['queues'][$qid]->sendLog = array_merge($this->_data['queues'][$qid]->sendLog, array("Error for '" . $data['to'] . "' (" . $q->title . ") : set to Error by user"));
		$this->_writeData();
		
		// no error
		return FALSE;
	}
	
	
	/**
     * Erase log for a queue
     *
     * @param string $qid ID of the queue
     */
	function clearLog($qid)
	{
		$q = $this->_data['queues'][$qid];

		if ( is_null($q) )
			return "Queue '$qid' does not exist";
			
		$this->_data['queues'][$qid]->sendLog = array();
		$this->_writeData();
		
		return FALSE;
	}
	
	
	/**
     * Erase a queue from disk
     *
     * @param string $qid ID of the queue
     */

	function purgeQueue($qid)
	{
		$files = glob($this->_directory . "$qid/$qid.*");
		if ( is_array($files) )
			foreach ( $files as $f )
				unlink($f);
				
		unset($this->_data['queues'][$qid]);
		
		if ( file_exists($this->_directory . $qid) )
			rmdir($this->_directory . $qid);

		
        $this->_writeData();
	}
	
	
	/**
     * Erase all queues on disk
     */
	function purgeAllQueues()
	{
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

		$this->_writeData();
	}
}
?>