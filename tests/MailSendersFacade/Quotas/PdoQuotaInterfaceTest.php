<?php

namespace Nettools\Mailing\Tests;



use \Nettools\Mailing\MailSendersFacade\Quotas\QuotaInterface;
use \Nettools\Mailing\MailSenders\Virtual;



class PdoQuotaInterfaceTest extends \PHPUnit\Framework\TestCase
{
	public function testPdoQI()
	{
		$ackQ = $this->createMock(\PDOStatement::class);
		$ackQ->method('execute')
				->with($this->equalTo([':name'=>'msname', ':timestamp'=>123456789]))
				->willReturn(true);
		
		$quotaQ = $this->createMock(\PDOStatement::class);
		$quotaQ->method('execute')
				->with($this->equalTo([':name'=>'msname', ':from'=>123456789, ':to'=>987654321]))
				->willReturn(true);
		
		$quotaQ->method('fetchColumn')
				->with($this->equalTo(0))
				->willReturn(75);
		
		
		$cleanQ = $this->createMock(\PDOStatement::class);
		$cleanQ->method('execute')
				->with($this->equalTo([':before'=>123456789]))
				->willReturn(true);		
		
		
		$pdoqi = new \Nettools\Mailing\MailSendersFacade\Quotas\PdoQuotaInterface($ackQ, $quotaQ, $cleanQ);
		$pdoqi->add('msname', 123456789);
		$this->assertEquals(75, $pdoqi->compute('msname', 123456789, 987654321));
		$pdoqi->clean(123456789);
	}
}

?>