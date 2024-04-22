<?php

namespace Nettools\Mailing\MailerEngine\Tests;


use \Nettools\Mailing\MailerEngine\Headers;
use \Nettools\Mailing\MailSenders\Virtual;
use \Nettools\Mailing\MailerEngine\Engine;





class DummyHandler implements \Nettools\Mailing\MailSenders\SentHandler
{
	public $count = 0;
	
	
	function notify($to, $subject, Headers $headers)
	{
		$this->count++;
	}
}





class EngineTest extends \PHPUnit\Framework\TestCase
{
    public function testMethods()
    {
		// getAddressPart
		$this->assertEquals('me@at.com', Engine::getAddressPart('recipient <me@at.com>'));
		$this->assertEquals('me@at.com', Engine::getAddressPart('"recipient" <me@at.com>'));
		$this->assertEquals('me@at.com', Engine::getAddressPart('me@at.com'));
		$this->assertEquals('me@at.com', Engine::getAddressPart('=?UTF-8?B?w6k=?= <me@at.com>'));
		
		
		// sentEventHandlers
		$e = new Engine(new Virtual());
		$this->assertEquals(0, count($e->getMailSender()->getSentEventHandlers()));
		
		$h = new DummyHandler();
		$e->getMailSender()->addSentEventHandler($h);
		$this->assertEquals(1, count($e->getMailSender()->getSentEventHandlers()));
		
		$e->getMailSender()->removeSentEventHandler($h);
		$this->assertEquals(0, count($e->getMailSender()->getSentEventHandlers()));
		
		
		// handleHeaders_ToSubject
		$h = new Headers([]);
		$e = new Engine(new Virtual());
		$e->handleHeaders_ToSubject('éric <recipient@at.domain>', 'Subject with accent é', $h);
		$this->assertEquals(['To' => '=?UTF-8?B?w6lyaWM=?= <recipient@at.domain>', 'Subject' => 'Subject with accent =?UTF-8?B?w6k=?='], $h->toArray());
		
		// handleHeaders_Cc
		$h = new Headers(['Cc' => 'me@at.here, éric <recipient@at.domain>']);
		$e = new Engine(new Virtual());
		$e->handleHeaders_Cc($h);
		$this->assertEquals(['Cc' => "me@at.here,\r\n =?UTF-8?B?w6lyaWM=?= <recipient@at.domain>"], $h->toArray());
 		
		// handleHeaders_From
		$h = new Headers(['From' => 'éric <recipient@at.domain>']);
		$e = new Engine(new Virtual());
		$e->handleHeaders_From($h);
		$this->assertEquals(['From' => "=?UTF-8?B?w6lyaWM=?= <recipient@at.domain>"], $h->toArray());
		
		
		// handleHeaders
		$h = new Headers(['From' => 'éric <recipient@at.domain>', 'Cc' => 'me@at.here, éric <recipient@at.domain>']);
		$e = new Engine(new Virtual());
		$e->handleHeaders('éric <recipient@at.domain>', 'Subject with accent é', $h);
		$this->assertEquals([
				'From' => "=?UTF-8?B?w6lyaWM=?= <recipient@at.domain>",
				'Cc' => "me@at.here,\r\n =?UTF-8?B?w6lyaWM=?= <recipient@at.domain>",
				'Date' => $h->get('Date'),
				'MIME-Version' => '1.0',
				'To' => '=?UTF-8?B?w6lyaWM=?= <recipient@at.domain>',
				'Subject' => 'Subject with accent =?UTF-8?B?w6k=?='
			], $h->toArray());
	}
    
    
	
	public function testCc()
    {
		$h = new Headers(['Cc' => 'cc-recipient@php.com', 'From' => 'from@test.php']);
		$e = new Engine(new Virtual());
		$handler = new DummyHandler();
		$e->getMailSender()->addSentEventHandler($handler);
		
		$e->send('recipient@at.domain', 'Subject here', "mail content", $h);
		$sent = $e->getMailSender()->getSent();
		
		$this->assertEquals(2, count($sent));
		$this->assertEquals(2, $handler->count);
		$this->assertStringContainsString("Delivered-To: cc-recipient@php.com\r\n", $sent[0]);
		$this->assertStringContainsString("Delivered-To: recipient@at.domain\r\n", $sent[1]);
    }
    
    
    
	public function testBCc()
    {
		$h = new Headers(['Bcc' => 'bcc-recipient@domain.name', 'From' => 'from@test.php']);
		$e = new Engine(new Virtual());
		$handler = new DummyHandler();
		$e->getMailSender()->addSentEventHandler($handler);
		
		$e->send('recipient@at.domain', 'Subject here', "mail content", $h);
		$sent = $e->getMailSender()->getSent();
		
		$this->assertEquals(2, count($sent));
		$this->assertEquals(2, $handler->count);
		$this->assertStringContainsString("Bcc: bcc-recipient@domain.name\r\n", $sent[0]);
		$this->assertStringContainsString("Delivered-To: bcc-recipient@domain.name\r\n", $sent[0]);
		$this->assertStringContainsString("Delivered-To: recipient@at.domain\r\n", $sent[1]);
		$this->assertStringContainsString("To: recipient@at.domain\r\n", $sent[1]);
		$this->assertStringNotContainsString("Bcc: bcc-recipient@domain.name\r\n", $sent[1]);
    }
    
    
    
