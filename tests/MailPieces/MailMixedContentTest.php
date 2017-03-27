<?php

namespace \Nettools\Mailing\Tests;




use \Nettools\Mailing\MailPieces\MailAttachment;
use \Nettools\Mailing\MailPieces\MailEmbedding;




class MailMixedContentTest extends \PHPUnit\Framework\TestCase
{
    protected static $_fatt = NULL;
	protected static $_fatt_ignorecache = NULL;
	protected static $_fatt_content = "Attachment sample with accents é.";
	protected static $_fatt_content_b64 = 'QXR0YWNobWVudCBzYW1wbGUgd2l0aCBhY2NlbnRzIMOpLg==';
	
	
	static public function setUpBeforeClass()
	{
        $tmp = tempnam(sys_get_temp_dir(), 'phpunit');
		self::$_fatt = $tmp . 'att1.txt';
		self::$_fatt_ignorecache = $tmp . 'att2.txt';
		
		// create attachments
		$f = fopen(self::$_fatt, "w");
		fwrite($f, self::$_fatt_content); 
		fclose($f);
	
		$f = fopen(self::$_fatt_ignorecache, "w");
		fwrite($f, self::$_fatt_content); 
		fclose($f);
	}
	
	
	static public function tearDownBeforeClass()
	{
		if ( file_exists(self::$_fatt) )
			unlink(self::$_fatt);
		if ( file_exists(self::$_fatt_ignorecache) )
			unlink(self::$_fatt_ignorecache);
	}
    
    
    public function testMailMixedContent()
    {
		// getContent
		$matt = new MailAttachment(self::$_fatt, 'attach.txt', 'text/plain', false);
		$this->assertEquals(self::$_fatt_content_b64, $matt->getContent());


        // getFile
		$this->assertEquals($matt->getFile(), self::$_fatt);


        // setFile
		$matt->setFile('other.txt');
		$this->assertEquals($matt->getFile(), 'other.txt');


        // getIgnoreCache
		$this->assertFalse($matt->getIgnoreCache());


        // setIgnoreCache
		$matt->setIgnoreCache(true);
		$this->assertTrue($matt->getIgnoreCache());


        // getContent and ignoreCache = false
		$matt = new MailAttachment(self::$_fatt_ignorecache, 'attach.txt', 'text/plain', false);
		$this->assertEquals(self::$_fatt_content_b64, $matt->getContent());
		$f = fopen(self::$_fatt_ignorecache, 'w'); // update content of file 
		fwrite($f, '');
		fclose($f);
		$this->assertEquals($matt->getContent(), self::$_fatt_content_b64);	// content not modified because caching is used


        // getContent and ignoreCache = true
		$matt->setIgnoreCache(true);
		$this->assertEquals('', $matt->getContent()); // empty content because caching is deactivated
    }
    
    
    
    public function testMailAttachment()
    {
        // getFileName
		$matt = new MailAttachment(self::$_fatt, 'attach.txt', 'text/plain', false);
		$this->assertEquals('attach.txt', $matt->getFileName());

        
        // setFileName
		$matt->setFileName('att.txt');
		$this->assertEquals('att.txt', $matt->getFileName());
		
        
		// getHeaders
		$this->assertEquals(
				"Content-Type: text/plain;\r\n   name=\"att.txt\"\r\n" .
				"Content-Transfer-Encoding: base64\r\n" .
				"Content-Disposition: attachment;\r\n   filename=\"att.txt\"",
				
                $matt->getHeaders()
			);
		
		
		// getContent
		$this->assertEquals(self::$_fatt_content_b64, $matt->getContent());
    }
    
    
    
    public function testMailEmbedding()
    {
        // getCid
		$membed = new MailEmbedding(self::$_fatt, 'text/plain', 'cid-123', false);
		$this->assertEquals('cid-123', $membed->getCid());


        // setCid
		$membed->setCid('cid-456');
		$this->assertEquals('cid-456', $membed->getCid());
		
		
        // getHeaders
		$this->assertEquals(
				"Content-Type: text/plain\r\n" .
				"Content-Transfer-Encoding: base64\r\n" .
				"Content-Disposition: inline;\r\n   filename=\"cid-456\"\r\n" .
				"Content-ID: <cid-456>",
            
				$membed->getHeaders()
			);
				
				
        // getContent
		$this->assertEquals(self::$_fatt_content_b64, $membed->getContent()); 
    }
        
}

?>