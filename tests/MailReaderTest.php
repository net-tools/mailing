<?php 

namespace Nettools\Mailing\Tests;



use \Nettools\Mailing\MailReader;




class MailReaderTest extends \PHPUnit\Framework\TestCase
{
    public function testMailReader()
	{
		// we test multipart/alternative : text/plain, text/html
		// we test headers with simple values and headers with multiple values (separated by ';')
		// we test headers with folding, with or without quotes (")
		// we test iso-8859-1 charset converted to utf8
        $mail = MailReader::fromFile(__DIR__ . '/data/' . substr(strrchr(__CLASS__, '\\'),1) . '.plainhtml.eml');
		$this->assertInstanceOf('Nettools\Mailing\MailReader', $mail);
		$this->assertInstanceOf('Nettools\Mailing\MailParts\Content', $mail->email);
		$this->assertTrue($mail->headers instanceof \Nettools\Mailing\MailerEngine\Headers);
		
		$this->assertEquals(
			array(
				'Content-Type' 	=> "multipart/alternative; boundary=\"----=_Part_13585_2454228.1420641166034\"",
				'From'			=> 'sent from éric <from_eric@here.com>',
				'Subject'		=> 'This is a subject with accents éà'
			),
			
			$mail->headers->toArray());
	}

}


?>