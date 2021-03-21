<?php 

namespace Nettools\Mailing\MailSenderQueue\Tests;



use \Nettools\Mailing\MailSenderQueue\Data;
use \Nettools\Mailing\MailSenderQueue\Queue;
use \org\bovigo\vfs\vfsStream;




class QueueTest extends \PHPUnit\Framework\TestCase
{
	protected $_vfs = NULL;
	

	public function setUp() :void
	{
		$this->_vfs = vfsStream::setup('root');
	}
	
	
		
	public function testMSQCreate()
	{
		$params = ['root'=>$this->_vfs->url()];
		$q = Queue::create('qname', $params);

		$this->assertEquals(0, $q->count);
		$this->assertEquals(50, $q->batchCount);
		$this->assertEquals(0, $q->sendOffset);
		$this->assertEquals(NULL, $q->lastBatchDate);
		$this->assertEquals([], $q->sendLog);
		$this->assertEquals('qname', $q->title);
		$this->assertEquals(false, $q->locked);
		$this->assertEquals(0, $q->volume);
		
		
		// asserting directory of queue has been created
		$this->assertEquals(true, $this->_vfs->hasChild($q->id));
		
		
		$q->rename('newqname');
		$this->assertEquals('newqname', $q->title);
		
		$q->locked = true;
		$q->unlock();
		$this->assertEquals(false, $q->locked);
		
		
		// asserting directory of queue has been created
		$q->delete();
		$this->assertEquals(false, $this->_vfs->hasChild($q->id));
	}
	
	
	
	public function testPush()
	{
		$params = ['root'=>$this->_vfs->url()];
		$q = Queue::create('qname', $params);

		$mail = Mailer::createText('mail content here');
		$q->push($mail, 'sender@home.com', 'recipient@here.com', 'Subject here');
		
		$this->assertEquals(1, $q->count);
		$this->assertEquals(strlen('Subject here'), $q->volume);
		
			
		// testing data files
		$qid = $q->id;
		$this->assertEquals(true, $this->_vfs->hasChild("$qid/$qid.0.data"));
		$this->assertEquals(true, $this->_vfs->hasChild("$qid/$qid.0.mail"));
		
		$d = Data::read($q, 0, true);
		$this->assertEquals('Subject here', $d->subject);
		$this->assertEquals('recipient@here.com', $d->to);
		$this->assertEquals("MIME-Version: 1.0\r\nFrom: sender@home.com", $d->headers);
		$this->assertEquals(Data::STATUS_TOSEND, $d->status);
		
		
		
		$mail = Mailer::createText('another mail content here');
		$q->push($mail, 'sender@home.com', 'recipient2@here.com', 'Other subject here');
		$this->assertEquals(true, $this->_vfs->hasChild("$qid/$qid.1.data"));
		$this->assertEquals(true, $this->_vfs->hasChild("$qid/$qid.1.mail"));
		$this->assertEquals(2, $q->count);
		
		$d = Data::read($q, 1, true);
		$this->assertEquals('Other subject here', $d->subject);
		$this->assertEquals('recipient2@here.com', $d->to);
		$this->assertEquals("MIME-Version: 1.0\r\nFrom: sender@home.com", $d->headers);
		$this->assertEquals(Data::STATUS_TOSEND, $d->status);
			
		
		// recipients
		$this->assertEquals(['recipient@here.com', 'recipient2@here.com'], $q->recipients());
		
		
		// set a recipient as an error
		$this->assertEquals(0, count($q->sendLog));
		$q->recipientError(1);
		$derr = Data::read($q, 1, true);
		$this->assertEquals(Data::STATUS_ERROR, $derr->status);
		$this->assertEquals(1, count($q->sendLog));
		
		
		// creating new queue with errors
		$q2 = $q->newQueueFromErrors('qerr');
		$q2id = $q2->id;
		$this->assertEquals(1, $q2->count);
		$this->assertEquals(true, $this->_vfs->hasChild("$q2id/$q2id.0.data"));
		$this->assertEquals(true, $this->_vfs->hasChild("$q2id/$q2id.0.mail"));
		
		// error sending in first queue still here (error data is copied, not moved)
		$this->assertEquals(2, $q->count);

		// reading in error queue
		$d2 = Data::read($q2, 0, true);
		$this->assertEquals($d2->subject, $derr->subject);
		$this->assertEquals($d2->to, $derr->to);
		$this->assertEquals($d2->headers, $derr->headers);
		$this->assertEquals(Data::STATUS_TOSEND, $derr->status);
		
		
		// reading eml content
		$eml = $q->emlAt(0);
		$this->assertStringStartsWith("MIME-Version: 1.0\r\nFrom: sender@home.com\r\nTo: recipient@here.com\r\nSubject: Subject here", $eml);
		$this->assertStringContainsString('mail content here', $eml);
		
		
		
		// searching recipient
		$this->assertEquals(false, $q->search('who@home.com'));
		$this->assertEquals(1, $q->search('recipient2@here.com'));		
		
		
		// clearing log
		$q->clearLog();
		$this->assertEquals(0, count($q->sendLog));
	}
	
	
	
	public function testSend()
	{
		$params = ['root'=>$this->_vfs->url()];
		$q = Queue::create('qname', $params, 1);		// batchcount = 1
		$this->assertEquals(1, $q->batchCount);


		// creating content and pushing to queue
		$mail = Mailer::createText('mail content here');
		$q->push($mail, 'sender@home.com', 'recipient@here.com', 'Subject here');
		$mail = Mailer::createText('other mail content here');
		$q->push($mail, 'sender@home.com', 'recipient2@here.com', 'Subject2 here');

		$this->assertEquals(2, $q->count);
		$this->assertEquals(NULL, $q->lastBatchDate);
		
		// sending
		$ms = new \Nettools\Mailing\MailSenders\Virtual();
		$mailer = new Mailer($ms);
		$q->send($mailer);
		
		// only one mail sent (batchCount = 1)
		$this->assertEquals(false, is_null($q->lastBatchDate));
		$this->assertEquals(1, $q->sendOffset);
		$this->assertEquals(false, $q->locked);

		$d = Data::read($q, 0, true);
		$this->assertEquals(Data::STATUS_SENT, $d->status);
		$d = Data::read($q, 1, true);
		$this->assertEquals(Data::STATUS_TOSEND, $d->status);
		
		
		// send again
		$q->send($mailer);		
		$this->assertEquals(2, $q->sendOffset);
		$this->assertEquals(true, $q->locked);
		
		$d = Data::read($q, 0, true);
		$this->assertEquals(Data::STATUS_SENT, $d->status);
		$d = Data::read($q, 1, true);
		$this->assertEquals(Data::STATUS_SENT, $d->status);

		$this->assertEquals(2, count($ms->getSent()));
		$this->assertStringContainsString('recipient@here.com', $ms->getSent()[0]);
		$this->assertStringContainsString('recipient2@here.com', $ms->getSent()[1]);
		
		
		// resend a mail
		$q->resend($mailer, 0, null, 'new_recipient@here.com');
		$this->assertEquals(3, count($ms->getSent()));
		$this->assertStringContainsString('new_recipient@here.com', $ms->getSent()[2]);
	}
	
}


?>