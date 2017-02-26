<?php 


use \Nettools\Mailing\EmlReader;



class EmlReaderTest extends PHPUnit\Framework\TestCase
{
    public function testPlainHtml()
	{
		// we test multipart/alternative : text/plain, text/html
		// we test headers with simple values and headers with multiple values (separated by ';')
		// we test headers with folding, with or without quotes (")
		// we test iso-8859-1 charset converted to utf8
		$mail = EmlReader::fromFile(str_replace('.php', '', __FILE__) . '.plainhtml.eml');
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

/*

		// multipart/related
		// content-disposition absent, à deviner avec présence content-id
		$this->setSubject('EmlReader::fromFile(multipart/related:inline)');								
		$mail = EmlReader::fromFile($this->getRoot() . '_data/inline.eml');
		$this->assertNotNull($mail);
		$this->assertObject($mail);
		$this->assertObjectInstanceOf($mail, '\Ppast\Mailing\MailPieces\MailMultipart');		
		$this->assertObjectInstanceOf($mail->getPart(0), '\Ppast\Mailing\MailPieces\MailMultipart');		
		$this->assertEquals($mail->getPart(0)->getType(), 'alternative');
		$this->assertObjectInstanceOf($mail->getPart(0)->getPart(0), '\Ppast\Mailing\MailPieces\MailTextPlainContent');		
		$this->assertStringStartsWith($mail->getPart(0)->getPart(0)->getText(), 
							"Bonjour, \n" .
							"\n" .
							" \n" .
							"\n" . 
							"Nous sommes une entreprise nationale de service à la personne "
						);
		$this->assertObjectInstanceOf($mail->getPart(1), '\Ppast\Mailing\MailPieces\MailEmbedding');
		$fname = $mail->getPart(1)->getFile();
		$this->assertFileEquals($fname, file_get_contents($this->getRoot() . '_data/inline.jpg'));
		EmlReader::destroy($mail);
		$this->assertFileNotExists($fname);	// destroy a supprimé le fichier temporaire
		


		// multipart/related
		// attachment text/plain caractère saut de ligne CRLF
		$this->setSubject('EmlReader::fromFile(multipart/related:attachment-CRLF)');								
		$mail = EmlReader::fromFile($this->getRoot() . '_data/attachment-textplain.eml');
		$this->assertNotNull($mail);
		$this->assertObject($mail);
		$this->assertObjectInstanceOf($mail, '\Ppast\Mailing\MailPieces\MailMultipart');		
		$this->assertObjectInstanceOf($mail->getPart(0), '\Ppast\Mailing\MailPieces\MailMultipart');		
		$this->assertEquals($mail->getType(), 'mixed');
		$this->assertEquals($mail->getPart(0)->getType(), 'alternative');
		$this->assertObjectInstanceOf($mail->getPart(0)->getPart(0), '\Ppast\Mailing\MailPieces\MailTextPlainContent');		
		$this->assertStringStartsWith($mail->getPart(0)->getPart(0)->getText(), 
							"Bonjour,\n" .
							"\n" .
							"\n" .
							"Sur mon site www.xyz.fr, j'ai détecté"
						);
		$this->assertObjectInstanceOf($mail->getPart(1), '\Ppast\Mailing\MailPieces\MailAttachment');
		$fname = $mail->getPart(1)->getFile();
		$this->assertFileEquals($fname, gzdecode(file_get_contents($this->getRoot() . '_data/attachmentCRLF.txt.gz'))); // gzdecode car GIT convertit les sauts de ligne CRLF en LF
		EmlReader::destroy($mail);
		$this->assertFileNotExists($fname);	// destroy a supprimé le fichier temporaire

		
		
		// multipart/related
		// attachment text/plain caractère saut de ligne LF
		$this->setSubject('EmlReader::fromFile(multipart/related:attachment-LF)');								
		$mail = EmlReader::fromFile($this->getRoot() . '_data/attachment-textplainLF.eml');
		$this->assertNotNull($mail);
		$this->assertObject($mail);
		$this->assertObjectInstanceOf($mail, '\Ppast\Mailing\MailPieces\MailMultipart');		
		$this->assertObjectInstanceOf($mail->getPart(0), '\Ppast\Mailing\MailPieces\MailMultipart');		
		$this->assertEquals($mail->getType(), 'mixed');
		$this->assertEquals($mail->getPart(0)->getType(), 'alternative');
		$this->assertObjectInstanceOf($mail->getPart(0)->getPart(0), '\Ppast\Mailing\MailPieces\MailTextPlainContent');		
		$this->assertStringStartsWith($mail->getPart(0)->getPart(0)->getText(), 
							"Bonjour,\n" .
							"\n" .
							"\n" .
							"Sur mon site www.xyz.fr, j'ai détecté"
						);
		$this->assertObjectInstanceOf($mail->getPart(1), '\Ppast\Mailing\MailPieces\MailAttachment');
		$fname = $mail->getPart(1)->getFile();
		$this->assertFileEquals($fname, file_get_contents($this->getRoot() . '_data/attachmentLF.txt'));
		EmlReader::destroy($mail);
		$this->assertFileNotExists($fname);	// destroy a supprimé le fichier temporaire
		

		
		// multipart/mixed avec 2 PJ (pas de alternative html)
		// 2 attachments + pollution content-disposition
		$this->setSubject('EmlReader::fromFile(multipart/related:attachments-X2)');								
		$mail = EmlReader::fromFile($this->getRoot() . '_data/attachments-x2.eml');
		$this->assertNotNull($mail);
		$this->assertObject($mail);
		$this->assertObjectInstanceOf($mail, '\Ppast\Mailing\MailPieces\MailMultipart');		
		$this->assertEquals($mail->getType(), 'mixed');
		$this->assertObjectInstanceOf($mail->getPart(0), '\Ppast\Mailing\MailPieces\MailTextPlainContent');		
		$this->assertStringStartsWith($mail->getPart(0)->getText(), 'deux pj');
		$this->assertEquals($mail->getCount(), 3);
		$this->assertObjectInstanceOf($mail->getPart(1), '\Ppast\Mailing\MailPieces\MailAttachment');
		$fname = $mail->getPart(1)->getFile();
		$this->assertFileEquals($fname, gzdecode(file_get_contents($this->getRoot() . '_data/attachmentCRLF.txt.gz')));
		$this->assertObjectInstanceOf($mail->getPart(2), '\Ppast\Mailing\MailPieces\MailAttachment');
		$fname2 = $mail->getPart(2)->getFile();
		$this->assertFileEquals($fname2, file_get_contents($this->getRoot() . '_data/attachmentLF.txt'));
		EmlReader::destroy($mail);
		$this->assertFileNotExists($fname);	// destroy a supprimé le fichier temporaire
		$this->assertFileNotExists($fname2);	// destroy a supprimé le fichier temporaire*/
	}

}


?>