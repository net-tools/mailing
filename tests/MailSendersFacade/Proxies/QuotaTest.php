<?php

namespace Nettools\Mailing\Tests;



use \Nettools\Mailing\MailSendersFacade\Proxies\Quota;
use \Nettools\Mailing\MailSendersFacade\Quotas\MailSender;



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
		$this->assertEquals(true, $p->getMailSender() instanceof \Nettools\Mailing\MailSendersFacade\Quotas\MailSender);
		
		$this->assertEquals(75, $p->computeQuota());
	}
}

?>