<?php

namespace Nettools\Mailing\Tests;

use \Nettools\Mailing\MailSendersFacade\Res\ProxyList;
use \Nettools\Mailing\MailSendersFacade\Factories\ProxyCreator;
use \Nettools\Mailing\MailSendersFacade\Factories\QuotaCreator;
use \Nettools\Mailing\MailSendersFacade\Proxies\Proxy;
use \Nettools\Mailing\MailSendersFacade\Proxies\Quota;
use \Nettools\Mailing\MailSender;




class ProxyListTest extends \PHPUnit\Framework\TestCase
{
	public function testProxy()
	{
		$o1 = (object)[
					'className'	=> 'SMTP',
					'name'		=> 'SMTP:test',
					'params'	=> (object)['host'=>'my.host.com', 'port'=>587]
				];

		$o2 = (object)[
					'name'		=> 'PHPMail'
				];

		$o3 = (object)[
					'name'		=> 'TestMailSender',
					'params'	=> (object)['k1'=>'value1']
				];



		$pl = new ProxyList([$o1, $o2, $o3], 'PHPMail', new ProxyCreator());
		$this->assertEquals(true, is_array($pl->getList()));

		$p1 = $pl->getList()[0];
		$p2 = $pl->getList()[1];
		$p3 = $pl->getList()[2];
		$this->assertEquals(true, $p1 instanceof Proxy);
		$this->assertEquals(true, $p2 instanceof Proxy);
		$this->assertEquals(true, $p3 instanceof Proxy);

		$this->assertEquals('SMTP', $p1->className);
		$this->assertEquals('PHPMail', $p2->className);
		$this->assertEquals('TestMailSender', $p3->className);

		$this->assertEquals('SMTP:test', $p1->name);
		$this->assertEquals('PHPMail', $p2->name);
		$this->assertEquals('TestMailSender', $p3->name);


		$this->assertEquals((object)['host'=>'my.host.com', 'port'=>587], $p1->params);
		$this->assertEquals((object)[], $p2->params);
		$this->assertEquals((object)['k1'=>'value1'], $p3->params);

		$this->assertEquals($p2, $pl->getActive());
	}
	
	
	public function testProxyQuota()
	{
		$o = (object)[
					'name'		=> 'PHPMail',
					'params'	=> (object)[]
				];


		$qi = new QIProxy();
		$pl = new ProxyList([$o], 'PHPMail', new QuotaCreator($qi));
		$this->assertEquals(true, is_array($pl->getList()));

		$p1 = $pl->getList()[0];
		$this->assertEquals(true, $p1 instanceof Quota);
		$this->assertEquals('PHPMail', $p1->className);
		$this->assertEquals('PHPMail', $p1->name);
	}	
}





class QIProxy implements \Nettools\Mailing\MailSendersFacade\Quotas\QuotaInterface
{
	function add($name, $time)
	{
		
	}
	
	function compute($name, $from, $to)
	{
		
	}
	
	function clean($before)
	{
		
	}

}



?>