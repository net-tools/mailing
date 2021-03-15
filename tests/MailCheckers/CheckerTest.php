<?php

namespace Nettools\Mailing\Tests;



use \Nettools\Mailing\MailCheckers\TrumailIo;




class CheckerTest extends \PHPUnit\Framework\TestCase
{
    public function test()
    {
		// create a instance of TrumailIo with static method `create`
		$chk = \Nettools\Mailing\MailCheckers\TrumailIo::create();
		
		// checking late static binding, as the constructor is in Checker abstract class, but use `get_called_class` to fetch the calling class
		$this->assertEquals(true, $chk instanceof \Nettools\Mailing\MailCheckers\TrumailIo);
    }
}

?>