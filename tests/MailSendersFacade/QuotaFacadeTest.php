<?php

namespace Nettools\Mailing\Tests;


use \Nettools\Mailing\MailSendersFacade\QuotaFacade;
use \Nettools\Mailing\MailSendersFacade\Res\ProxyList;
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
		$pl = new ProxyList([(object)['name'=>'PHPMail', 'params'=>(object)['quota'=>'40:d']]], 'PHPMail', new QuotaCreator($qif));
		$f = new \Nettools\Mailing\MailSendersFacade\QuotaFacade($pl, $qif);
		
		
		$this->assertEquals($pl, $f->getProxyList());
		$this->assertEquals('PHPMail', $f->getActiveProxy()->name);
	
		$quotas = $f->compute();
		$this->assertEquals((object)['PHPMail'=>75], $quotas);
		$this->assertEquals(true, $qif->cleanCalled);
	}
    
}

?>