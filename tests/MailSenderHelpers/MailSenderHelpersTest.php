<?php 

namespace Nettools\Mailing\MailSenderHelpers\Tests;



use \Nettools\Mailing\Mailer;
use \Nettools\Mailing\MailSenderQueue\Store;
use \Nettools\Mailing\MailSenderQueue\Queue;
use \Nettools\Mailing\MailSenderHelpers\MailSenderHelper;
use \Nettools\Mailing\MailSenderHelpers\Attachments;
use \Nettools\Mailing\MailSenderHelpers\Embeddings;
use \org\bovigo\vfs\vfsStream;
use \org\bovigo\vfs\vfsStreamDirectory;



class MailSenderHelpersTest extends \PHPUnit\Framework\TestCase
{
	protected $_queuePath = NULL;
	protected $_fatt = NULL;
	protected $_vfs = NULL;
	protected static $_fatt_content = "Attachment sample with accents é.";
	protected static $_fatt_content_b64 = 'QXR0YWNobWVudCBzYW1wbGUgd2l0aCBhY2NlbnRzIMOpLg==';
	

	public function setUp() :void
	{
		$this->_vfs = vfsStream::setup('root');
		
		// temp file
		$tmpdir = uniqid() . 'msh';
		vfsStream::newDirectory($tmpdir)->at($this->_vfs);
		
		$this->_queuePath = vfsStream::url("root/$tmpdir/");
		$this->_fatt = vfsStream::url("root/$tmpdir/" . uniqid() . 'att1.txt');
	
		// creating attachment
		$f = fopen($this->_fatt, "w");
		fwrite($f, self::$_fatt_content); 
		fclose($f);
	}
	
	
		
