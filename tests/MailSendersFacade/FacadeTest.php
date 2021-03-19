<?php

namespace Nettools\Mailing\Tests;


use \Nettools\Mailing\MailSendersFacade\Facade;
use \Nettools\Mailing\MailSendersFacade\Res\ProxyList;
use \Nettools\Mailing\MailSendersFacade\Factories\ProxyCreator;



class FacadeTest extends \PHPUnit\Framework\TestCase
{
	public function testFacade()
	{
		$pl = new ProxyList([(object)['name'=>'PHPMail']], 'PHPMail', new ProxyCreator());
		$f = new \Nettools\Mailing\MailSendersFacade\Facade($pl);
		
		
		$this->assertEquals(true, is_array($f->getProxyList()));
		$this->assertEquals(1, count($f->getProxyList()));
		$this->assertEquals(\Nettools\Mailing\MailSendersFacade\Proxies\Proxy::class, get_class($f->getProxyList()[0]));
		$this->assertEquals('PHPMail', $f->getActiveProxy()->name);
	}
    
}

?>