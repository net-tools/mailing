<?php

namespace Nettools\Mailing\Tests;


use \Nettools\Mailing\MailPieces\Headers;





class MailHeadersTest extends \PHPUnit\Framework\TestCase
{
    public function testAddHeader()
    {
		$h = new Headers([]);
		$h->add('From', 'user@domain.tld');		
		$this->assertEquals(['From' => 'user@domain.tld' ], $h->toArray());

		$h = new Headers(['From' => 'user@domain.tld']);
		$h->add('', '');		
		$this->assertEquals(['From' => 'user@domain.tld' ], $h->toArray());
		
		$h = new Headers(['From' => 'user@domain.tld']);
		$h->add('To', 'other@domain.tld');		
		$this->assertEquals(['From' => 'user@domain.tld', 'To' => 'other@domain.tld' ], $h->toArray());

		$h = new Headers(['From' => 'user@domain.tld', 'Bcc' => 'bcc-user@domain.tld']);
		$h->add('From', 'other-user@domain.tld');		
		$this->assertEquals(['From' => 'other-user@domain.tld', 'Bcc' => 'bcc-user@domain.tld'], $h->toArray());

		$h = new Headers(['Content-Type' => "multipart/mixed;\r\n boundary=\"xyz1234\""]);
		$h->add('From', 'other-user@domain.tld');		
		$this->assertEquals(['Content-Type' => "multipart/mixed;\r\n boundary=\"xyz1234\"", 'From' => 'user@domain.tld'], $h->toArray());
							
		$h = new Headers(['Content-Type' => "multipart/mixed;\r\n boundary=\"xyz1234\""]);
		$h->add('Content-Type', 'text/plain; charset=UTF-8');		
		$this->assertEquals(['Content-Type' => 'text/plain; charset=UTF-8'], $h->toArray()); 
		
		$h = new Headers(['Content-Type' => "multipart/mixed;\r\n boundary=\"xyz1234\""]);
		$h->add('Content-Type', "multipart/mixed;\r\n boundary=\"abc5678\"");		
		$this->assertEquals(['Content-Type' => "multipart/mixed;\r\n boundary=\"abc5678\""], $h->toArray());

		$h = new Headers(['Content-Type' => "multipart/mixed;\r\n boundary=\"xyz1234\"\r\n other=\"testfolding\"", 'From' => 'user@domain.tld']);
		$h->add('Content-Type', "multipart/mixed;\r\n boundary=\"abc5678\"");		
		$this->assertEquals(['Content-Type' => "multipart/mixed;\r\n boundary=\"abc5678\"", 'From' => 'user@domain.tld'], $h->toArray());
    }
    
    
    public function testAddHeaders()
    {
		$h = new Headers();
		$h->merge(['From' => 'user@domain.tld']);
		$this->assertEquals(['From' => 'user@domain.tld'], $h->toArray());
		
		$h = new Headers(['From' => 'user@domain.tld']);
		$h->merge(['To' => 'other@domain.tld', 'Bcc' => 'bcc-user@domain.tld']);		
		$this->assertEquals(['From' => 'user@domain.tld', 'To' => 'other@domain.tld', 'Bcc' => 'bcc-user@domain.tld'], $h->toArray());
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
    
    
}

?>