	public function testCc2()
    {
		$h = new Headers(['Cc' => 'cc <cc-recipient@php.com>, othercc <othercc-recipient@php.com>, éric <another-cc@php.com>', 'From' => 'unit-test@php.com']);
		$e = new Engine(new Virtual());
		$handler = new DummyHandler();
		$e->getMailSender()->addSentEventHandler($handler);

		$e->send('unit-test-recipient@php.com', 'Mail subject', "mail content", $h);
		$sent = $e->getMailSender()->getSent();
		
		$this->assertEquals(4, count($sent));
		$this->assertEquals(4, $handler->count);
		$this->assertStringContainsString("Delivered-To: cc-recipient@php.com\r\n", $sent[0]);
		$this->assertStringContainsString("Delivered-To: othercc-recipient@php.com\r\n", $sent[1]);
		$this->assertStringContainsString("Delivered-To: another-cc@php.com\r\n", $sent[2]);
		$this->assertStringContainsString("Delivered-To: unit-test-recipient@php.com\r\n", $sent[3]);

		$this->assertStringContainsString("Cc: cc <cc-recipient@php.com>,\r\n othercc <othercc-recipient@php.com>,\r\n =?UTF-8?B?w6lyaWM=?= <another-cc@php.com>\r\n", $sent[0]);
		$this->assertStringContainsString("Cc: cc <cc-recipient@php.com>,\r\n othercc <othercc-recipient@php.com>,\r\n =?UTF-8?B?w6lyaWM=?= <another-cc@php.com>\r\n", $sent[1]);
		$this->assertStringContainsString("Cc: cc <cc-recipient@php.com>,\r\n othercc <othercc-recipient@php.com>,\r\n =?UTF-8?B?w6lyaWM=?= <another-cc@php.com>\r\n", $sent[2]);
		$this->assertStringContainsString("Cc: cc <cc-recipient@php.com>,\r\n othercc <othercc-recipient@php.com>,\r\n =?UTF-8?B?w6lyaWM=?= <another-cc@php.com>\r\n", $sent[3]);
    }
	
	
    
    public function testEncoding()
    {
		$h = new Headers(['From' => 'from <unit-test@php.com>']);
		$e = new Engine(new Virtual());

		$e->send('to <unit-test-recipient@php.com>', 'Mail subject', "mail content", $h);
		$sent = $e->getMailSender()->getSent();

		
		// guess Message-ID and Date headers
		$regs = [];
		$this->assertEquals(1, preg_match('/Message-ID: <[0-9a-f]+@php.com>/', $sent[0], $regs));
		$mid = $regs[0];
		$regs = [];
		$this->assertEquals(1, preg_match('/Date: [A-Z][a-z]{2,4}, [0-9]{1,2} [A-Z][a-z]{2,4} 20[0-9]{2} [0-9]{2}:[0-9]{2}:[0-9]{2} .[0-9]{4}/', $sent[0], $regs));
		$dt = $regs[0];

		$this->assertStringContainsString( 
				"From: from <unit-test@php.com>\r\n" .
				"$dt\r\n" .
				"MIME-Version: 1.0\r\n" .
				"To: to <unit-test-recipient@php.com>\r\n" .
				"Subject: Mail subject\r\n" .
				"$mid\r\n" .
				"Delivered-To: unit-test-recipient@php.com\r\n",
            
                $sent[0]
			);
    }
    
    
	
    public function testEncoding2()
    {
		$h = new Headers(['From' => 'é <unit-test@php.com>']);
		$e = new Engine(new Virtual());

		$e->send('à <unit-test-recipient@php.com>', 'Mail subject with accents éè inside the string', "mail content", $h);
		$sent = $e->getMailSender()->getSent();
		
		// guess Message-ID and Date headers
		$regs = [];
		$this->assertEquals(1, preg_match('/Message-ID: <[0-9a-f]+@php.com>/', $sent[0], $regs));
		$mid = $regs[0];
		$regs = [];
		$this->assertEquals(1, preg_match('/Date: [A-Z][a-z]{2,4}, [0-9]{1,2} [A-Z][a-z]{2,4} 20[0-9]{2} [0-9]{2}:[0-9]{2}:[0-9]{2} .[0-9]{4}/', $sent[0], $regs));
		$dt = $regs[0];

		$this->assertStringContainsString( 
				"From: =?UTF-8?B?w6k=?= <unit-test@php.com>\r\n" .
				"$dt\r\n" .
				"MIME-Version: 1.0\r\n" .
				"To: =?UTF-8?B?w6A=?= <unit-test-recipient@php.com>\r\n" .
				"Subject: Mail subject with accents =?UTF-8?B?w6nDqCBpbnNpZGUgdGhlIHN0cmluZw==?=\r\n" .
				"$mid\r\n" .
				"Delivered-To: unit-test-recipient@php.com\r\n",
            
                $sent[0]
			);
    }
           
}

?>