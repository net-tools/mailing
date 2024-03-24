<?php

namespace Nettools\Mailing\Tests;


use \Nettools\Mailing\MailPieces\Headers;





class MailHeadersTest extends \PHPUnit\Framework\TestCase
{
    public function testSetHeader()
    {
		$h = new Headers([]);
		$h->set('From', 'user@domain.tld');		
		$this->assertEquals(['From' => 'user@domain.tld' ], $h->toArray());

		$h = new Headers([]);
		$h->From = 'user@domain.tld';
		$this->assertEquals(['From' => 'user@domain.tld' ], $h->toArray());

		$h = new Headers(['From' => 'user@domain.tld']);
		$h->set('', '');		
		$this->assertEquals(['From' => 'user@domain.tld' ], $h->toArray());
		
		$h = new Headers(['From' => 'user@domain.tld']);
		$h->set('To', 'other@domain.tld');		
		$this->assertEquals(['From' => 'user@domain.tld', 'To' => 'other@domain.tld' ], $h->toArray());

		$h = new Headers(['From' => 'user@domain.tld', 'Bcc' => 'bcc-user@domain.tld']);
		$h->set('From', 'other-user@domain.tld');		
		$this->assertEquals(['From' => 'other-user@domain.tld', 'Bcc' => 'bcc-user@domain.tld'], $h->toArray());

		$h = new Headers(['Content-Type' => "multipart/mixed;\r\n boundary=\"xyz1234\""]);
		$h->set('From', 'user@domain.tld');		
		$this->assertEquals(['Content-Type' => "multipart/mixed;\r\n boundary=\"xyz1234\"", 'From' => 'user@domain.tld'], $h->toArray());
							
		$h = new Headers(['Content-Type' => "multipart/mixed;\r\n boundary=\"xyz1234\""]);
		$h->set('Content-Type', 'text/plain; charset=UTF-8');		
		$this->assertEquals(['Content-Type' => 'text/plain; charset=UTF-8'], $h->toArray()); 
		
		$h = new Headers(['Content-Type' => "multipart/mixed;\r\n boundary=\"xyz1234\""]);
		$h->set('Content-Type', "multipart/mixed;\r\n boundary=\"abc5678\"");		
		$this->assertEquals(['Content-Type' => "multipart/mixed;\r\n boundary=\"abc5678\""], $h->toArray());

		$h = new Headers(['Content-Type' => "multipart/mixed;\r\n boundary=\"xyz1234\"\r\n other=\"testfolding\"", 'From' => 'user@domain.tld']);
		$h->set('Content-Type', "multipart/mixed;\r\n boundary=\"abc5678\"");		
		$this->assertEquals(['Content-Type' => "multipart/mixed;\r\n boundary=\"abc5678\"", 'From' => 'user@domain.tld'], $h->toArray());
    }
    
    
    public function testEncoding()
    {
		$h = new Headers([]);
		$h->setEncodedRecipient('From', 'éric <user@domain.tld>');
		$this->assertEquals(['From' => '=?UTF-8?B?w6lyaWM=?= <user@domain.tld>' ], $h->toArray());

		$h = new Headers([]);
		$h->set('Subject', 'message to éric', true);
		$this->assertEquals(['Subject' => 'message to =?UTF-8?B?w6lyaWM=?=' ], $h->toArray());

		$h = new Headers([]);
		$h->setEncoded('Subject', 'message to éric');
		$this->assertEquals(['Subject' => 'message to =?UTF-8?B?w6lyaWM=?=' ], $h->toArray());
		
 		$h = new Headers(['From' => 'éric <user@domain.tld>']);
		$h->encodeRecipient('From');
		$this->assertEquals(['From' => '=?UTF-8?B?w6lyaWM=?= <user@domain.tld>' ], $h->toArray());

		$h = new Headers(['From' => '=?UTF-8?B?w6lyaWM=?= <user@domain.tld>']);
		$this->assertEquals(['From' => 'éric <user@domain.tld>' ], $h->getDecoded('From'));

		$h = new Headers(['Subject' => 'message to =?UTF-8?B?w6lyaWM=?=']);
		$this->assertEquals(['Subject' => 'message to éric' ], $h->getDecoded('Subject'));
   }
    
    
    public function testMerge()
    {
		$h = new Headers();
		$h->merge(['From' => 'user@domain.tld']);
		$this->assertEquals(['From' => 'user@domain.tld'], $h->toArray());
		
		$h = new Headers(['From' => 'user@domain.tld']);
		$h->merge(['To' => 'other@domain.tld', 'Bcc' => 'bcc-user@domain.tld']);		
		$this->assertEquals(['From' => 'user@domain.tld', 'To' => 'other@domain.tld', 'Bcc' => 'bcc-user@domain.tld'], $h->toArray());
    }
    
    
    public function testMergeWith()
    {
		$h = new Headers();
		$h2 = new Headers(['From' => 'user@domain.tld']);
		$this->assertEquals(['From' => 'user@domain.tld'], $h->mergeWith($h2)->toArray());
		
		$h = new Headers(['From' => 'user@domain.tld']);
		$h2 = new Headers(['To' => 'other@domain.tld', 'Bcc' => 'bcc-user@domain.tld']);
		$this->assertEquals(['From' => 'user@domain.tld', 'To' => 'other@domain.tld', 'Bcc' => 'bcc-user@domain.tld'], $h->mergeWith($h2)->toArray());
    }
    
    
    public function testStringToArray()
    {
		$this->assertEquals(array('From'=>'user@domain.tld', 'Content-Type'=>"multipart/mixed;\r\n boundary=\"abc5678\""),
                            Headers::string2array("From: user@domain.tld\r\nContent-Type: multipart/mixed;\r\n boundary=\"abc5678\""));
		$this->assertEquals(array(), Headers::string2array(""));
    }
    
    
    public function testArrayToString()
    {
		$this->assertEquals("From: user@domain.tld\r\nContent-Type: multipart/mixed;\r\n boundary=\"abc5678\"",
                            Headers::array2string(array('From'=>'user@domain.tld', 'Content-Type'=>"multipart/mixed;\r\n boundary=\"abc5678\"")));
		$this->assertEquals("", Headers::array2string(array()));
    }
    
    
    public function testRemoveHeader()
    {
		$h = new Headers(['From' => 'user@domain.tld', 'Bcc' => 'user-bcc@domain.tld']);
		$h->remove('Bcc');
		$this->assertEquals(['From' => 'user@domain.tld'], $h->toArray());
		
		$h = new Headers([]);
		$h->remove('Bcc');
		$this->assertEquals([], $h->toArray());
		
		$h = new Headers(['From' => 'user@domain.tld']);
		$h->remove('Content-Type');
		$this->assertEquals(['From' => 'user@domain.tld'], $h->toArray());

		$h = new Headers([]);
		$h->remove('');
		$this->assertEquals([], $h->toArray());
    }
	
	
	public function testGet()
	{
		$h = new Headers(['From' => 'user@domain.tld', 'Bcc' => 'user-bcc@domain.tld', 'Content-Type'=>'text/plain']);
		$this->assertEquals('user@domain.tld', $h->get('From'));
		$this->assertEquals('user-bcc@domain.tld', $h->get('Bcc'));
		$this->assertEquals(NULL, $h->get('To'));
		
		$this->assertEquals('user@domain.tld', $h->From);
		$this->assertEquals('text/plain', $h->{'Content-Type'});
	}
	
	
	public function testToString()
	{
		$h = new Headers(['From' => 'user@domain.tld', 'Bcc' => 'user-bcc@domain.tld']);
		$this->assertEquals("From: user@domain.tld\r\nBcc: user-bcc@domain.tld", $h->toString());
	}
    
    
	public function testFromString()
	{
		$h = Headers::fromString("From: user@domain.tld\r\nBcc: user-bcc@domain.tld");
		$this->assertEquals(['From' => 'user@domain.tld', 'Bcc' => 'user-bcc@domain.tld'], $h->toArray());
	}
    
    
	public function testFromObject()
	{
		$h = Headers::fromString("From: user@domain.tld\r\nBcc: user-bcc@domain.tld");
		$h2 = Headers::fromObject($h);		
		$h2->set('From', 'me@here.com');
		$this->assertEquals(['From' => 'user@domain.tld', 'Bcc' => 'user-bcc@domain.tld'], $h->toArray());
		$this->assertEquals(['From' => 'me@here.com', 'Bcc' => 'user-bcc@domain.tld'], $h2->toArray());
	}
    
    
}

?>