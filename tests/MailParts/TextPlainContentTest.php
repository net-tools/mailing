<?php

namespace Nettools\Mailing\MailParts\Tests;



use \Nettools\Mailing\MailParts\TextPlainContent;




class TextPlainContentTest extends \PHPUnit\Framework\TestCase
{
    public function test()
    {
		$mc = new TextPlainContent('Test é.');
		$this->assertEquals('Test é.', $mc->getText());


		$mc = new TextPlainContent('Test é.');
		$mc->setText('Test è.');
		$this->assertEquals('Test è.', $mc->getText());
		
		
		$mc = new TextPlainContent('Test é.');
		$mc->setText('Test è.');
		$this->assertEquals('Test è.', $mc->getText());
		
		
		$mc = new TextPlainContent('Test');
		$this->assertEquals('text/plain', $mc->getContentType());


		$mc = new TextPlainContent('Test');
		$mc->setContentType('text/csv');
		$this->assertEquals('text/csv', $mc->getContentType());


		$mc = new TextPlainContent('Test é.');
		$this->assertEquals( [
			'Content-Type'	=> 'text/plain; charset=UTF-8',
			'Content-Transfer-Encoding' => 'quoted-printable' ], $mc->getHeaders()->toArray());


		$mc = new TextPlainContent('Test é.');
		$mc->headers->set('Bcc', 'user1@gmail.com');
		$mc->headers->set('Bcc', 'user2@gmail.com'); // overrides previously defined header
		$this->assertEquals( [ 'Bcc' => 'user2@gmail.com' ], $mc->headers->toArray());


		$mc = new TextPlainContent('Test é.');
		$mc->headers->set('Bcc', 'user2@gmail.com');
		$this->assertEquals([
			'Content-Type'	=> 'text/plain; charset=UTF-8',
			'Content-Transfer-Encoding' => 'quoted-printable',
			'Bcc' => 'user2@gmail.com'
			], $mc->getAllHeaders()->toArray());


		$mc = new TextPlainContent('Test é.');
		$this->assertEquals("Test =C3=A9.", $mc->getContent());		// C3 : ASCII 195 : 1er octet indique caractère unicode UTF8


		$mc = new TextPlainContent('Test é.');
		$this->assertEquals("Content-Type: text/plain; charset=UTF-8\r\nContent-Transfer-Encoding: quoted-printable\r\n\r\n" .
											"Test =C3=A9.\r\n\r\n", $mc->toString());		
    }
}

?>