<?php

namespace Nettools\Mailing\Tests;



use \Nettools\Mailing\MailCheckers\TrumailIo;




class TrumailIoTest extends \PHPUnit\Framework\TestCase
{
    public function test()
    {
		//{"address":"xxxx@gmail.com","username":"xxxx","domain":"gmail.com","md5Hash":"af1650be8b5d7d293ce8d1efc09a062f","suggestion":"","validFormat":true,"deliverable":true,"fullInbox":false,"hostExists":true,"catchAll":false,"gravatar":false,"role":false,"disposable":false,"free":true}
		
		$stub_guzzle_response = $this->createMock(\Psr\Http\Message\ResponseInterface::class);
		$stub_guzzle_response->method('getStatusCode')->willReturn(200);
		$stub_guzzle_response->method('getBody')->willReturn('{"address":"xxxx@gmail.com","username":"xxxx","domain":"gmail.com","md5Hash":"af1650be8b5d7d293ce8d1efc09a062f","suggestion":"","validFormat":true,"deliverable":true,"fullInbox":false,"hostExists":true,"catchAll":false,"gravatar":false,"role":false,"disposable":false,"free":true}');
				
		// creating stub for guzzle client ; any of the request (GET, POST, PUT, DELETE) will return the guzzle response
		$stub_guzzle = $this->createMock(\GuzzleHttp\Client::class);
		
		// asserting that method Request is called with the right parameters, in particular, the options array being merged with default timeout options
		$stub_guzzle->expects($this->once())->method('request')->with(
						$this->equalTo('GET'), 
						$this->equalTo(TrumailIo::URL), 
						$this->equalTo(
								array(
									'query' => [
										'email'		=> 'xxxx@gmail.com',
									]
								)
							)
					)
					->willReturn($stub_guzzle_response);
		
		
		
		$chk = new TrumailIo($stub_guzzle);
		$r = $chk->check('xxxx@gmail.com');
		$this->assertEquals(true, $r);
    }
	
	
	
    public function testKo()
    {
		$stub_guzzle_response = $this->createMock(\Psr\Http\Message\ResponseInterface::class);
		$stub_guzzle_response->method('getStatusCode')->willReturn(200);
		$stub_guzzle_response->method('getBody')->willReturn('{"address":"xxxx@gmail.com","username":"xxxx","domain":"gmail.com","md5Hash":"af1650be8b5d7d293ce8d1efc09a062f","suggestion":"","validFormat":true,"deliverable":false,"fullInbox":false,"hostExists":true,"catchAll":false,"gravatar":false,"role":false,"disposable":false,"free":true}');
				
		// creating stub for guzzle client ; any of the request (GET, POST, PUT, DELETE) will return the guzzle response
		$stub_guzzle = $this->createMock(\GuzzleHttp\Client::class);
		
		// asserting that method Request is called with the right parameters, in particular, the options array being merged with default timeout options
		$stub_guzzle->expects($this->once())->method('request')->with(
						$this->equalTo('GET'), 
						$this->equalTo(TrumailIo::URL), 
						$this->equalTo(
								array(
									'query' => [
										'email'		=> 'xxxx@gmail.com',
									]
								)
							)
					)
					->willReturn($stub_guzzle_response);
		
		
		
		$chk = new TrumailIo($stub_guzzle);
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
						$this->equalTo(TrumailIo::URL), 
						$this->equalTo(
								array(
									'query' => [
										'email'		=> 'xxxx@gmail.com',
									]
								)
							)
					)
					->willReturn($stub_guzzle_response);
		
		
		
		$chk = new TrumailIo($stub_guzzle);
		
		$this->expectException(\Nettools\Mailing\MailCheckers\Exception::class);
		$this->expectExceptionMessage("API error for email 'xxxx@gmail.com' : No answer yet");
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
						$this->equalTo(TrumailIo::URL), 
						$this->equalTo(
								array(
									'query' => [
										'email'		=> 'xxxx@gmail.com',
									]
								)
							)
					);
		
		
		

		$chk = new TrumailIo($stub_guzzle);
		
		$this->expectException(\Nettools\Mailing\MailCheckers\Exception::class);
		$r = $chk->check('xxxx@gmail.com');
    }
}

?>