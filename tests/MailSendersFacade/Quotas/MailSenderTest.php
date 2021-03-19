<?php

namespace Nettools\Mailing\Tests;



use \Nettools\Mailing\MailSendersFacade\Quotas\MailSender;
use \Nettools\Mailing\MailSendersFacade\Quotas\QuotaInterface;
use \Nettools\Mailing\MailSenders\Virtual;



class QI2 implements QuotaInterface
{
	public $quota = [];
	
	public function add($name, $time)
	{
		$this->quota[] = ['name'=> $name, 'time'=>$time];
	}
	
	function compute($name, $from, $to)
	{
		
	}
	
	
	function clean($before)
	{
		
	}
}




class MailSenderTest extends \PHPUnit\Framework\TestCase
{
	public function testProxy()
	{
		$qi = new QI2();
		$msv = new Virtual();
		$ms = new \Nettools\Mailing\MailSendersFacade\Quotas\MailSender('Virtual:test', $msv, $qi);
		$this->assertEquals(FALSE, $ms->send('tester@gmail.com', 'subject', 'this is a test mail', 'From: unitest@selfhost.com'));
		$this->assertEquals(FALSE, $ms->send('tester2@gmail.com', 'subject 2', 'this is a test mail 2', 'From: unitest@selfhost.com'));

		$this->assertCount(2, $qi->quota);
		$this->assertEquals('Virtual:test', $qi->quota[0]['name']);
		$this->assertIsInt($qi->quota[0]['time']);
		
		$this->assertEquals($msv, $ms->getUnderlyingObject());
		$this->assertCount(2, $msv->getSent());
	}
}

?>