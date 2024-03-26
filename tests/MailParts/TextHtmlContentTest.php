<?php

namespace Nettools\Mailing\MailParts\Tests;



use \Nettools\Mailing\MailParts\TextHtmlContent;




class TextHtmlContentTest extends \PHPUnit\Framework\TestCase
{
    public function test()
    {
		$mc = new TextHtmlContent('<b>Test</b> é.');
		$this->assertEquals('<b>Test</b> é.', $mc->getHtml());
	
	
		$mc = new TextHtmlContent('Test é.');
		$mc->setHtml('<strong>Test</strong> è.');
		$this->assertEquals('<strong>Test</strong> è.', $mc->getHtml());
		
		
		$mc = new TextHtmlContent('Test');
		$this->assertEquals('text/html', $mc->getContentType());


		$mc = new TextHtmlContent('Test é.');
		$this->assertEquals( [
								'Content-Type' => 'text/html; charset=UTF-8',
								'Content-Transfer-Encoding' => 'quoted-printable'
							], $mc->getHeaders()->toArray());


		$mc = new TextHtmlContent('<b>Test</b> é.');
		$this->assertEquals("<b>Test</b> =C3=A9.", $mc->getContent());	
    }
}

?>