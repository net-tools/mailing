<?php

use \Nettools\Mailing\MailPieces\MailTextHtmlContent;



class MailTextHtmlContentTest extends PHPUnit_Framework_TestCase
{
    public function test()
    {
		$mc = new MailTextHtmlContent('<b>Test</b> é.');
		$this->assertEquals('<b>Test</b> é.', $mc->getHtml());
	
	
		$mc = new MailTextHtmlContent('Test é.');
		$mc->setHtml('<strong>Test</strong> è.');
		$this->assertEquals('<strong>Test</strong> è.', $mc->getHtml());
		
		
		$mc = new MailTextHtmlContent('Test');
		$this->assertEquals('text/html', $mc->getContentType());


		$mc = new MailTextHtmlContent('Test é.');
		$this->assertEquals("Content-Type: text/html; charset=UTF-8\r\nContent-Transfer-Encoding: quoted-printable", $mc->getHeaders());


		$mc = new MailTextHtmlContent('<b>Test</b> é.');
		$this->assertEquals("<b>Test</b> =C3=A9.", $mc->getContent());	
    }
}

?>