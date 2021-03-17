<?php

namespace Nettools\Mailing\MailSenders
{
	class TestMailSender extends \Nettools\Mailing\MailSender
	{
		public $parameters;
		
		
    	function __construct($params = NULL)
		{
			parent::_construct($params);
			$this->parameters = $params;
		}
	}
}


namespace Nettools\Mailing\Tests
{
	use \Nettools\Mailing\MailSendersFacade\Strategies\ProxyList;
	use \Nettools\Mailing\MailSenderProxy;
	use \Nettools\Mailing\MailSender;




	class ProxyListTest extends \PHPUnit\Framework\TestCase
	{
		public function proxyTest()
		{
			$o1 = (object)[
						'className'	=> 'SMTP',
						'name'		=> 'SMTP:test',
						'params'	=> (object)['host'=>'my.host.com', 'port'=>587]
					];

			$o2 = (object)[
						'name'		=> 'PHPMail',
						'params'	=> (object)[]
					];

			$o3 = (object)[
						'name'		=> 'TestMailSender',
						'params'	=> (object)['k1'=>'value1']
					];



			$pl = new ProxyList([$o1, $o2, $o3], $o2);
			$this->assertEquals(true, is_array($pl->getProxyList()));

			$p1 = $pl->getProxyList()[0];
			$p2 = $pl->getProxyList()[1];
			$p3 = $pl->getProxyList()[2];
			$this->assertEquals(true, $p1 instanceof MailSenderProxy);
			$this->assertEquals(true, $p2 instanceof MailSenderProxy);
			$this->assertEquals(true, $p3 instanceof MailSenderProxy);
			
			$this->assertEquals((object)['host'=>'my.host.com', 'port'=>587], $p1->params);
			$this->assertEquals((object)[], $p2->params);
			$this->assertEquals((object)['k1'=>'value1'], $p3->params);
						
			$ms = $p3->getMailSender();
			$this->assertEquals(true, $ms instanceof MailSender);
			$this->assertEquals(['k1'=>'value1'], $ms->parameters);
		}
	}
}
?>