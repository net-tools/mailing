<?php

namespace Nettools\Mailing\Tests;



use \Nettools\Mailing\Mailer;
use \Nettools\Mailing\MailSenderProxy;




class MailSenderProxyTest extends \PHPUnit\Framework\TestCase
{
	public function proxyTest()
	{
		$params = (object)["host" => "smtp.host.com"];
		
		$p = new MailSenderProxy('SMTP', 'smtp:test', $params);
		$this->assertEquals('SMTP', $p->className);
		$this->assertEquals('smtp:test', $p->name);
		$this->assertEquals($params, $p->params);
	}
}

?>