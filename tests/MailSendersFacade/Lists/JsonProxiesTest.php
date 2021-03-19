<?php
namespace Nettools\Mailing\Tests;

use \Nettools\Mailing\MailSendersFacade\Lists\JsonProxies;
use \Nettools\Mailing\MailSendersFacade\Factories\ProxyCreator;
use \Nettools\Mailing\MailSendersFacade\Proxies\Proxy;



class JsonProxiesTest extends \PHPUnit\Framework\TestCase
{
	public function testProxy()
	{
		$lst = ['SMTP:test', 'SMTP:test2', 'PHPMail'];
		$json = '{"SMTP:test":{"className":"SMTP","host":"my.host.com"}, "SMTP:test2":{"className":"SMTP","host":"my.host2.com"}}';
		
		
		$pl = new JsonProxies($lst, $json, 'SMTP:test2', new ProxyCreator());
		$p1 = $pl->getList()[0];
		$p2 = $pl->getList()[1];
		$p3 = $pl->getList()[2];
		$this->assertEquals(true, $p1 instanceof Proxy);
		$this->assertEquals(true, $p2 instanceof Proxy);
		$this->assertEquals(true, $p3 instanceof Proxy);

		$this->assertEquals($p2, $pl->getActive());
		
		$this->assertEquals((object)['className'=>"SMTP", "host"=>"my.host.com"], $p1->params);
		$this->assertEquals((object)['className'=>"SMTP", "host"=>"my.host2.com"], $p2->params);
		$this->assertEquals((object)[], $p3->params);
	}
}

?>