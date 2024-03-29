<?php

namespace Nettools\Mailing\MailBuilder\Tests;




use \Nettools\Mailing\MailBuilder\Attachment;
use \Nettools\Mailing\MailBuilder\Embedding;
use \org\bovigo\vfs\vfsStream;





class MixedRelatedTest extends \PHPUnit\Framework\TestCase
{
    protected static $_fatt = NULL;
	protected static $_fatt_ignorecache = NULL;
	protected static $_fatt_content = "Attachment sample with accents é.";
	protected static $_fatt_content_b64 = 'QXR0YWNobWVudCBzYW1wbGUgd2l0aCBhY2NlbnRzIMOpLg==';
	
	
	static public function setUpBeforeClass() :void
	{
		$vfs = vfsStream::setup('root');
        self::$_fatt = vfsStream::newFile('att1.txt')->at($vfs)->setContent(self::$_fatt_content)->url();
        self::$_fatt_ignorecache = vfsStream::newFile('att2.txt')->at($vfs)->setContent(self::$_fatt_content)->url();
	}
	
	
    public function testMailMixedRelated()
    {
		// getContent
		$matt = new Attachment(self::$_fatt, 'attach.txt', 'text/plain', false);
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
		$matt = new Attachment(self::$_fatt_ignorecache, 'attach.txt', 'text/plain', false);
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
		$matt = new Attachment(self::$_fatt, 'attach.txt', 'text/plain', false);
		$this->assertEquals('attach.txt', $matt->getFileName());

        
        // setFileName
		$matt->setFileName('att.txt');
		$this->assertEquals('att.txt', $matt->getFileName());
		
        
		// getHeaders
		$this->assertEquals(
				[	'Content-Type' 				=> "text/plain;\r\n name=\"att.txt\"",
					'Content-Transfer-Encoding' => 'base64',
					'Content-Disposition' 		=> "attachment;\r\n filename=\"att.txt\""
				],
				
                $matt->getHeaders()->toArray()
			);
		
		
		// isFile
		$this->assertEquals(true, $matt->getIsFile());
		
		
		// getContent
		$this->assertEquals(self::$_fatt_content_b64, $matt->getContent());
    }
    
    
    
    public function testMailAttachmentAsString()
    {
        // getFileName
		$matt = new Attachment('attachment data string', 'attach.txt', 'text/plain', false, false);
		$this->assertEquals('attach.txt', $matt->getFileName());

        
        // setFileName
		$matt->setFileName('att.txt');
		$this->assertEquals('att.txt', $matt->getFileName());
		
        
		// getHeaders
		$this->assertEquals(
				[	'Content-Type' 				=> "text/plain;\r\n name=\"att.txt\"",
					'Content-Transfer-Encoding' => 'base64',
					'Content-Disposition' 		=> "attachment;\r\n filename=\"att.txt\""
				],
				
                $matt->getHeaders()->toArray()
			);
		
		
		// isFile
		$this->assertEquals(false, $matt->getIsFile());
		
		
		// getContent
		$this->assertEquals(base64_encode('attachment data string'), $matt->getContent());
    }
    
    
    
    public function testMailEmbedding()
    {
        // getCid
		$membed = new Embedding(self::$_fatt, 'text/plain', 'cid-123', false);
		$this->assertEquals('cid-123', $membed->getCid());


        // setCid
		$membed->setCid('cid-456');
		$this->assertEquals('cid-456', $membed->getCid());
		
		
        // getHeaders
		$this->assertEquals(
				[	'Content-Type' 				=> "text/plain",
					'Content-Transfer-Encoding' => 'base64',
					'Content-Disposition' 		=> "inline;\r\n filename=\"cid-456\"", 
				 	'Content-ID'				=> '<cid-456>'
				],
            
				$membed->getHeaders()->toArray()
			);
				
				
		// isFile
		$this->assertEquals(true, $membed->getIsFile());
				
				
        // getContent
		$this->assertEquals(self::$_fatt_content_b64, $membed->getContent()); 
    }

	
	
    public function testMailEmbeddingAsString()
    {
        // getCid
		$membed = new Embedding('embedding data string', 'text/plain', 'cid-123', false, false);
		$this->assertEquals('cid-123', $membed->getCid());


        // setCid
		$membed->setCid('cid-456');
		$this->assertEquals('cid-456', $membed->getCid());
		
		
        // getHeaders
		$this->assertEquals(
				[	'Content-Type' 				=> "text/plain",
					'Content-Transfer-Encoding' => 'base64',
					'Content-Disposition' 		=> "inline;\r\n filename=\"cid-456\"", 
				 	'Content-ID'				=> '<cid-456>'
				],
            
				$membed->getHeaders()->toArray()
			);

		
		// isFile
		$this->assertEquals(false, $membed->getIsFile());
				
				
        // getContent
		$this->assertEquals(base64_encode('embedding data string'), $membed->getContent()); 
    }
        
	
}

?>