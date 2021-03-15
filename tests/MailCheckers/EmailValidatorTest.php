<?php

namespace Nettools\Mailing\Tests;



use \Nettools\Mailing\MailCheckers\EmailValidator;




class EmailValidatorTest extends \PHPUnit\Framework\TestCase
{
    public function test()
    {
/*
		{
		  "status":200,"ratelimit_remain":99,"ratelimit_seconds":299,"info":"OK - Valid Address","details":"The mail address is valid.","freemail":true
		}

*/
		$stub_guzzle_response = $this->createMock(\Psr\Http\Message\ResponseInterface::class);
		$stub_guzzle_response->method('getStatusCode')->willReturn(200);
		$stub_guzzle_response->method('getBody')->willReturn('{
		  "status":200,"ratelimit_remain":99,"ratelimit_seconds":299,"info":"OK - Valid Address","details":"The mail address is valid.","freemail":true
		}');
				
		// creating stub for guzzle client ; any of the request (GET, POST, PUT, DELETE) will return the guzzle response
		$stub_guzzle = $this->createMock(\GuzzleHttp\Client::class);
		
		// asserting that method Request is called with the right parameters, in particular, the options array being merged with default timeout options
		$stub_guzzle->expects($this->once())->method('request')->with(
						$this->equalTo('GET'), 
						$this->equalTo(EmailValidator::URL), 
						$this->equalTo(
								array(
									'query' => [
										'EmailAddress'	=> 'xxxx@gmail.com',
										'APIKey'		=> 'apikey',
										'Timeout'		=> 5
									]
								)
							)
					)
					->willReturn($stub_guzzle_response);
		
		
		
		$chk = new EmailValidator($stub_guzzle, 'apikey');
		$r = $chk->check('xxxx@gmail.com');
		$this->assertEquals(true, $r);
    }
	
	
	
    public function testKo()
    {
		$stub_guzzle_response = $this->createMock(\Psr\Http\Message\ResponseInterface::class);
		$stub_guzzle_response->method('getStatusCode')->willReturn(200);
		$stub_guzzle_response->method('getBody')->willReturn('{
		  "status":410,"ratelimit_remain":99,"ratelimit_seconds":299,"info":"Address rejected","details":"The mail server for the recipient domain does not accept messages to this address.","freemail":true
		}');
				
		// creating stub for guzzle client ; any of the request (GET, POST, PUT, DELETE) will return the guzzle response
		$stub_guzzle = $this->createMock(\GuzzleHttp\Client::class);
		
		// asserting that method Request is called with the right parameters, in particular, the options array being merged with default timeout options
		$stub_guzzle->expects($this->once())->method('request')->with(
						$this->equalTo('GET'), 
						$this->equalTo(EmailValidator::URL), 
						$this->equalTo(
								array(
									'query' => [
										'EmailAddress'	=> 'xxxx@gmail.com',
										'APIKey'		=> 'apikey',
										'Timeout'		=> 5
									]
								)
							)
					)
					->willReturn($stub_guzzle_response);
		
		
		
		$chk = new EmailValidator($stub_guzzle, 'apikey');
		$r = $chk->check('xxxx@gmail.com');
		$this->assertEquals(false, $r);
    }
	
	
    public function testHttpError()
    {
		$stub_guzzle_response = $this->createMock(\Psr\Http\Message\ResponseInterface::class);
		$stub_guzzle_response->method('getStatusCode')->willReturn(500);
				
		// creating stub for guzzle client ; any of the request (GET, POST, PUT, DELETE) will return the guzzle response
		$stub_guzzle = $this->createMock(\GuzzleHttp\Client::class);
		
		// asserting that method Request is called with the right parameters, in particular, the options array being merged with default timeout options
		$stub_guzzle->expects($this->once())->method('request')->with(
						$this->equalTo('GET'), 
						$this->equalTo(EmailValidator::URL), 
						$this->equalTo(
								array(
									'query' => [
										'EmailAddress'	=> 'xxxx@gmail.com',
										'APIKey'		=> 'apikey',
										'Timeout'		=> 5
									]
								)
							)
					);
		
		
		

		$chk = new EmailValidator($stub_guzzle, 'apikey');
		
		$this->expectException(\Nettools\Mailing\MailCheckers\Exception::class);
		$r = $chk->check('xxxx@gmail.com');
    }
}

?>