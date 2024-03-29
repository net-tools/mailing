<?php

namespace Nettools\Mailing\Tests;



use \Nettools\Mailing\MailSendersFacade\Proxies\Quota;



class QI implements \Nettools\Mailing\MailSendersFacade\Quotas\QuotaInterface
{
	function add($name, $time)
	{
		
	}
	
	
	function compute($name, $from, $to)
	{
		return 30;
	}
	
	
	function clean($before)
	{
		
	}
}




class QuotaTest extends \PHPUnit\Framework\TestCase
{
	public function testProxy()
	{
		$p = new Quota('PHPMail', 'PHPMail', (object)['quota'=>'40:d'], new QI());
		$this->assertEquals(true, $p->getMailSender() instanceof \Nettools\Mailing\MailSenders\MailSender);
		$this->assertEquals(true, $p->getMailSender()->getSentEventHandlers()[0] instanceof \Nettools\Mailing\MailSendersFacade\Quotas\SentHandler);

		$c = $p->computeQuota();
		$this->assertEquals(75, $c->pct);
		$this->assertEquals(30, $c->value);
		$this->assertEquals(40, $c->quota);		
		$this->assertEquals('d', $c->period);		
	}
}

?>