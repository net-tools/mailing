<?php

namespace Nettools\Mailing\MailSenders
{
	class TestMailSender extends \Nettools\Mailing\MailSenders\MailSender
	{
		function doSend($to, $subject, $mail, $headers)
		{
			
		}
	}
}



namespace Nettools\Mailing\Tests{
	
	use \Nettools\Mailing\MailSendersFacade\Proxies\Proxy;

	

	class ProxyTest extends \PHPUnit\Framework\TestCase
	{
		public function testProxy()
		{
			$params = (object)["k1" => "value1"];

			$p = new Proxy('TestMailSender', 'TestMailSender:test', $params);
			$this->assertEquals('TestMailSender', $p->className);
			$this->assertEquals('TestMailSender:test', $p->name);
			$this->assertEquals($params, $p->params);

			$ms = $p->getMailSender();
			$this->assertEquals(true, $ms instanceof \Nettools\Mailing\MailSenders\MailSender);
		}
		
		
		public function testProxyWrongClass()
		{
			$params = (object)["k1" => "value1"];

			$p = new Proxy('TestMailSenderKo', 'TestMailSender:test', $params);

			$this->expectException(\Nettools\Mailing\MailSendersFacade\Exception::class);
			$this->expectExceptionMessage('Mailsender of class ');
			$ms = $p->getMailSender();
		}
	}
}
?>