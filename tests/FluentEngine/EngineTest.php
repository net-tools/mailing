<?php

namespace Nettools\Mailing\FluentEngine\Tests;


use \Nettools\Mailing\MailSenders\Virtual;
use \Nettools\Mailing\FluentEngine\Engine;
use \Nettools\Mailing\Mailer;
use \org\bovigo\vfs\vfsStream;




class EngineTest extends \PHPUnit\Framework\TestCase
{	
	static protected $_file;
	static protected $_fileContent = 'my_content_here';
	static protected $_fileName = 'file1.txt';
	
	
	
	static public function setUpBeforeClass() :void
	{
		$vfs = vfsStream::setup('root');
        self::$_file = vfsStream::newFile(self::$_fileName)->at($vfs)->setContent(self::$_fileContent)->url();
	}
	
	
	
    public function testSimple()
    {
		$ml = new Mailer(new Virtual());
		$e = new Engine($ml);
		
		$e->compose()
			->text('This is **me** !')
			->about('Here is the subject line')
			->to('recipient@domain.name')
			->send();
		
		
		$sent = $ml->getMailerEngine()->getMailSender()->getSent();
		
		$this->assertStringContainsString('This is **me**', $sent[0]);
		$this->assertStringContainsString('This is <b>me</b>', $sent[0]);
		$this->assertStringContainsString('Subject: Here is the subject line', $sent[0]);
		$this->assertStringContainsString('To: recipient@domain.name', $sent[0]);
	}
    
	
      
    public function testRecipients()
    {
		$ml = new Mailer(new Virtual());
		$e = new Engine($ml);
		
		$e->compose()
			->text('This is **me** !')
			->about('Here is the subject line')
			->to('recipient@domain.name')
			->ccTo('cc@domain.name')
			->bccTo('bcc@domain.name')
			->send();
		
		
		$sent = $ml->getMailerEngine()->getMailSender()->getSent();
		
		$this->assertEquals(3, count($sent));		
		$this->assertStringContainsString('Delivered-To: bcc@domain.name', $sent[0]);
		$this->assertStringContainsString('Delivered-To: cc@domain.name', $sent[1]);
		$this->assertStringContainsString('Delivered-To: recipient@domain.name', $sent[2]);
	}
    
	
      
    public function testNoAltPart()
    {
		$ml = new Mailer(new Virtual());
		$e = new Engine($ml);
		
		$e->compose()
			->text('This is **me** !')
			->about('Here is the subject line')
			->to('recipient@domain.name')
			->noAlternatePart()
			->send();
		
		
		$sent = $ml->getMailerEngine()->getMailSender()->getSent();
		
		$this->assertStringContainsString('This is **me**', $sent[0]);
		$this->assertStringNotContainsString('This is <b>me</b>', $sent[0]);
		$this->assertStringContainsString('Subject: Here is the subject line', $sent[0]);
		$this->assertStringContainsString('To: recipient@domain.name', $sent[0]);
	}
    
	
      
    public function testFileAttachment()
    {
		$ml = new Mailer(new Virtual());
		$e = new Engine($ml);
		
		$e->compose()
			->text('This is **me** !')
			->about('Here is the subject line')
			->to('recipient@domain.name')
			->attach( $e->attachment(self::$_file, 'text/plain') )
			->send();
		
		
		$sent = $ml->getMailerEngine()->getMailSender()->getSent();
		
		$this->assertStringContainsString('This is **me**', $sent[0]);
		$this->assertStringContainsString('This is <b>me</b>', $sent[0]);
		$this->assertStringContainsString('Subject: Here is the subject line', $sent[0]);
		$this->assertStringContainsString('To: recipient@domain.name', $sent[0]);
		
		$this->assertStringContainsString("Content-Type: multipart/mixed;\r\n boundary=\"", $sent[0]);
		$fname = self::$_fileName;
		$this->assertStringContainsString("Content-Type: text/plain;\r\n name=\"$fname\"", $sent[0]);
		$this->assertStringContainsString(base64_encode(self::$_fileContent), $sent[0]);
	}
     
	
      
