<?php

namespace Nettools\Mailing\Tests;


use \Nettools\Mailing\MailSendersFacade\Facade;
use \Nettools\Mailing\MailSendersFacade\Lists\Proxies;
use \Nettools\Mailing\MailSendersFacade\Factories\ProxyCreator;



class FacadeTest extends \PHPUnit\Framework\TestCase
{
	public function testFacade()
	{
		$pl = new Proxies([(object)['name'=>'PHPMail']], 'PHPMail', new ProxyCreator());
		$f = new \Nettools\Mailing\MailSendersFacade\Facade($pl);
		
		
		$this->assertEquals(true, is_array($f->getProxies()));
		$this->assertEquals(1, count($f->getProxies()));
		$this->assertEquals(\Nettools\Mailing\MailSendersFacade\Proxies\Proxy::class, get_class($f->getProxies()[0]));
		$this->assertEquals('PHPMail', $f->getActiveProxy()->name);
	}
    

	public function testFacadeFromJson()
	{
		$f = \Nettools\Mailing\MailSendersFacade\Facade::facadeProxiesFromJson(['PHPMail'], '{"PHPMail":{"name":"PHPMail"}}', 'PHPMail');
		
		$this->assertEquals(true, is_array($f->getProxies()));
		$this->assertEquals(1, count($f->getProxies()));
		$this->assertEquals(\Nettools\Mailing\MailSendersFacade\Proxies\Proxy::class, get_class($f->getProxies()[0]));
		$this->assertEquals('PHPMail', $f->getActiveProxy()->name);
	}
    
}

?>