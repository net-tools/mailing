<?php

namespace Nettools\Mailing\Tests;



use \Nettools\Mailing\MailCheckers\Bouncer;




class BouncerTest extends \PHPUnit\Framework\TestCase
{
    public function test()
    {
/*
		{
		  "email": "john@usebouncer.com",
		  "status": "deliverable",
		  "reason": "accepted_email",
		  "domain": {
			"name": "usebouncer.com",
			"acceptAll": "no",
			"disposable": "no",
			"free": "no"
		  },
		  "account": {
			"role": "no",
			"disabled": "no",
			"fullMailbox": "no"
		  }
		}

*/
		$stub_guzzle_response = $this->createMock(\Psr\Http\Message\ResponseInterface::class);
		$stub_guzzle_response->method('getStatusCode')->willReturn(200);
		$stub_guzzle_response->method('getBody')->willReturn('{
		  "email": "xxxx@gmail.com",
		  "status": "deliverable",
		  "reason": "accepted_email",
		  "domain": {
			"name": "usebouncer.com",
			"acceptAll": "no",
			"disposable": "no",
			"free": "no"
		  },
		  "account": {
			"role": "no",
			"disabled": "no",
			"fullMailbox": "no"
		  }
		}');
				
		// creating stub for guzzle client ; any of the request (GET, POST, PUT, DELETE) will return the guzzle response
		$stub_guzzle = $this->createMock(\GuzzleHttp\Client::class);
		
		// asserting that method Request is called with the right parameters, in particular, the options array being merged with default timeout options
		$stub_guzzle->expects($this->once())->method('request')->with(
						$this->equalTo('GET'), 
						$this->equalTo(Bouncer::URL), 
						$this->equalTo(
								array(
									'query' => [
										'email'		=> 'xxxx@gmail.com',
									],
									'headers'	=> ['x-api-key' => 'apikey']
								)
							)
					)
					->willReturn($stub_guzzle_response);
		
		
		
		$chk = new Bouncer($stub_guzzle, 'apikey');
		$r = $chk->check('xxxx@gmail.com');
		$this->assertEquals(true, $r);
    }
	
	
	
    public function testKo()
    {
		$stub_guzzle_response = $this->createMock(\Psr\Http\Message\ResponseInterface::class);
		$stub_guzzle_response->method('getStatusCode')->willReturn(200);
		$stub_guzzle_response->method('getBody')->willReturn('{
		  "email": "xxxx@gmail.com",
		  "status": "undeliverable",
		  "reason": "rejected_email",
		  "domain": {
			"name": "usebouncer.com",
			"acceptAll": "no",
			"disposable": "no",
			"free": "no"
		  },
		  "account": {
			"role": "no",
			"disabled": "no",
			"fullMailbox": "no"
		  }
		}');
				
		// creating stub for guzzle client ; any of the request (GET, POST, PUT, DELETE) will return the guzzle response
		$stub_guzzle = $this->createMock(\GuzzleHttp\Client::class);
		
		// asserting that method Request is called with the right parameters, in particular, the options array being merged with default timeout options
		$stub_guzzle->expects($this->once())->method('request')->with(
						$this->equalTo('GET'), 
						$this->equalTo(Bouncer::URL), 
						$this->equalTo(
								array(
									'query' => [
										'email'		=> 'xxxx@gmail.com',
									],
									'headers'	=> ['x-api-key' => 'apikey']
								)
							)
					)
					->willReturn($stub_guzzle_response);
		
		
		
		$chk = new Bouncer($stub_guzzle, 'apikey');
		$r = $chk->check('xxxx@gmail.com');
		$this->assertEquals(false, $r);
    }
	
	
    public function testBadResponse()
    {
		$stub_guzzle_response = $this->createMock(\Psr\Http\Message\ResponseInterface::class);
		$stub_guzzle_response->method('getStatusCode')->willReturn(200);
		$stub_guzzle_response->method('getBody')->willReturn('{"message":"No answer yet"}');
				
		// creating stub for guzzle client ; any of the request (GET, POST, PUT, DELETE) will return the guzzle response
		$stub_guzzle = $this->createMock(\GuzzleHttp\Client::class);
		
		// asserting that method Request is called with the right parameters, in particular, the options array being merged with default timeout options
		$stub_guzzle->expects($this->once())->method('request')->with(
						$this->equalTo('GET'), 
						$this->equalTo(Bouncer::URL), 
						$this->equalTo(
								array(
									'query' => [
										'email'		=> 'xxxx@gmail.com',
									],
									'headers'	=> ['x-api-key' => 'apikey']
								)
							)
					)
					->willReturn($stub_guzzle_response);
		
		
		
		$chk = new Bouncer($stub_guzzle, 'apikey');
		
		$this->expectException(\Nettools\Mailing\MailCheckers\Exception::class);
		$r = $chk->check('xxxx@gmail.com');
		
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
						$this->equalTo(Bouncer::URL), 
						$this->equalTo(
								array(
									'query' => [
										'email'		=> 'xxxx@gmail.com',
									],
									'headers'	=> ['x-api-key' => 'apikey']
								)
							)
					);
		
		
		

		$chk = new Bouncer($stub_guzzle, 'apikey');
		
		$this->expectException(\Nettools\Mailing\MailCheckers\Exception::class);
		$r = $chk->check('xxxx@gmail.com');
    }
}

?>