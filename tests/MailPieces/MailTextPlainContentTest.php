<?php

namespace Nettools\Mailing\Tests;



use \Nettools\Mailing\MailPieces\MailTextPlainContent;




class MailTextPlainContentTest extends \PHPUnit\Framework\TestCase
{
    public function test()
    {
		$mc = new MailTextPlainContent('Test é.');
		$this->assertEquals('Test é.', $mc->getText());


		$mc = new MailTextPlainContent('Test é.');
		$mc->setText('Test è.');
		$this->assertEquals('Test è.', $mc->getText());
		
		
		$mc = new MailTextPlainContent('Test é.');
		$mc->setText('Test è.');
		$this->assertEquals('Test è.', $mc->getText());
		
		
		$mc = new MailTextPlainContent('Test');
		$this->assertEquals('text/plain', $mc->getContentType());


		$mc = new MailTextPlainContent('Test');
		$mc->setContentType('text/csv');
		$this->assertEquals('text/csv', $mc->getContentType());


		$mc = new MailTextPlainContent('Test é.');
		$this->assertEquals( [
			'Content-Type'	=> 'text/plain; charset=UTF-8',
			'Content-Transfer-Encoding' => 'quoted-printable' ], $mc->getHeaders());


		$mc = new MailTextPlainContent('Test é.');
		$mc->addCustomHeader('Bcc', 'user1@gmail.com');
		$mc->addCustomHeader('Bcc', 'user2@gmail.com'); // overrides previously defined header
		$this->assertEquals( [ 'Bcc' => 'user2@gmail.com' ], $mc->getCustomHeaders());


		$mc = new MailTextPlainContent('Test é.');
		$mc->addCustomHeader('Bcc', 'user2@gmail.com');
		$this->assertEquals([
			'Content-Type'	=> 'text/plain; charset=UTF-8',
			'Content-Transfer-Encoding' => 'quoted-printable',
			'Bcc' => 'user2@gmail.com'
			], $mc->getFullHeaders());


		$mc = new MailTextPlainContent('Test é.');
		$this->assertEquals("Test =C3=A9.", $mc->getContent());		// C3 : ASCII 195 : 1er octet indique caractère unicode UTF8


		$mc = new MailTextPlainContent('Test é.');
		$this->assertEquals("Content-Type: text/plain; charset=UTF-8\r\nContent-Transfer-Encoding: quoted-printable\r\n\r\n" .
											"Test =C3=A9.\r\n\r\n", $mc->toString());		
    }
}

?>