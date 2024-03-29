<?php

namespace Nettools\Mailing\Tests;



use \Nettools\Mailing\MailBuilder\TextPlainContent;
use \Nettools\Mailing\Mailer;
use \Nettools\Mailing\MailBuilder\Builder;
use \Nettools\Mailing\MailSenders\Virtual;
use \Nettools\Mailing\MailSenders\MailSender;
use \org\bovigo\vfs\vfsStream;





class MailerTest extends \PHPUnit\Framework\TestCase
{
	protected static $_fatt = NULL;
	protected static $_fatt2 = NULL;
	protected static $_fatt_content = "Attachment sample with accents é.";
	protected static $_fatt_content2 = "Attachment sample 2 with accents é.";
	protected static $_fatt_content_b64 = 'QXR0YWNobWVudCBzYW1wbGUgd2l0aCBhY2NlbnRzIMOpLg==';
	
	
	static public function setUpBeforeClass() :void
	{
		$vfs = vfsStream::setup('root');
        self::$_fatt = vfsStream::newFile('att1.txt')->at($vfs)->setContent(self::$_fatt_content)->url();
        self::$_fatt2 = vfsStream::newFile('att2.txt')->at($vfs)->setContent(self::$_fatt_content2)->url();
	}
	
	
	
    public function testMethods()
    {
        // getDefault
		$this->assertInstanceOf('Nettools\Mailing\Mailer', Mailer::getDefault());
		
		
        // getMailSender
		$ml = Mailer::getDefault();
		$this->assertInstanceOf(\Nettools\Mailing\MailSenders\PHPMail::class, $ml->getMailerEngine()->getMailSender());


		try
		{
        	// setMailSender
			$ml->setMailSender(new Virtual());
			$this->assertInstanceOf(Virtual::class, $ml->getMailerEngine()->getMailSender());
		}
		finally
		{
			$ml->setMailSender(new \Nettools\Mailing\MailSenders\PHPMail());
		}
    }
    
	

    public function testSendmail()
    {
		$ml = new Mailer(new Virtual());

        $obj = Builder::addAttachment(new TextPlainContent('textplain content'), self::$_fatt, 'attach.txt', 'text/plain');
		$ml->sendmail($obj, 'unit-test@php.com', 'unit-test-recipient@php.com', 'Mail subject', false);
		$sent = $ml->getMailerEngine()->getMailSender()->getSent();
		
		// guess Message-ID and Date headers
		$regs = [];
		$this->assertEquals(1, preg_match('/Message-ID: <[0-9a-f]+@php.com>/', $sent[0], $regs));
		$mid = $regs[0];
		$regs = [];
		$this->assertEquals(1, preg_match('/Date: [A-Z][a-z]{2,4}, [0-9]{1,2} [A-Z][a-z]{2,4} 20[0-9]{2} [0-9]{2}:[0-9]{2}:[0-9]{2} .[0-9]{4}/', $sent[0], $regs));
		$dt = $regs[0];

		$this->assertEquals( 
				"Content-Type: multipart/mixed;\r\n" .
				" boundary=\"" . $obj->getSeparator() . "\"\r\n" .
				"From: unit-test@php.com\r\n" .
				"$dt\r\n" .
				"MIME-Version: 1.0\r\n" .
				"To: unit-test-recipient@php.com\r\n" .
				"Subject: Mail subject\r\n" .
				"$mid\r\n" .
				"Delivered-To: unit-test-recipient@php.com\r\n" .
				"\r\n" .  
				"--" . $obj->getSeparator() . "\r\n" .
				"Content-Type: text/plain; charset=UTF-8\r\n" .
				"Content-Transfer-Encoding: quoted-printable\r\n" .
				"\r\n" .
				"textplain content\r\n" .
				"\r\n" .
				"--" . $obj->getSeparator() . "\r\n" .
				"Content-Type: text/plain;\r\n name=\"attach.txt\"\r\n" .
				"Content-Transfer-Encoding: base64\r\n" .
				"Content-Disposition: attachment;\r\n filename=\"attach.txt\"\r\n" .
				"\r\n" .
				self::$_fatt_content_b64 . "\r\n" .
				"\r\n" .
				"--" . $obj->getSeparator() . "--",
            
                $sent[0]
			);
    }
    
	
    public function testSendmail_raw()
    {
		$obj = new TextPlainContent('textplain content');
		$ml = new Mailer(new Virtual());
		$ml->sendmail_raw('user1@test.com,user2@test.com', 'test subject', $obj->getContent(), $obj->getAllHeaders()->set('From', 'unit-test@php.com'), false); 
		$sent = $ml->getMailerEngine()->getMailSender()->getSent();
		$this->assertEquals(2, count($sent));
		
		// guess Message-ID and Date headers
		$regs = [];
		$this->assertEquals(1, preg_match('/Message-ID: <[0-9a-f]+@php.com>/', $sent[0], $regs));
		$mid1 = $regs[0];
		$regs = [];
		$this->assertEquals(1, preg_match('/Date: [A-Z][a-z]{2,4}, [0-9]{1,2} [A-Z][a-z]{2,4} 20[0-9]{2} [0-9]{2}:[0-9]{2}:[0-9]{2} .[0-9]{4}/', $sent[0], $regs));
		$dt1 = $regs[0];
		$regs = [];
		$this->assertEquals(1, preg_match('/Message-ID: <[0-9a-f]+@php.com>/', $sent[1], $regs));
		$mid2 = $regs[0];
		$regs = [];
		$this->assertEquals(1, preg_match('/Date: [A-Z][a-z]{2,4}, [0-9]{1,2} [A-Z][a-z]{2,4} 20[0-9]{2} [0-9]{2}:[0-9]{2}:[0-9]{2} .[0-9]{4}/', $sent[1], $regs));
		$dt2 = $regs[0];

		
		$this->assertEquals(
				"Content-Type: text/plain; charset=UTF-8\r\n" .
				"Content-Transfer-Encoding: quoted-printable\r\n" .
				"From: unit-test@php.com\r\n" .
				"$dt1\r\n" .
				"MIME-Version: 1.0\r\n" .
				"To: user1@test.com,\r\n user2@test.com\r\n" .
				"Subject: test subject\r\n" .
				"$mid1\r\n" .
				"Delivered-To: user1@test.com\r\n" .
				"\r\n" .
				"textplain content",
            
                $sent[0]
			);
		$this->assertEquals(
				"Content-Type: text/plain; charset=UTF-8\r\n" .
				"Content-Transfer-Encoding: quoted-printable\r\n" .
				"From: unit-test@php.com\r\n" .
				"$dt2\r\n" .
				"MIME-Version: 1.0\r\n" .
				"To: user1@test.com,\r\n user2@test.com\r\n" .
				"Subject: test subject\r\n" .
				"$mid2\r\n" .
				"Delivered-To: user2@test.com\r\n" .
				"\r\n" .
				"textplain content",
            
                $sent[1]
			);
		
        
        // by setting the mailsender, we create another strategy; previously sent emails are lost
        $ml->setMailSender(new Virtual());
		$ml->sendmail_raw(array('user1@test.com','user2@test.com'), 'test subject', $obj->getContent(), $obj->getAllHeaders()->set('From', 'unit-test@php.com'), false); 
		$sent = $ml->getMailerEngine()->getMailSender()->getSent();
		$this->assertEquals(2, count($sent));    
    }
}

?>