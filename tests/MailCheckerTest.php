<?php

namespace Nettools\Mailing\Tests;




class MailCheckerTest extends \PHPUnit\Framework\TestCase
{
    public function test()
    {
		$stub = $this->createMock(\Nettools\Mailing\MailCheckers\Checker::class);
		$stub->method('check')->with('xxxx@gmail.com')->willReturn(true);
		
		$chk = new \Nettools\Mailing\MailChecker($stub);
		$b = $chk->check('xxxx@gmail.com');
		$this->assertEquals(true, $b);
		$this->assertEquals($stub, $chk->getMailChecker());
    }
}

?>