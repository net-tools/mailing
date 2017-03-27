<?php 

namespace Nettools\Mailing\Tests;



use \Nettools\Mailing\EmlReader;




class EmlReaderTest extends \PHPUnit\Framework\TestCase
{
    public function testPlainHtml()
	{
		// we test multipart/alternative : text/plain, text/html
		// we test headers with simple values and headers with multiple values (separated by ';')
		// we test headers with folding, with or without quotes (")
		// we test iso-8859-1 charset converted to utf8
        $mail = EmlReader::fromFile(__DIR__ . '/data/' . substr(strrchr(__CLASS__, '\\'),1) . '.plainhtml.eml');
		$this->assertNotNull($mail);
		$this->assertInstanceOf('Nettools\Mailing\MailPieces\MailMultipart', $mail);		
		$this->assertEquals('alternative', $mail->getType());
		$this->assertInstanceOf('Nettools\Mailing\MailPieces\MailTextPlainContent', $mail->getPart(0));
		$this->assertStringStartsWith( 
							"At your request, please find below the information you requested :\n" . 
                            "- xxxx\n" . 
                            "Test with french accents : éà\n",

                            $mail->getPart(0)->getText()
						);
		$this->assertInstanceOf('Nettools\Mailing\MailPieces\MailTextHtmlContent', $mail->getPart(1));		
		$this->assertStringStartsWith( 
							"<html><head><title></title><head><body>\n" .
							"At your request, please find below the information you requested :<br/>",
            
                            $mail->getPart(1)->getHtml()
						);



		// multipart/related (embedding)
		// content-disposition is not present, we guess it thanks to the content-id attribute
        $mail = EmlReader::fromFile(__DIR__ . '/data/' . substr(strrchr(__CLASS__, '\\'),1) . '.inline.eml');
		$this->assertNotNull($mail);
		$this->assertInstanceOf('Nettools\Mailing\MailPieces\MailMultipart', $mail);		
		$this->assertInstanceOf('Nettools\Mailing\MailPieces\MailMultipart', $mail->getPart(0));		
		$this->assertEquals('alternative', $mail->getPart(0)->getType());
		$this->assertInstanceOf('Nettools\Mailing\MailPieces\MailTextPlainContent', $mail->getPart(0)->getPart(0));		
		$this->assertStringStartsWith("this is a *unit test* with inline attachment", $mail->getPart(0)->getPart(0)->getText());
		$this->assertInstanceOf('Nettools\Mailing\MailPieces\MailEmbedding', $mail->getPart(1));
		$fname = $mail->getPart(1)->getFile();
		$this->assertFileEquals(__DIR__ . '/data/' . substr(strrchr(__CLASS__, '\\'),1) . '.inline.png', $fname);
		EmlReader::destroy($mail);
		$this->assertFileNotExists($fname);	// destroy a supprimé le fichier temporaire
		


		// multipart/mixed
		// attachment with text/plain content and CRLF newlines
		$mail = EmlReader::fromFile(__DIR__ . '/data/' . substr(strrchr(__CLASS__, '\\'),1) . '.CRLF_attachment.eml');
		$this->assertNotNull($mail);
		$this->assertInstanceOf('Nettools\Mailing\MailPieces\MailMultipart', $mail);		
		$this->assertInstanceOf('Nettools\Mailing\MailPieces\MailTextPlainContent', $mail->getPart(0));
        $this->assertEquals('mixed', $mail->getType());
		$this->assertStringStartsWith(
							"Hi,\n" .
							"\n" .
							"\n" .
							"This is a unit test",
            
                            $mail->getPart(0)->getText()
						);
		$this->assertInstanceOf('Nettools\Mailing\MailPieces\MailAttachment', $mail->getPart(1));
		$fname = $mail->getPart(1)->getFile();
        // gzdecode because GIT or FTP software may convert CRLF to LF
		$this->assertEquals(gzdecode(file_get_contents(__DIR__ . '/data/' . substr(strrchr(__CLASS__, '\\'),1) . '.CRLF_attachment.bin.gz')), file_get_contents($fname)); 
		EmlReader::destroy($mail);
		$this->assertFileNotExists($fname);	

		
		
		// multipart/mixed
		// attachment with text/plain content and LF newlines
		$mail = EmlReader::fromFile(__DIR__ . '/data/' . substr(strrchr(__CLASS__, '\\'),1) . '.LF_attachment.eml');
		$this->assertNotNull($mail);
		$this->assertInstanceOf('Nettools\Mailing\MailPieces\MailMultipart', $mail);		
		$this->assertInstanceOf('Nettools\Mailing\MailPieces\MailTextPlainContent', $mail->getPart(0));
        $this->assertEquals('mixed', $mail->getType());
		$this->assertStringStartsWith(
							"Hi,\n" .
							"\n" .
							"\n" .
							"This is a unit test",
            
                            $mail->getPart(0)->getText()
						);
		$this->assertInstanceOf('Nettools\Mailing\MailPieces\MailAttachment', $mail->getPart(1));
		$fname = $mail->getPart(1)->getFile();
        // gzdecode because GIT or FTP software may convert CRLF to LF
		$this->assertEquals(gzdecode(file_get_contents(__DIR__ . '/data/' . substr(strrchr(__CLASS__, '\\'),1) . '.LF_attachment.bin.gz')), file_get_contents($fname)); 
		EmlReader::destroy($mail);
		$this->assertFileNotExists($fname);	
		

		
		// multipart/mixed with 2 attachments (no multipart/alternative html part)
		$mail = EmlReader::fromFile(__DIR__ . '/data/' . substr(strrchr(__CLASS__, '\\'),1) . '.CRLF_LF_attachments.eml');
		$this->assertNotNull($mail);
		$this->assertInstanceOf('Nettools\Mailing\MailPieces\MailMultipart', $mail);		
		$this->assertInstanceOf('Nettools\Mailing\MailPieces\MailTextPlainContent', $mail->getPart(0));
        $this->assertEquals('mixed', $mail->getType());
        $this->assertStringStartsWith('two attachments', $mail->getPart(0)->getText());
		$this->assertEquals(3, $mail->getCount()); // 3 parts in the mailmultipart : the text/plain one, and the 2 attachments
		$this->assertInstanceOf('Nettools\Mailing\MailPieces\MailAttachment', $mail->getPart(1));
		$this->assertInstanceOf('Nettools\Mailing\MailPieces\MailAttachment', $mail->getPart(2));

        $fname = $mail->getPart(1)->getFile();
		$this->assertEquals(gzdecode(file_get_contents(__DIR__ . '/data/' . substr(strrchr(__CLASS__, '\\'),1) . '.CRLF_attachment.bin.gz')), file_get_contents($fname)); 
		$fname2 = $mail->getPart(2)->getFile();
		$this->assertEquals(gzdecode(file_get_contents(__DIR__ . '/data/' . substr(strrchr(__CLASS__, '\\'),1) . '.LF_attachment.bin.gz')), file_get_contents($fname2)); 
		EmlReader::destroy($mail);
		$this->assertFileNotExists($fname);	
		$this->assertFileNotExists($fname2);
	}

}


?>