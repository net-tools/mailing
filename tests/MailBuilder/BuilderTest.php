<?php

namespace Nettools\Mailing\Builder\Tests;



use \Nettools\Mailing\MailBuilder\TextPlainContent;
use \Nettools\Mailing\MailBuilder\TextHtmlContent;
use \Nettools\Mailing\MailBuilder\Builder;
use \org\bovigo\vfs\vfsStream;





class BuilderTest extends \PHPUnit\Framework\TestCase
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
        // htmlMinify
		$this->assertEquals('ab cd ef gh ij', Builder::htmlMinify("ab\r\ncd   ef\tgh\nij"));
		
		
        // html2plain
		$this->assertEquals("é \r\nTITLE\r\n\r\nparagraph1\r\n\r\nparagraph2\r\n\r\n- enum1\r\n- enum2\r\n\r\n" .
							"click here ( http://www.weblink.com )" .
							"\r\n\r\nnewline and tabs" .
							"\r\nspaces on new line beginning",
                            
                            Builder::html2plain('<b>é</b> <h1>title</h1> <p>paragraph1</p><p>paragraph2</p> <ul><li>enum1</li><li>enum2</li></ul>' .
												'<a href="http://www.web.com"> <img> </a> <a href="http://www.weblink.com">click here</a>' . 
												"<br><br><br><br>newline\tand\ttabs<br>" .
												"<p>  spaces on new line beginning</p>"));	/* no more than 2 newlines */
							
        // plain2html
		$this->assertEquals('<b>&lt;&eacute;&gt;</b><br><b style="color:#DD0000;">red</b><br><br><a href="http://www.website.com">http://www.website.com</a>',
                           Builder::plain2html("**<é>**\r\n==red==\r\n\r\nhttp://www.website.com"));
    }
    
    
    public function testAddTextHtml()
    {
		$obj = Builder::addTextHtml('Test message', '<b>test</b> message');
		$this->assertInstanceOf('Nettools\Mailing\MailBuilder\Multipart', $obj);
		$this->assertEquals('text/plain', $obj->getPart(0)->getContentType());
		$this->assertEquals('text/html', $obj->getPart(1)->getContentType());
		$this->assertEquals('Test message', $obj->getPart(0)->getContent());
		$this->assertEquals('<b>test</b> message', $obj->getPart(1)->getContent());
    }


    public function testAddTextHtmlFromHtml()
    {
		$obj = Builder::addTextHtmlFromHtml('<b>test</b> message', 'Content :<br>--%content%--');
		$this->assertInstanceOf('Nettools\Mailing\MailBuilder\Multipart', $obj);
		$this->assertEquals('text/plain', $obj->getPart(0)->getContentType());
		$this->assertEquals('text/html', $obj->getPart(1)->getContentType());
		$this->assertEquals("Content :\r\n--test message--", $obj->getPart(0)->getContent());
		$this->assertEquals('Content :<br>--<b>test</b> message--', $obj->getPart(1)->getContent());
    }
    
    
    public function testAddTextHtmlFromText()
    {
		$obj = Builder::addTextHtmlFromText('**test** message', 'Content :<br>--%content%--');
		$this->assertInstanceOf('Nettools\Mailing\MailBuilder\Multipart', $obj);
		$this->assertEquals('text/plain', $obj->getPart(0)->getContentType());
		$this->assertEquals('text/html', $obj->getPart(1)->getContentType());
		$this->assertEquals("Content :\r\n--**test** message--", $obj->getPart(0)->getContent());
		$this->assertEquals('Content :<br>--<b>test</b> message--', $obj->getPart(1)->getContent());
    }
    
    
    public function testAddAlternativeObject()
    {
		$obj = Builder::addAlternativeObject(new TextPlainContent('textplain content'), new TextHtmlContent('html content'));
		$this->assertInstanceOf('Nettools\Mailing\MailBuilder\Multipart', $obj);
		$this->assertEquals('text/plain', $obj->getPart(0)->getContentType());
		$this->assertEquals('text/html', $obj->getPart(1)->getContentType());
		$this->assertEquals('textplain content', $obj->getPart(0)->getContent());
		$this->assertEquals('html content', $obj->getPart(1)->getContent());
    }
    
    
    public function testCreateText()
    {
		$obj = Builder::createText('textplain content');
		$this->assertInstanceOf('Nettools\Mailing\MailBuilder\TextPlainContent', $obj);
		$this->assertEquals('text/plain', $obj->getContentType());
		$this->assertEquals('textplain content', $obj->getContent());
    }
    
    
    public function testCreateHtml()
    {
		$obj = Builder::createHtml('html content');
		$this->assertInstanceOf('Nettools\Mailing\MailBuilder\TextHtmlContent', $obj);
		$this->assertEquals('text/html', $obj->getContentType());
		$this->assertEquals('html content', $obj->getContent());
    }
    
    
    public function testCreateEmbedding()
    {
		$obj = Builder::createEmbedding(self::$_fatt, 'text/plain', 'cid-123');
		$this->assertInstanceOf('Nettools\Mailing\MailBuilder\Embedding', $obj);
		$this->assertEquals('text/plain', $obj->getContentType());
    }
    
    
    public function testCreateAttachment()
    {
		$obj = Builder::createAttachment(self::$_fatt, 'attach.txt', 'text/plain');
		$this->assertInstanceOf('Nettools\Mailing\MailBuilder\Attachment', $obj);
		$this->assertEquals('text/plain', $obj->getContentType());
    }
    
    
    public function testAddAttachment()
    {
		$obj = Builder::addAttachment(new TextPlainContent('textplain content'), self::$_fatt, 'attach.txt', 'text/plain');
		$this->assertInstanceOf('Nettools\Mailing\MailBuilder\Multipart', $obj);
		$this->assertEquals('multipart/mixed', $obj->getContentType());
		$this->assertEquals( 
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
        
                $obj->getContent()
            );
    }
    
    
    public function testAddAttachments()
    {
		$obj = Builder::addAttachments(new TextPlainContent('textplain content'), 
										[
											array('file'=>self::$_fatt, 'filename'=>'attach.txt', 'filetype'=>'text/plain'),
											array('file'=>self::$_fatt2, 'filename'=>'attach2.txt', 'filetype'=>'text/plain')
										]
									);
		$this->assertInstanceOf('Nettools\Mailing\MailBuilder\Multipart', $obj);
		$this->assertEquals('multipart/mixed', $obj->getContentType());
    }
    
    
    public function testAddAttachmentObject()
    {
		$obj = Builder::createAttachment(self::$_fatt, 'attach.txt', 'text/plain');
		$mail = Builder::createText('textplain content');
		$matt = Builder::addAttachmentObject($mail, $obj);
		$this->assertInstanceOf('Nettools\Mailing\MailBuilder\Multipart', $matt);
		$this->assertEquals('multipart/mixed', $matt->getContentType());
    }
    
    
    public function testAddAttachmentObjects()
    {
		$obj = Builder::createAttachment(self::$_fatt, 'attach.txt', 'text/plain');
		$obj2 = Builder::createAttachment(self::$_fatt2, 'attach2.txt', 'text/plain');
		$mail = Builder::createText('textplain content');
		$matt = Builder::addAttachmentObjects($mail, [$obj, $obj2]);
		$this->assertInstanceOf('Nettools\Mailing\MailBuilder\Multipart', $matt);
		$this->assertEquals('multipart/mixed', $matt->getContentType());
    }
    
    
    public function testAddEmbedding()
    {
		$obj = Builder::addEmbedding(new TextPlainContent('textplain content'), self::$_fatt, 'text/plain', 'cid-123');
		$this->assertInstanceOf('Nettools\Mailing\MailBuilder\Multipart', $obj);
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
				"Content-Disposition: inline;\r\n filename=\"cid-123\"\r\n" .
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
		$obj = Builder::addEmbeddings(new TextPlainContent('textplain content'), 
										[
											array('file'=>self::$_fatt, 'filetype'=>'text/plain', 'cid'=>'cid-123'),
											array('file'=>self::$_fatt2, 'filetype'=>'text/plain', 'cid'=>'cid-456')
										]
									);
		$this->assertInstanceOf('Nettools\Mailing\MailBuilder\Multipart', $obj);
		$this->assertEquals('multipart/related', $obj->getContentType());
    }
    
    
    public function testAddEmbeddingObject()
    {
		$obj = Builder::createEmbedding(self::$_fatt, 'text/plain', 'cid-123');
		$mail = Builder::createText('textplain content');
		$matt = Builder::addEmbeddingObject($mail, $obj);
		$this->assertInstanceOf('Nettools\Mailing\MailBuilder\Multipart', $matt);
		$this->assertEquals('multipart/related', $matt->getContentType());
    }
    
    
    public function testAddEmbeddingObjects()
    {
		$obj = Builder::createEmbedding(self::$_fatt, 'text/plain', 'cid-123');
		$obj2 = Builder::createEmbedding(self::$_fatt2, 'text/plain', 'cid-456');
		$mail = Builder::createText('textplain content');
		$matt = Builder::addEmbeddingObjects($mail, [$obj, $obj2]);
		$this->assertInstanceOf('Nettools\Mailing\MailBuilder\Multipart', $matt);
		$this->assertEquals('multipart/related', $matt->getContentType());
    }
    
    
    public function testPatch()
    {
		$obj = Builder::addAttachment(
					Builder::addAlternativeObject(
								new TextPlainContent('http://www.web.com ; textplain content'),
								new TextHtmlContent('<a href="http://www.web.com">texthtml</a>')
							),
					self::$_fatt, 'attach.txt', 'text/plain'
				);
		$obj = Builder::patch($obj, function($code, $ctype, $data){return $code . " with appended value=$data.";}, 'nodata');
		$this->assertEquals('http://www.web.com ; textplain content with appended value=nodata.', $obj->getPart(0)->getPart(0)->getText());
		$this->assertEquals('<a href="http://www.web.com">texthtml</a> with appended value=nodata.', $obj->getPart(0)->getPart(1)->getHtml());
    }
}

?>