	public function testMSH()
	{
		function __ready($msh)
		{
			try
			{
				$msh->ready();
				return true;
			}
			catch( \Nettools\Mailing\MailSenderHelpers\Exception $e )
			{
				return false;
			}
		}
		
		
		$ml = new Mailer(new \Nettools\Mailing\MailSenders\Virtual());
		$msh = new MailSenderHelper($ml, 'msh content', 'text/plain', 'unit-test@php.com', 'test subject', ['testMode'=>true]);
		$this->assertEquals(NULL, $msh->getToOverride());
		$msh->setToOverride('override-user@php.com');
		$this->assertEquals('override-user@php.com', $msh->getToOverride());
		$this->assertEquals(true, $msh->getTestMode());
		$this->assertEquals('msh content', $msh->getRawMail());

		$msh->setRawMail('other content');
		$this->assertEquals('other content', $msh->getRawMail());
		
//	(Mailer $mailer, $mail, $mailContentType, $from, $subject, $testmode)
		$msh = new MailSenderHelper($ml, NULL, NULL, NULL, NULL);		
		$this->assertEquals(false, __ready($msh));	// no parameter
		
		$msh = new MailSenderHelper($ml, 'msh content', 'text/plain', 'unit-test@php.com', 'test subject');
		$this->assertEquals(true, __ready($msh));	// all parameters
		$this->assertEquals(false, $msh->getTestMode());
		
		$msh = new MailSenderHelper($ml, NULL, 'text/plain', 'unit-test@php.com', 'test subject');		
		$this->assertEquals(false, __ready($msh));	// all except content
		
		$msh = new MailSenderHelper($ml, 'msh content', NULL, 'unit-test@php.com', 'test subject');
		$this->assertEquals(false, __ready($msh));	// all except contenttype
		
		$msh = new MailSenderHelper($ml, 'msh content', 'text/plain', NULL, 'test subject');
		$this->assertEquals(false, __ready($msh));	// all exception from address
		
		$msh = new MailSenderHelper($ml, 'msh content', 'text/plain', 'unit-test@php.com', NULL);
		$this->assertEquals(true, __ready($msh));	// all except subject : but subject is not mandatory, provided it's set when calling `send`
		
		$msh = new MailSenderHelper($ml, 'msh content', 'text/plain', 'unit-test@php.com', 'test subject', ['testMode' => true]);
		$this->assertEquals(false, __ready($msh));	// test mode but no test recipients

		$msh = new MailSenderHelper($ml, 'msh content', 'text/plain', 'unit-test@php.com', 'test subject', ['testMode' => true, 'testRecipients' => ['me@home.com', 'them@home.net']]);
		$this->assertEquals(true, __ready($msh));	// test mode with test recipients as params

		
		$msh = new MailSenderHelper($ml, 'msh content', 'text/plain', 'unit-test@php.com', 'test subject', ['testMode' => true, 'testRecipients' => ['user-test1@php.com', 'user-test2@php.com']]);
		$content = $msh->render(NULL);
		$this->assertInstanceOf(\Nettools\Mailing\MailPieces\MailContent::class, $content);
		$ml->setMailSender(new \Nettools\Mailing\MailSenders\Virtual(), NULL);
		$msh->send($content, 'user-to@php.com');
		$sent = $ml->getMailSender()->getSent();
		$this->assertCount(1, $sent);	// test mode, sent to a test recipient
		$this->assertStringContainsString('user-test1@php.com', $sent[0]);
		

		$msh = new MailSenderHelper($ml, 'msh content', 'text/plain', 'unit-test@php.com', 'test subject', ['bcc' => 'bcc-user@php.com', 'replyTo' => 'reply-to-user@php.com']);
		$ml->setMailSender(new \Nettools\Mailing\MailSenders\Virtual(), NULL);
		$content = $msh->render(NULL);
		
		try
		{
			$msh->send($content, NULL);		// recipient not set
			$this->assertEquals(true, false);
		}
		catch( \Nettools\Mailing\MailSenderHelpers\Exception $e )
		{
		}
			
		
		
		try
		{
			$msh->send($content, 'nouser');	// recipient syntax wrong
			$this->assertEquals(true, false);
		}
		catch( \Nettools\Mailing\MailSenderHelpers\Exception $e )
		{
		}
			
		
		$msh->send($content, 'user-to@php.com'); // fine
		
		$sent = $ml->getMailSender()->getSent();
		$this->assertCount(2, $sent);								// BCC + mail
		$this->assertStringStartsWith( 
				"Content-Type: multipart/alternative;\r\n   boundary=\"" . $content->getSeparator() . "\"\r\n" .
				"Reply-To: reply-to-user@php.com\r\n" .
				"MIME-Version: 1.0\r\n" . 
				"From: unit-test@php.com\r\n" .
				"To: user-to@php.com\r\n" .
				"Subject: " . Mailer::encodeSubject('test subject') . "\r\n" .
				"X-Priority: 1\r\n" .
				"Importance: High\r\n" . 
				"Delivered-To: bcc-user@php.com\r\n" .
				"\r\n" . 
				"--" . $content->getSeparator() . "\r\n",
		
				$sent[0]);

		$this->assertEquals(true, is_int(strpos($sent[0], 'msh content')));
		$this->assertStringStartsWith( 
				"Content-Type: multipart/alternative;\r\n   boundary=\"" . $content->getSeparator() . "\"\r\n" .
				"Reply-To: reply-to-user@php.com\r\n" .
				"MIME-Version: 1.0\r\n" . 
				"From: unit-test@php.com\r\n" .
				"To: user-to@php.com\r\n" .
				"Subject: " . Mailer::encodeSubject('test subject') . "\r\n" .
				"X-Priority: 1\r\n" .
				"Importance: High\r\n" . 
				"Delivered-To: user-to@php.com\r\n" .
				"\r\n" . 
				"--" . $content->getSeparator() . "\r\n",
		
				$sent[1]);

		$msh->setToOverride('override-user@php.com');
		$ml->setMailSender(new \Nettools\Mailing\MailSenders\Virtual(), NULL);
		$msh->send($content, 'user-to@php.com');
		$sent = $ml->getMailSender()->getSent();
		$this->assertCount(2, $sent);								// BCC + mail
		$this->assertStringStartsWith( 
				"Content-Type: multipart/alternative;\r\n   boundary=\"" . $content->getSeparator() . "\"\r\n" .
				"Reply-To: reply-to-user@php.com\r\n" .
				"MIME-Version: 1.0\r\n" . 
				"From: unit-test@php.com\r\n" .
				"To: override-user@php.com\r\n" .
				"Subject: " . Mailer::encodeSubject('test subject') . "\r\n" .
				"X-Priority: 1\r\n" .
				"Importance: High\r\n" . 
				"Delivered-To: bcc-user@php.com\r\n" .
				"\r\n" .
				"--" . $content->getSeparator() . "\r\n",
		
				$sent[0]);
		$this->assertStringStartsWith( 
				"Content-Type: multipart/alternative;\r\n   boundary=\"" . $content->getSeparator() . "\"\r\n" .
				"Reply-To: reply-to-user@php.com\r\n" .
				"MIME-Version: 1.0\r\n" . 
				"From: unit-test@php.com\r\n" .
				"To: override-user@php.com\r\n" .
				"Subject: " . Mailer::encodeSubject('test subject') . "\r\n" .
				"X-Priority: 1\r\n" .
				"Importance: High\r\n" . 
				"Delivered-To: override-user@php.com\r\n" .
				"\r\n" .
				"--" . $content->getSeparator() . "\r\n",
		
				$sent[1]);
				
				

		$this->assertEquals(NULL, $msh->getQueueCount());			// queue not used, NULL is returned
		
		$msh = new MailSenderHelper($ml, 'msh content', 'text/plain', 'unit-test@php.com', 'test subject', 
										[
											'template' => 'my template : %content%',
											'queue' => 'queuename',
											'queueParams' => ['root' => $this->_queuePath, 'batchCount' => 10]
										]);
		$ml->setMailSender(new \Nettools\Mailing\MailSenders\Virtual(), NULL);
		$content = $msh->render(NULL);
		$msh->send($content, 'user-to@php.com');
		$msh->closeQueue();
		$sent = $ml->getMailSender()->getSent();
		$this->assertCount(0, $sent);								// no mail sent yet, as we use a queue
		
		$msq = Store::read($this->_queuePath, true);
		$queues = $msq->getList(Store::SORT_DATE);
		$this->assertCount(1, $queues);
		$key = key($queues);
		$q = current($queues);
		$this->assertEquals('queuename_' . date("Ymd"), $q->title);
		$this->assertEquals(1, $q->count);
		$this->assertEquals(false, $q->locked);
		$this->assertEquals(0, $q->sendOffset);
		$q->send($ml);
		$sent = $ml->getMailSender()->getSent();
		$this->assertCount(1, $sent);								// one mail from queue sent
		$this->assertEquals(true, is_int(strpos($sent[0], 'my template : msh content')));
		$this->assertStringStartsWith( 
				"Content-Type: multipart/alternative;\r\n   boundary=\"" . $content->getSeparator() . "\"\r\n" .
				"MIME-Version: 1.0\r\n" . 
				"From: unit-test@php.com\r\n" .
				"X-MailSenderQueue: " . $q->id . "\r\n" .
				"To: user-to@php.com\r\n" .
				"Subject: " . Mailer::encodeSubject('test subject') . "\r\n" .
				"X-Priority: 1\r\n" .
				"Importance: High\r\n" . 
				"Delivered-To: user-to@php.com\r\n" .
				"\r\n" .
				"--" . $content->getSeparator() . "\r\n",
		
				$sent[0]);
				
				
		$msh = new MailSenderHelper($ml, 'msh content', 'text/plain', 'unit-test@php.com', 'test subject');
		$ml->setMailSender(new \Nettools\Mailing\MailSenders\Virtual(), NULL);
		$content = $msh->render(NULL);
		$msh->send($content, 'user-to@php.com');
		$msh->destruct();
		$sent = $ml->getMailSender()->getSent();
		$this->assertCount(0, $sent);								// destruct drops emails stored in Virtual

				
				
		$ml->setMailSender(new \Nettools\Mailing\MailSenders\Virtual(), NULL);
		$amsh = new Attachments(new MailSenderHelper($ml, 'content with attachments.', 'text/plain', 'unit-test@php.com', 'test subject'));
		$amsh->setAttachmentsCount(1);
		$this->assertInstanceOf(\Nettools\Mailing\MailSenderHelpers\Attachments::class, $amsh->setAttachment($this->_fatt, 'attachment.txt', 'text/plain', 0));	// tester chainage



		$ml->setMailSender(new \Nettools\Mailing\MailSenders\Virtual(), NULL);
		$amsh = new Attachments(new MailSenderHelper($ml, 'content with attachments.', 'text/plain', 'unit-test@php.com', 'test subject'));
		$this->assertInstanceOf(\Nettools\Mailing\MailSenderHelpers\Attachments::class,
								
								$amsh->setAttachments(
											array(
												array('file'=>$this->_fatt, 'filename'=>'attachment.txt', 'contentType'=>'text/plain'),
												array('file'=>$this->_fatt, 'filename'=>'attachment2.txt', 'contentType'=>'text/plain')
												)
											)
										
										);	// tester chainage
		
		
		$ml->setMailSender(new \Nettools\Mailing\MailSenders\Virtual(), NULL);
		$amsh = new Attachments(new MailSenderHelper($ml, 'content with attachments.', 'text/plain', 'unit-test@php.com', 'test subject'));
		$amsh->setAttachmentsCount(2);
		$amsh->setAttachment($this->_fatt, 'attachment1.txt', 'text/plain', 0);
		$amsh->setAttachment($this->_fatt, 'attachment2.txt', 'text/plain', 1);
		$content = $amsh->render(NULL);
		$msh->send($content, 'user-to@php.com');
		$this->assertEquals(false, $r);							// renvoie FALSE si OK
		$sent = $ml->getMailSender()->getSent();
		$this->assertCount(1, $sent);								// aucun mail réellement envoyé, puisqu'on utilise une file
		$this->assertEquals( 
				"Content-Type: multipart/mixed;\r\n   boundary=\"" . $content->getSeparator() . "\"\r\n" .
				"MIME-Version: 1.0\r\n" . 
				"From: unit-test@php.com\r\n" .
				"To: user-to@php.com\r\n" .
				"Subject: " . Mailer::encodeSubject('test subject') . "\r\n" .
				"X-Priority: 1\r\n" .
				"Importance: High\r\n" . 
				"Delivered-To: user-to@php.com\r\n" .
				"\r\n" .
				"--" . $content->getSeparator() . "\r\n" .
				"Content-Type: multipart/alternative;\r\n" .
				"   boundary=\"" . $content->getPart(0)->getSeparator() . "\"\r\n" .
				"\r\n" . 
				"--" . $content->getPart(0)->getSeparator() . "\r\n" .
				"Content-Type: text/plain; charset=UTF-8\r\n" .
				"Content-Transfer-Encoding: quoted-printable\r\n" .
				"\r\n" .
				"content with attachments.\r\n" .
				"\r\n" . 
				"--" . $content->getPart(0)->getSeparator() . "\r\n" .
				"Content-Type: text/html; charset=UTF-8\r\n" .
				"Content-Transfer-Encoding: quoted-printable\r\n" .
				"\r\n" .
				"content with attachments.\r\n" .
				"\r\n" . 
				"--" . $content->getPart(0)->getSeparator() . "--\r\n" .
				"\r\n" . 
				"--" . $content->getSeparator() . "\r\n" .
				"Content-Type: text/plain;\r\n" .
				"   name=\"attachment1.txt\"\r\n" .
				"Content-Transfer-Encoding: base64\r\n" .
				"Content-Disposition: attachment;\r\n" .
				"   filename=\"attachment1.txt\"\r\n" .
				"\r\n" .
				self::$_fatt_content_b64 . "\r\n" .
				"\r\n" . 
				"--" . $content->getSeparator() . "\r\n" .
				"Content-Type: text/plain;\r\n" .
				"   name=\"attachment2.txt\"\r\n" .
				"Content-Transfer-Encoding: base64\r\n" .
				"Content-Disposition: attachment;\r\n" .
				"   filename=\"attachment2.txt\"\r\n" .
				"\r\n" .
				self::$_fatt_content_b64 . "\r\n" .
				"\r\n" . 
				"--" . $content->getSeparator() . "--",
			
				$sent[0]
			);
				
				

		$ml->setMailSender(new \Nettools\Mailing\MailSenders\Virtual(), NULL);
		$amsh = new Embeddings(new MailSenderHelper($ml, 'content with embeddings.', 'text/plain', 'unit-test@php.com', 'test subject'));
		$amsh->setEmbeddingsCount(1);
		$this->assertInstanceOf(\Nettools\Mailing\MailSenderHelpers\Embeddings::class, $amsh->setEmbedding($this->_fatt, 'text/plain', 'cid-123', 0));	// tester chainage



		$ml->setMailSender(new \Nettools\Mailing\MailSenders\Virtual(), NULL);
		$amsh = new Embeddings(new MailSenderHelper($ml, 'content with embeddings.', 'text/plain', 'unit-test@php.com', 'test subject'));
		$this->assertInstanceOf(\Nettools\Mailing\MailSenderHelpers\Embeddings::class,
								
								$amsh->setEmbeddings(
											array(
												array('file'=>$this->_fatt, 'contentType'=>'text/plain', 'cid'=>'cid-123'),
												array('file'=>$this->_fatt, 'contentType'=>'text/plain', 'cid'=>'456')
												)
											)
										
										);	// tester chainage
		
		

		$ml->setMailSender(new \Nettools\Mailing\MailSenders\Virtual(), NULL);
		$amsh = new Embeddings(new MailSenderHelper($ml, 'content with embeddings.', 'text/plain', 'unit-test@php.com', 'test subject'));
		$amsh->setEmbeddingsCount(1);
		$amsh->setEmbedding($this->_fatt, 'text/plain', 'cid-123', 0);
		$content = $amsh->render(NULL);
		$msh->send($content, 'user-to@php.com');
		$sent = $ml->getMailSender()->getSent();
		$this->assertCount(1, $sent);								// aucun mail réellement envoyé, puisqu'on utilise une file
		$this->assertEquals(
				"Content-Type: multipart/related;\r\n   boundary=\"" . $content->getSeparator() . "\"\r\n" .
				"MIME-Version: 1.0\r\n" . 
				"From: unit-test@php.com\r\n" .
				"To: user-to@php.com\r\n" .
				"Subject: " . Mailer::encodeSubject('test subject') . "\r\n" .
				"X-Priority: 1\r\n" .
				"Importance: High\r\n" . 
				"Delivered-To: user-to@php.com\r\n" .
				"\r\n" .
				"--" . $content->getSeparator() . "\r\n" .
				"Content-Type: multipart/alternative;\r\n" .
				"   boundary=\"" . $content->getPart(0)->getSeparator() . "\"\r\n" .
				"\r\n" . 
				"--" . $content->getPart(0)->getSeparator() . "\r\n" .
				"Content-Type: text/plain; charset=UTF-8\r\n" .
				"Content-Transfer-Encoding: quoted-printable\r\n" .
				"\r\n" .
				"content with embeddings.\r\n" .
				"\r\n" . 
				"--" . $content->getPart(0)->getSeparator() . "\r\n" .
				"Content-Type: text/html; charset=UTF-8\r\n" .
				"Content-Transfer-Encoding: quoted-printable\r\n" .
				"\r\n" .
				"content with embeddings.\r\n" .
				"\r\n" . 
				"--" . $content->getPart(0)->getSeparator() . "--\r\n" .
				"\r\n" . 
				"--" . $content->getSeparator() . "\r\n" .
				"Content-Type: text/plain\r\n" .
				"Content-Transfer-Encoding: base64\r\n" .
				"Content-Disposition: inline;\r\n" .
				"   filename=\"cid-123\"\r\n" .
				"Content-ID: <cid-123>\r\n" .
				"\r\n" .
				self::$_fatt_content_b64 . "\r\n" .
				"\r\n" . 
				"--" . $content->getSeparator() . "--",
			
				$sent[0]
			);
	}
}


?>