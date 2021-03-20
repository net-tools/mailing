<?php

namespace Nettools\Mailing\MailSenders
{
	class TestMailSender implements \Nettools\Mailing\MailSenderIntf
	{
		public $parameters;
		public $name;
		
		

		function send($to, $subject, $mail, $headers)
		{
		}


		function ready()
		{
			return true;
		}


		function destruct()
		{
		}



    	function __construct($params = NULL)
		{
			$this->parameters = $params;
		}
	}
}



namespace Nettools\Mailing\Tests{
	
use \Nettools\Mailing\MailSendersFacade\Proxies\Proxy;
use \Nettools\Mailing\MailSender;


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
			$this->assertEquals(true, $ms instanceof \Nettools\Mailing\MailSenderIntf);
			$this->assertEquals(['k1'=>'value1'], $ms->parameters);
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