    public function testAttachments()
    {
		$ml = new Mailer(new Virtual());
		$e = new Engine($ml);
		
		$e->compose()
			->text('This is **me** !')
			->about('Here is the subject line')
			->to('recipient@domain.name')
			->attach( $e->attachment('content_here_as_raw_string', 'text/plain')
						->withFileName('attach.txt')
						->asRawContent())
			->attach( $e->attachment('other_content_here_as_raw_string', 'text/plain')
						->withFileName('attach2.txt')
						->asRawContent())
			->send();
		
		
		$sent = $ml->getMailerEngine()->getMailSender()->getSent();
		
		$this->assertStringContainsString('This is **me**', $sent[0]);
		$this->assertStringContainsString('This is <b>me</b>', $sent[0]);
		$this->assertStringContainsString('Subject: Here is the subject line', $sent[0]);
		$this->assertStringContainsString('To: recipient@domain.name', $sent[0]);
		
		$this->assertStringContainsString("Content-Type: multipart/mixed;\r\n boundary=\"", $sent[0]);
		$this->assertStringContainsString("Content-Type: text/plain;\r\n name=\"attach.txt\"", $sent[0]);
		$this->assertStringContainsString(base64_encode("content_here_as_raw_string"), $sent[0]);
		$this->assertStringContainsString("Content-Type: text/plain;\r\n name=\"attach2.txt\"", $sent[0]);
		$this->assertStringContainsString(base64_encode("other_content_here_as_raw_string"), $sent[0]);
	}
     
	
      
    public function testEmbeddings()
    {
		$ml = new Mailer(new Virtual());
		$e = new Engine($ml);
		
		$e->compose()
			->text('This is **me** !')
			->about('Here is the subject line')
			->to('recipient@domain.name')
			->embed( $e->embedding('content_here_as_raw_string', 'text/plain', 'cid_1')
						->asRawContent())
			->embed( $e->embedding('other_content_here_as_raw_string', 'text/plain', 'cid_2')
						->asRawContent())
			->send();
		
		
		$sent = $ml->getMailerEngine()->getMailSender()->getSent();
		
		$this->assertStringContainsString('This is **me**', $sent[0]);
		$this->assertStringContainsString('This is <b>me</b>', $sent[0]);
		$this->assertStringContainsString('Subject: Here is the subject line', $sent[0]);
		$this->assertStringContainsString('To: recipient@domain.name', $sent[0]);
		
		$this->assertStringContainsString("Content-Type: multipart/related;\r\n boundary=\"", $sent[0]);
		$this->assertStringContainsString("Content-ID: <cid_1>", $sent[0]);
		$this->assertStringContainsString(base64_encode("content_here_as_raw_string"), $sent[0]);
		$this->assertStringContainsString("Content-ID: <cid_2>", $sent[0]);
		$this->assertStringContainsString(base64_encode("other_content_here_as_raw_string"), $sent[0]);
	}
   
	
	
    public function testAttachmentEmbedding()
    {
		$ml = new Mailer(new Virtual());
		$e = new Engine($ml);
		
		$e->compose()
			->text('This is **me** !')
			->about('Here is the subject line')
			->to('recipient@domain.name')
			->attach( $e->attachment('content_here_as_raw_string', 'text/plain')
						->withFileName('attach.txt')
						->asRawContent())
			->embed( $e->embedding('embedded_content_here_as_raw_string', 'text/plain', 'cid')
						->asRawContent())
			->send();
		
		
		$sent = $ml->getMailerEngine()->getMailSender()->getSent();
		
		$this->assertStringContainsString('This is **me**', $sent[0]);
		$this->assertStringContainsString('This is <b>me</b>', $sent[0]);
		$this->assertStringContainsString('Subject: Here is the subject line', $sent[0]);
		$this->assertStringContainsString('To: recipient@domain.name', $sent[0]);
		
		$this->assertStringContainsString("Content-Type: multipart/mixed;\r\n boundary=\"", $sent[0]);
		$this->assertStringContainsString("Content-Type: multipart/related;\r\n boundary=\"", $sent[0]);
		$this->assertStringContainsString("Content-Type: text/plain;\r\n name=\"attach.txt\"", $sent[0]);
		$this->assertStringContainsString(base64_encode("content_here_as_raw_string"), $sent[0]);
		$this->assertStringContainsString("Content-ID: <cid>", $sent[0]);
		$this->assertStringContainsString(base64_encode("embedded_content_here_as_raw_string"), $sent[0]);
	}
      
}

?>