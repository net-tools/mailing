<?php

namespace Nettools\Mailing\Tests;


use \Nettools\Mailing\MailSendersFacade\QuotaFacade;
use \Nettools\Mailing\MailSendersFacade\Lists\Proxies;
use \Nettools\Mailing\MailSendersFacade\Factories\QuotaCreator;




class QIF implements \Nettools\Mailing\MailSendersFacade\Quotas\QuotaInterface
{
	public $cleanCalled = false;
	
	
	function add($name, $time)
	{
		
	}
	
	
	function compute($name, $from, $to)
	{
		return 30;
	}
	
	
	function clean($before)
	{
		$this->cleanCalled = true;
	}
}





class QuotaFacadeTest extends \PHPUnit\Framework\TestCase
{
	public function testFacade()
	{
		$qif = new QIF();
		$pl = new Proxies([(object)['name'=>'PHPMail', 'params'=>(object)['quota'=>'40:d']]], 'PHPMail', new QuotaCreator($qif));
		$f = new \Nettools\Mailing\MailSendersFacade\QuotaFacade($pl, $qif);
		
		
		$this->assertEquals(true, is_array($f->getProxies()));
		$this->assertEquals(1, count($f->getProxies()));
		$this->assertEquals(\Nettools\Mailing\MailSendersFacade\Proxies\Quota::class, get_class($f->getProxies()[0]));
		$this->assertEquals('PHPMail', $f->getActiveProxy()->name);
	
		$quotas = $f->compute();
		$this->assertEquals((object)['PHPMail'=>75], $quotas);
		$this->assertEquals(true, $qif->cleanCalled);
	}
    
	
	public function testFacadeFromJson()
	{
		$qif = new QIF();
		$f = \Nettools\Mailing\MailSendersFacade\QuotaFacade::facadeQuotaProxiesFromJson(['PHPMail'], '{"PHPMail":{"name":"PHPMail","quota":"40:d"}}', 'PHPMail', $qif);
		
		$this->assertEquals(true, is_array($f->getProxies()));
		$this->assertEquals(1, count($f->getProxies()));
		$this->assertEquals(\Nettools\Mailing\MailSendersFacade\Proxies\Quota::class, get_class($f->getProxies()[0]));
		$this->assertEquals('PHPMail', $f->getActiveProxy()->name);
	
		$quotas = $f->compute();
		$this->assertEquals((object)['PHPMail'=>75], $quotas);
		$this->assertEquals(true, $qif->cleanCalled);
	}
    
}

?>