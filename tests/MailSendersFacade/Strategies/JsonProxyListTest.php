<?php
namespace Nettools\Mailing\Tests;

use \Nettools\Mailing\MailSendersFacade\Strategies\JsonProxyList;
use \Nettools\Mailing\MailSenderProxy;
use \Nettools\Mailing\MailSender;



class JsonProxyListTest extends \PHPUnit\Framework\TestCase
{
	public function proxyTest()
	{
		$lst = 'SMTP:test;SMTP:test2;PHPMail';
		$json = '{"SMTP:test":{"className":"SMTP","host":"my.host.com"}, "SMTP:test2":{"className":"SMTP","host":"my.host2.com"}}';
		
		
		$pl = new JsonProxyList($lst, $json, 'SMTP:test2');
		$p1 = $pl->getProxyList()[0];
		$p2 = $pl->getProxyList()[1];
		$p3 = $pl->getProxyList()[2];
		$this->assertEquals(true, $p1 instanceof MailSenderProxy);
		$this->assertEquals(true, $p2 instanceof MailSenderProxy);
		$this->assertEquals(true, $p3 instanceof MailSenderProxy);
		
		$this->assertEquals($p2, $pl->getActiveProxy());
	}
}

?>