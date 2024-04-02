<?php

namespace Nettools\Mailing\MailBuilder\Tests;




use \Nettools\Mailing\MailBuilder\Builder;
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
	
	
    public function testNoCacheByDefault()
    {
		$matt = new Attachment(self::$_fatt, 'attach.txt', 'text/plain');
		$this->assertTrue($matt->getNoCache());

		$matt = new Attachment(self::$_fatt, 'attach.txt', 'text/plain', true);
		$this->assertTrue($matt->getNoCache());

		$matt = new Attachment(self::$_fatt, 'attach.txt', 'text/plain', false);
		$this->assertFalse($matt->getNoCache());
	}
	
	
	
    public function testCacheFile()
    {
		// store cache count now
		$cache = Builder::getAttachmentsCache();
		$c0 = $cache->getCount();
		
		// using cache
		$matt = new Attachment(self::$_fatt, 'attach.txt', 'text/plain', false);
		$str = $matt->getContent();
		
		// one more entry in cache		
		$this->assertEquals($c0+1, $cache->getCount());
		
		$matt2 = new Attachment(self::$_fatt, 'attach.txt', 'text/plain', false);
		$str2 = $matt2->getContent();
		$this->assertEquals($str, $str2);
		
		// still one more entry in cache, because cacheid is the same
		$this->assertEquals($c0+1, $cache->getCount());
	}
	
	
	
    public function testCacheData()
    {
		// store cache count now
		$cache = Builder::getAttachmentsCache();
		$c0 = $cache->getCount();
		
		// using cache
		$matt = new Attachment(self::$_fatt_content, 'attach.txt', 'text/plain', false /* nocache */, false /* isfile */);
		$str = $matt->getContent();
		
		// one more entry in cache		
		$this->assertEquals($c0+1, $cache->getCount());
		
		$matt2 = new Attachment(self::$_fatt_content, 'attach.txt', 'text/plain', false, false);
		$str2 = $matt2->getContent();
		$this->assertEquals($str, $str2);
		
		// still one more entry in cache, because cacheid is the same (sha256 computation)
		$this->assertEquals($c0+1, $cache->getCount());

		
		
	
		// store cache count now
		$c0 = $cache->getCount();
		
		// using cache
		$matt = new Attachment(self::$_fatt_content, 'attach.txt', 'text/plain', false /* nocache */, false /* isfile */);
		$matt->setCacheId('idatt');
		$str = $matt->getContent();
		
		// one more entry in cache		
		$this->assertEquals($c0+1, $cache->getCount());
		
		$matt2 = new Attachment(self::$_fatt_content, 'attach.txt', 'text/plain', false, false);
		$str2 = $matt2->getContent();
		$this->assertEquals($str, $str2);
		
		// two more entries in cache, because cacheid is different (user set in first attachment test above)
		$this->assertEquals($c0+2, $cache->getCount());
		
		
		$this->assertEquals(trim(chunk_split(base64_encode(self::$_fatt_content))), $matt2->getContent());
	}
	
	
	
    public function testMailMixedRelated()
    {
		// getContent
		$matt = new Attachment(self::$_fatt, 'attach.txt', 'text/plain', false);
		$this->assertEquals(self::$_fatt_content_b64, $matt->getContent());


        // getData
		$this->assertEquals($matt->getData(), self::$_fatt);


        // setData
		$matt->setData('other.txt');
		$this->assertEquals($matt->getData(), 'other.txt');


        // getNoCache
		$this->assertFalse($matt->getNoCache());


        // setNoCache
		$matt->setNoCache(true);
		$this->assertTrue($matt->getNoCache());


        // getContent and noCache = false
		$matt = new Attachment(self::$_fatt_ignorecache, 'attach.txt', 'text/plain', false);
		$this->assertEquals(self::$_fatt_content_b64, $matt->getContent());
		$f = fopen(self::$_fatt_ignorecache, 'w'); // update content of file 
		fwrite($f, '');
		fclose($f);
		$this->assertEquals($matt->getContent(), self::$_fatt_content_b64);	// content not modified because caching is used


        // getContent and noCache = true
		$matt->setNoCache(true);
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