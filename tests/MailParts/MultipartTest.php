<?php

namespace Nettools\Mailing\MailParts\Tests;



use \Nettools\Mailing\MailParts\TextPlainContent;
use \Nettools\Mailing\MailParts\TextHtmlContent;
use \Nettools\Mailing\MailParts\Multipart;




class MultipartTest extends \PHPUnit\Framework\TestCase
{
    public function test()
    {
        // from
		$mcplain = new TextPlainContent('Test é.');
		$mchtml = new TextHtmlContent('<b>Test</b> é.');
		$mcalt = Multipart::from('alternative', $mcplain, $mchtml);
		$this->assertInstanceOf('Nettools\Mailing\MailParts\Multipart', $mcalt);


        // getCount : 2 parts
		$this->assertEquals(2, $mcalt->getCount());


        // getPart
		$this->assertEquals($mcplain, $mcalt->getPart(0));
		$this->assertEquals($mchtml, $mcalt->getPart(1));


        // getType
		$this->assertEquals('alternative', $mcalt->getType());


        // getSeparator
		$this->assertMatchesRegularExpression('/---[a-fA-F0-9]{32}/', $mcalt->getSeparator());

        
        // getContentType
		$mcalt = Multipart::from('alternative', new TextPlainContent('Test é.'), new TextHtmlContent('<b>Test</b> é.'));
		$this->assertEquals('multipart/alternative', $mcalt->getContentType());


        // getHeaders
		$this->assertEquals( [ 'Content-Type' => "multipart/alternative;\r\n boundary=\"" . $mcalt->getSeparator() . "\"" ], $mcalt->getHeaders()->toArray());
		
		
		// getContent
		$mcalt = Multipart::from('alternative', new TextPlainContent('Test é.'), new TextHtmlContent('<b>Test</b> é.'));
		$mcalt_content_expected = "--" . $mcalt->getSeparator() . "\r\n" .
				"Content-Type: text/plain; charset=UTF-8\r\n" .
				"Content-Transfer-Encoding: quoted-printable\r\n" .
				"\r\n" .
				"Test =C3=A9.\r\n" .
				"\r\n" .
				"--" . $mcalt->getSeparator() . "\r\n" .
				"Content-Type: text/html; charset=UTF-8\r\n" .
				"Content-Transfer-Encoding: quoted-printable\r\n" .
				"\r\n" .
				"<b>Test</b> =C3=A9.\r\n" .
				"\r\n" .
				"--" . $mcalt->getSeparator() . "--";
		
		$this->assertEquals($mcalt_content_expected, $mcalt->getContent());


        // toString
		$this->assertEquals("Content-Type: multipart/alternative;\r\n boundary=\"" . $mcalt->getSeparator() . "\"\r\n\r\n" . $mcalt_content_expected . "\r\n\r\n",
                           $mcalt->toString());
    }
}

?>