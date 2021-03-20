<?php

namespace Nettools\Mailing\Tests;



use \Nettools\Mailing\MailPieces\MailTextPlainContent;
use \Nettools\Mailing\MailPieces\MailTextHtmlContent;
use \Nettools\Mailing\Mailer;
use \Nettools\Mailing\MailSenders\Virtual;
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
		
		
		// getAttachmentsCache
		$this->assertInstanceOf('Nettools\Core\Containers\Cache', Mailer::getAttachmentsCache());


		// getEmbeddingsCache
		$this->assertInstanceOf('Nettools\Core\Containers\Cache', Mailer::getEmbeddingsCache());
        
        
        // encodeSubject
		$this->assertEquals('=?utf-8?B?'.base64_encode('Title with accents : é').'?=', Mailer::encodeSubject('Title with accents : é'));

		
        // htmlMinify
		$this->assertEquals('ab cd ef gh ij', Mailer::htmlMinify("ab\r\ncd   ef\tgh\nij"));
		
		
        // html2plain
		$this->assertEquals("é \r\nTITLE\r\n\r\nparagraph1\r\n\r\nparagraph2\r\n\r\n- enum1\r\n- enum2\r\n\r\n" .
							"click here ( http://www.weblink.com )" .
							"\r\n\r\nnewline and tabs" .
							"\r\nspaces on new line beginning",
                            
                            Mailer::html2plain('<b>é</b> <h1>title</h1> <p>paragraph1</p><p>paragraph2</p> <ul><li>enum1</li><li>enum2</li></ul>' .
												'<a href="http://www.web.com"> <img> </a> <a href="http://www.weblink.com">click here</a>' . 
												"<br><br><br><br>newline\tand\ttabs<br>" .
												"<p>  spaces on new line beginning</p>"));	/* no more than 2 newlines */
							
        // plain2html
		$this->assertEquals('<b>&lt;&eacute;&gt;</b><br><b style="color:#DD0000;">red</b><br><br><a href="http://www.website.com">http://www.website.com</a>',
                           Mailer::plain2html("**<é>**\r\n==red==\r\n\r\nhttp://www.website.com"));

        
        // getMailSender
		$ml = Mailer::getDefault();
		$this->assertInstanceOf(\Nettools\Mailing\MailSenders\PHPMail::class, $ml->getMailSender());


		try
		{
        	// setMailSender
			$ml->setMailSender(new Virtual());
			$this->assertInstanceOf(Virtual::class, $ml->getMailSender());
		}
		finally
		{
			$ml->setMailSender(new \Nettools\Mailing\MailSenders\PHPMail());
		}
    }
    
    
    public function testAddTextHtml()
    {
		$obj = Mailer::addTextHtml('Test message', '<b>test</b> message');
		$this->assertInstanceOf('Nettools\Mailing\MailPieces\MailMultipart', $obj);
		$this->assertEquals('text/plain', $obj->getPart(0)->getContentType());
		$this->assertEquals('text/html', $obj->getPart(1)->getContentType());
		$this->assertEquals('Test message', $obj->getPart(0)->getContent());
		$this->assertEquals('<b>test</b> message', $obj->getPart(1)->getContent());
    }


    public function testAddTextHtmlFromHtml()
    {
		$obj = Mailer::addTextHtmlFromHtml('<b>test</b> message', 'Content :<br>--%content%--');
		$this->assertInstanceOf('Nettools\Mailing\MailPieces\MailMultipart', $obj);
		$this->assertEquals('text/plain', $obj->getPart(0)->getContentType());
		$this->assertEquals('text/html', $obj->getPart(1)->getContentType());
		$this->assertEquals("Content :\r\n--test message--", $obj->getPart(0)->getContent());
		$this->assertEquals('Content :<br>--<b>test</b> message--', $obj->getPart(1)->getContent());
    }
    
    
    public function testAddTextHtmlFromText()
    {
		$obj = Mailer::addTextHtmlFromText('**test** message', 'Content :<br>--%content%--');
		$this->assertInstanceOf('Nettools\Mailing\MailPieces\MailMultipart', $obj);
		$this->assertEquals('text/plain', $obj->getPart(0)->getContentType());
		$this->assertEquals('text/html', $obj->getPart(1)->getContentType());
		$this->assertEquals("Content :\r\n--**test** message--", $obj->getPart(0)->getContent());
		$this->assertEquals('Content :<br>--<b>test</b> message--', $obj->getPart(1)->getContent());
    }
    
    
    public function testAddAlternativeObject()
    {
		$obj = Mailer::addAlternativeObject(new MailTextPlainContent('textplain content'), new MailTextHtmlContent('html content'));
		$this->assertInstanceOf('Nettools\Mailing\MailPieces\MailMultipart', $obj);
		$this->assertEquals('text/plain', $obj->getPart(0)->getContentType());
		$this->assertEquals('text/html', $obj->getPart(1)->getContentType());
		$this->assertEquals('textplain content', $obj->getPart(0)->getContent());
		$this->assertEquals('html content', $obj->getPart(1)->getContent());
    }
    
    
    public function testCreateText()
    {
		$obj = Mailer::createText('textplain content');
		$this->assertInstanceOf('Nettools\Mailing\MailPieces\MailTextPlainContent', $obj);
		$this->assertEquals('text/plain', $obj->getContentType());
		$this->assertEquals('textplain content', $obj->getContent());
    }
    
    
    public function testCreateHtml()
    {
		$obj = Mailer::createHtml('html content');
		$this->assertInstanceOf('Nettools\Mailing\MailPieces\MailTextHtmlContent', $obj);
		$this->assertEquals('text/html', $obj->getContentType());
		$this->assertEquals('html content', $obj->getContent());
    }
    
    
    public function testCreateEmbedding()
    {
		$obj = Mailer::createEmbedding(self::$_fatt, 'text/plain', 'cid-123');
		$this->assertInstanceOf('Nettools\Mailing\MailPieces\MailEmbedding', $obj);
		$this->assertEquals('text/plain', $obj->getContentType());
    }
    
    
    public function testCreateAttachment()
    {
		$obj = Mailer::createAttachment(self::$_fatt, 'attach.txt', 'text/plain');
		$this->assertInstanceOf('Nettools\Mailing\MailPieces\MailAttachment', $obj);
		$this->assertEquals('text/plain', $obj->getContentType());
    }
    
    
    public function testAddAttachment()
    {
		$obj = Mailer::addAttachment(new MailTextPlainContent('textplain content'), self::$_fatt, 'attach.txt', 'text/plain');
		$this->assertInstanceOf('Nettools\Mailing\MailPieces\MailMultipart', $obj);
		$this->assertEquals('multipart/mixed', $obj->getContentType());
		$this->assertEquals( 
				"--" . $obj->getSeparator() . "\r\n" .
				"Content-Type: text/plain; charset=UTF-8\r\n" .
				"Content-Transfer-Encoding: quoted-printable\r\n" .
				"\r\n" .
				"textplain content\r\n" .
				"\r\n" .
				"--" . $obj->getSeparator() . "\r\n" .
				"Content-Type: text/plain;\r\n   name=\"attach.txt\"\r\n" .
				"Content-Transfer-Encoding: base64\r\n" .
				"Content-Disposition: attachment;\r\n   filename=\"attach.txt\"\r\n" .
				"\r\n" .
				self::$_fatt_content_b64 . "\r\n" .
				"\r\n" .
				"--" . $obj->getSeparator() . "--",
        
                $obj->getContent()
            );
    }
    
    
    public function testAddAttachments()
    {
		$obj = Mailer::addAttachments(new MailTextPlainContent('textplain content'), 
										[
											array('file'=>self::$_fatt, 'filename'=>'attach.txt', 'filetype'=>'text/plain'),
											array('file'=>self::$_fatt2, 'filename'=>'attach2.txt', 'filetype'=>'text/plain')
										]
									);
		$this->assertInstanceOf('Nettools\Mailing\MailPieces\MailMultipart', $obj);
		$this->assertEquals('multipart/mixed', $obj->getContentType());
    }
    
    
    public function testAddAttachmentObject()
    {
		$obj = Mailer::createAttachment(self::$_fatt, 'attach.txt', 'text/plain');
		$mail = Mailer::createText('textplain content');
		$matt = Mailer::addAttachmentObject($mail, $obj);
		$this->assertInstanceOf('Nettools\Mailing\MailPieces\MailMultipart', $matt);
		$this->assertEquals('multipart/mixed', $matt->getContentType());
    }
    
    
    public function testAddAttachmentObjects()
    {
		$obj = Mailer::createAttachment(self::$_fatt, 'attach.txt', 'text/plain');
		$obj2 = Mailer::createAttachment(self::$_fatt2, 'attach2.txt', 'text/plain');
		$mail = Mailer::createText('textplain content');
		$matt = Mailer::addAttachmentObjects($mail, [$obj, $obj2]);
		$this->assertInstanceOf('Nettools\Mailing\MailPieces\MailMultipart', $matt);
		$this->assertEquals('multipart/mixed', $matt->getContentType());
    }
    
    
    public function testAddEmbedding()
    {
		$obj = Mailer::addEmbedding(new MailTextPlainContent('textplain content'), self::$_fatt, 'text/plain', 'cid-123');
		$this->assertInstanceOf('Nettools\Mailing\MailPieces\MailMultipart', $obj);
		$this->assertEquals('multipart/related', $obj->getContentType());

		$this->assertEquals(
				"--" . $obj->getSeparator() . "\r\n" .
				"Content-Type: text/plain; charset=UTF-8\r\n" .
				"Content-Transfer-Encoding: quoted-printable\r\n" .
				"\r\n" .
				"textplain content\r\n" .
				"\r\n" .
				"--" . $obj->getSeparator() . "\r\n" .
				"Content-Type: text/plain\r\n" .
				"Content-Transfer-Encoding: base64\r\n" .
				"Content-Disposition: inline;\r\n   filename=\"cid-123\"\r\n" .
				"Content-ID: <cid-123>\r\n" .
				"\r\n" .
				self::$_fatt_content_b64 . "\r\n" .
				"\r\n" .
				"--" . $obj->getSeparator() . "--",
                
                $obj->getContent()
            );		
    }
    
    
    public function testAddEmbeddings()
    {
		$obj = Mailer::addEmbeddings(new MailTextPlainContent('textplain content'), 
										[
											array('file'=>self::$_fatt, 'filetype'=>'text/plain', 'cid'=>'cid-123'),
											array('file'=>self::$_fatt2, 'filetype'=>'text/plain', 'cid'=>'cid-456')
										]
									);
		$this->assertInstanceOf('Nettools\Mailing\MailPieces\MailMultipart', $obj);
		$this->assertEquals('multipart/related', $obj->getContentType());
    }
    
    
    public function testAddEmbeddingObject()
    {
		$obj = Mailer::createEmbedding(self::$_fatt, 'text/plain', 'cid-123');
		$mail = Mailer::createText('textplain content');
		$matt = Mailer::addEmbeddingObject($mail, $obj);
		$this->assertInstanceOf('Nettools\Mailing\MailPieces\MailMultipart', $matt);
		$this->assertEquals('multipart/related', $matt->getContentType());
    }
    
    
    public function testAddEmbeddingObjects()
    {
		$obj = Mailer::createEmbedding(self::$_fatt, 'text/plain', 'cid-123');
		$obj2 = Mailer::createEmbedding(self::$_fatt2, 'text/plain', 'cid-456');
		$mail = Mailer::createText('textplain content');
		$matt = Mailer::addEmbeddingObjects($mail, [$obj, $obj2]);
		$this->assertInstanceOf('Nettools\Mailing\MailPieces\MailMultipart', $matt);
		$this->assertEquals('multipart/related', $matt->getContentType());
    }
    
    
    public function testAddHeader()
    {
		$this->assertEquals('From: user@domain.tld', Mailer::addHeader('', 'From: user@domain.tld'));
		$this->assertEquals('From: user@domain.tld', Mailer::addHeader('From: user@domain.tld', ''));
		$this->assertEquals("From: user@domain.tld\r\nTo: other@domain.tld", Mailer::addHeader('From: user@domain.tld', 'To: other@domain.tld'));
		$this->assertEquals("From: other-user@domain.tld\r\nBcc: bcc-user@domain.tld", Mailer::addHeader("From: user@domain.tld\r\nBcc: bcc-user@domain.tld", 'From: other-user@domain.tld'));
		$this->assertEquals("Content-Type: multipart/mixed;\r\n   boundary=\"xyz1234\"\r\nFrom: user@domain.tld", 
                                Mailer::addHeader("Content-Type: multipart/mixed;\r\n   boundary=\"xyz1234\"", 'From: user@domain.tld'));
		$this->assertEquals("Content-Type: text/plain; charset=UTF-8", 
                                Mailer::addHeader("Content-Type: multipart/mixed;\r\n   boundary=\"xyz1234\"", "Content-Type: text/plain; charset=UTF-8"));
		$this->assertEquals("Content-Type: multipart/mixed;\r\n   boundary=\"abc5678\"",
                                Mailer::addHeader("Content-Type: multipart/mixed;\r\n   boundary=\"xyz1234\"", "Content-Type: multipart/mixed;\r\n   boundary=\"abc5678\""));
		$this->assertEquals("Content-Type: multipart/mixed;\r\n   boundary=\"abc5678\"\r\nFrom: user@domain.tld",
                                Mailer::addHeader("Content-Type: multipart/mixed;\r\n   boundary=\"xyz1234\"\r\n   other=\"testfolding\"\r\nFrom: user@domain.tld", "Content-Type: multipart/mixed;\r\n   boundary=\"abc5678\""));
    }
    
    
    public function testAddHeaders()
    {
		$this->assertEquals('From: user@domain.tld', Mailer::addHeaders('', 'From: user@domain.tld'));
		$this->assertEquals("From: user@domain.tld\r\nTo: other@domain.tld\r\nBcc: bcc-user@domain.tld", Mailer::addHeaders('From: user@domain.tld', "To: other@domain.tld\r\nBcc: bcc-user@domain.tld"));
    }
    
    
    public function testHeadersToArray()
    {
		$this->assertEquals(array('From'=>'user@domain.tld', 'Content-Type'=>"multipart/mixed;\r\n   boundary=\"abc5678\""),
                            Mailer::headersToArray("From: user@domain.tld\r\nContent-Type: multipart/mixed;\r\n   boundary=\"abc5678\""));
		$this->assertEquals(array(), Mailer::headersToArray(""));
    }
    
    
    public function testArrayToHeaders()
    {
		$this->assertEquals("From: user@domain.tld\r\nContent-Type: multipart/mixed;\r\n   boundary=\"abc5678\"",
                            Mailer::arrayToHeaders(array('From'=>'user@domain.tld', 'Content-Type'=>"multipart/mixed;\r\n   boundary=\"abc5678\"")));
		$this->assertEquals("", Mailer::arrayToHeaders(array()));
    }
    
    
    public function testRemoveHeader()
    {
		$this->assertEquals('From: user@domain.tld', Mailer::removeHeader("From: user@domain.tld\r\nBcc: user-bcc@domain.tld", 'Bcc'));
		$this->assertEquals("From: user@domain.tld\r\nBcc: user-bcc@domain.tld", Mailer::removeHeader("From: user@domain.tld\r\nBcc: user-bcc@domain.tld", NULL));
		$this->assertEquals("", Mailer::removeHeader("", 'Bcc'));
		$this->assertEquals("From: user@domain.tld", Mailer::removeHeader("From: user@domain.tld", 'Content-Type'));
		$this->assertEquals("", Mailer::removeHeader("", ''));
    }
    
    
    public function testPatch()
    {
		$obj = Mailer::addAttachment(
					Mailer::addAlternativeObject(
								new MailTextPlainContent('http://www.web.com ; textplain content'),
								new MailTextHtmlContent('<a href="http://www.web.com">texthtml</a>')
							),
					self::$_fatt, 'attach.txt', 'text/plain'
				);
		$obj = Mailer::patch($obj, function($code, $ctype, $data){return $code . " with appended value=$data.";}, 'nodata');
		$this->assertEquals('http://www.web.com ; textplain content with appended value=nodata.', $obj->getPart(0)->getPart(0)->getText());
		$this->assertEquals('<a href="http://www.web.com">texthtml</a> with appended value=nodata.', $obj->getPart(0)->getPart(1)->getHtml());
    }
    
    

    public function testRender()
    {
		$obj = Mailer::createText('textplain content');
		Mailer::render($obj);
		$this->assertMatchesRegularExpression('/MIME-Version: 1\\.0/', $obj->getFullHeaders());
    }
    
    
    public function testSendmail()
    {
		$ml = new Mailer(new Virtual());

        $obj = Mailer::addAttachment(new MailTextPlainContent('textplain content'), self::$_fatt, 'attach.txt', 'text/plain');
		$ml->sendmail($obj, 'unit-test@php.com', 'unit-test-recipient@php.com', 'Mail subject', false);
		$sent = $ml->getMailSender()->getSent();
		$this->assertEquals( 
				"Content-Type: multipart/mixed;\r\n" .
				"   boundary=\"" . $obj->getSeparator() . "\"\r\n" .
				"MIME-Version: 1.0\r\n" .
				"From: unit-test@php.com\r\n" .
				"To: unit-test-recipient@php.com\r\n" .
				"Subject: Mail subject\r\n" .
				"X-Priority: 1\r\n" .
				"Importance: High\r\n" . 
				"Delivered-To: unit-test-recipient@php.com\r\n" .
				"\r\n" .  
				"--" . $obj->getSeparator() . "\r\n" .
				"Content-Type: text/plain; charset=UTF-8\r\n" .
				"Content-Transfer-Encoding: quoted-printable\r\n" .
				"\r\n" .
				"textplain content\r\n" .
				"\r\n" .
				"--" . $obj->getSeparator() . "\r\n" .
				"Content-Type: text/plain;\r\n   name=\"attach.txt\"\r\n" .
				"Content-Transfer-Encoding: base64\r\n" .
				"Content-Disposition: attachment;\r\n   filename=\"attach.txt\"\r\n" .
				"\r\n" .
				self::$_fatt_content_b64 . "\r\n" .
				"\r\n" .
				"--" . $obj->getSeparator() . "--",
            
                $sent[0]
			);
    }
    
    
    public function testSendmail_raw()
    {
		$obj = new MailTextPlainContent('textplain content');
		$ml = new Mailer(new Virtual());
		Mailer::render($obj);
		$ml->sendmail_raw('user1@test.com,user2@test.com', 'test subject', $obj->getContent(), Mailer::addHeader($obj->getFullHeaders(), 'From: unit-test@php.com'), false); 
		$sent = $ml->getMailSender()->getSent();
		$this->assertEquals(2, count($sent));
		$this->assertEquals(
				"Content-Type: text/plain; charset=UTF-8\r\n" .
				"Content-Transfer-Encoding: quoted-printable\r\n" .
				"MIME-Version: 1.0\r\n" .
				"From: unit-test@php.com\r\n" .
				"To: user1@test.com\r\n" .
				"Subject: test subject\r\n" .
				"X-Priority: 1\r\n" .
				"Importance: High\r\n" . 
				"Delivered-To: user1@test.com\r\n" .
				"\r\n" .
				"textplain content",
            
                $sent[0]
			);
		$this->assertEquals(
				"Content-Type: text/plain; charset=UTF-8\r\n" .
				"Content-Transfer-Encoding: quoted-printable\r\n" .
				"MIME-Version: 1.0\r\n" .
				"From: unit-test@php.com\r\n" .
				"To: user2@test.com\r\n" .
				"Subject: test subject\r\n" .
				"X-Priority: 1\r\n" .
				"Importance: High\r\n" . 
				"Delivered-To: user2@test.com\r\n" .
				"\r\n" .
				"textplain content",
            
                $sent[1]
			);
		
        
        // by setting the mailsender, we create another strategy; previously sent emails are lost
        $ml->setMailSender(new Virtual());
		Mailer::render($obj);
		$ml->sendmail_raw(array('user1@test.com','user2@test.com'), 'test subject', $obj->getContent(), Mailer::addHeader($obj->getFullHeaders(), 'From: unit-test@php.com'), false); 
		$sent = $ml->getMailSender()->getSent();
		$this->assertEquals(2, count($sent));    
    }
}

?>