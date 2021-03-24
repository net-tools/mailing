# net-tools/mailing

## Composer library to send emails with PHP

This package contains all classes required to easily build e-mails with PHP, in an object-oriented way.

Attachments and embeddings are supported.



## Setup instructions

To install net-tools/mailing package, just require it through composer : `require net-tools/mailing:^1.0.0`.


## How to use ?

### Quick email sending 

To send an email in an easy way, just get an instance of `Mailer` with default email sending strategy, and call `expressSendmail` method. If no attachments to send, omit the last parameter.

```php
Mailer::getDefault()->expressSendmail(
  '<b>This is a</b> test', 
  'from-user@test.com', 
  'recipient@test.com', 
  'This is a subject', 
  array('/home/tmp/invoice.pdf')
);
```

Email technical parts (text/plain, text/html, multipart/alternative, multipart/mixed) will be created automatically ; the default email sending strategy send emails through PHP built-in Mail() function.


### Build emails 

If you want to have more control when sending emails, you may build them with Mailer :

```php
$mail = Mailer::addTextHtmlFromText('\*\*This is a\*\* test');
$mail = Mailer::addAttachment($mail, '/home/tmp/invoice.pdf', 'your_invoice.pdf', 'application/pdf');
Mailer::getDefault()->sendmail($mail, 'from-user@test.com', 'recipient@test.com', 'This is a subject');
```

To send emails with SMTP protocol (or any other email sending strategy in the MailSenders subfolder), create the Mailer (instead of getting it throught `getDefault`) with the appropriate MailSender object

```php
$smtpmailer = new Mailer(new MailSenders\SMTP(array('host'=>'mysmtphost.com', 'username'=>'user', 'password'=>'1234')));
$smtpmailer->sendmail($mail);
```


### Parse an EML file/string to create a MailContent object

Sometimes you have an email and you want to display it on screen. However, you can't echo the raw content. You have to parse the email content to extract the appropriate part (generally, the text/html part) and if necessary the attachments (multipart/mixed part).

To parse the email, just use the EmlReader class and the `fromString` or `fromFile` static methods. They will return a `MailContent` object :

```php
// assuming that $email is a very simple email with a multipart/alternative content
$mail = EmlReader::parseString($email);

// this line prints MailMultipart (class name of the MailContent object)
echo get_class($mail);

// the following lines extract the text/plain and text/html sub-parts
$textplain_content = $mail->getPart(0)->toString();
$htmlpart_content = $mail->getPart(1)->toString();
```

If your email contains attachments or embeddings, don't forget to call `destroy` static method to delete temporary files created during parsing to store attachments and embeddings :

```php
EmlReader::destroy($mail);
```



### Send with queues

Sometimes we don't want to send a lot of emails in one shot, and we may want to queue them somewhere and then send them later, maybe through batches. This is the purpose of
`MailSenderQueue` subfolder. It contains a `Store` and `Queue` classes ; the first one is the facade of queue subsystem, the second stands for a given queue.

To queue items :

```php

// create a Store object to manage queues
$store = new MailSenderQueue\Store('~/home/path_to_queue_subsystem_root');

// create a new queue, which sends emails through batches of 75 items
$queue = $store->createQueue('title_of_queue', 75);
$id = $queue->id;

// create an email content with text/plain and text/html parts
$mail = Mailer::addTextHtmlFromText('\*\*This is a\*\* test');

// then send email (of course this last line is usually called within a loop, to queue all items at once
$queue->push ($mail, 'sender@domain.tld', 'recipient_here@domain.tld', 'Email subject here');

```

Then later on, maybe in another script/call :

```php

// reopen the same store
$store = new MailSenderQueue\Store('~/home/path_to_queue_subsystem_root');

// getting queue with `$id` (saved from above call)
$queue = $store->getQueue($id);

// send a batch
$queue->send(Mailer::getDefault());

```

Please check API reference for full details about Store and Queue objects (deleting queues, dealing with errors, listing queues and recipients).




### Managing mailsenders strategies

The Mailer object, when constructed, accepts a `MailSenders\MailSenderIntf` object, which is a strategy to send the email through. It can be `MailSenders\PHPMail` (to use the built-in `mail()` function) or `MailSenders\SMTP`. Each strategy may have some parameters (for SMTP, those are the host/user/password data).

Sometimes, there are multiple SMTP strategies (to send emails through several hosts). To deals with all those sending strategies, we have created a MailSendersFacade system :
- data (strategies parameters) is stored in a JSON string
- strategy list is stored in a PHP array as strings

Json and strings makes it possible to update parameters withouth too much trouble, and can be stored in a file, a database or hard-coded.

To create the facade object (this is the one we will be dealing with to list strategies or get one ready to use with `Mailer` class :

```php

// list of strategies with an optionnal paramaters set name
$msenders = ['SMTP:params1', 'PHPMail'];

// setting data for all strategies (json-formatted string)
$msdata = '{"SMTP:params1":{"className":"SMTP","host":"value1","username":"value2","password":"value3"}, "PHPMail":{}}';

// active strategy
$ms = 'SMTP:params1';

// create the facade object, creating a list of sending strategies proxies (we don't create real `MailSenders\MailSenderIntf` objects), thanks to json data
$f = MailSendersFacade\Facade::facadeProxiesFromJson($msenders, $msdata, $ms);

// ... do some stuff such as listing mail senders, etc.

// now, get the active mail sender and create the concrete `MailSenders\MailSenderIntf` object, passing it to Mailer constructor
$m = new Mailer($f->getActiveMailSender());
```



### Sending through `MailSenderHelpers\MailSenderHelper`

Sometimes, creating the email content, adding the BCC, subject, replyTo headers or dealing with queues can be tough. The MailSenderHelpers subsystem is here to abstract all this.

```php

$mailer = Mailer::getDefault();

// first, we create a helper object, with minimum parameters (mailer object, body content, sender, recipient subject, optional parameters)
$msh = new MailSenderHelpers\MailSenderHelper($mailer, 'raw mail as text', 'text/plain', 'from@me.com', 'subject of email', $params);
// OR : $msh = new MailSenderHelpers\MailSenderHelper($mailer, 'mail as <b>html</b>', 'text/html', 'from@me.com', 'subject of email', $params);

// prepare the mail : checking required parameters set, building text/html and text/plain parts (from respectively text/plain or text/html body content) 
$mailContent = $msh->render(null);

// send the email rendered
$msh->send($mailContent, 'recipient@here.com');

```

Of course, what is interesting is the optional `$params` last argument, as it may contains :
- a `template` parameter (the body content of constructor is inserted in the template, replacing any %content% string
- a `bcc` parameter, to send the email to its recipient AND a bcc one (to be given as a string)
- a `testMode` parameter ; if set to True, the emails won't be sent to real recipients during `send` calls, but to test recipients (see below)
- a `testRecipients` parameter ; a PHP array of test emails to send emails to, if `testMode` equals True
- a `replyTo` parameter ; if set with an email string, it will insert a specific header in the email so that answer should be returned to another address
- a `toOverride` parameter ; if set, all emails are sent to this address (debug purposes ?)
- a `queue` parameter ; if set, a queue of the name provided will be created and call to `send` will push emails to the queue
- a `queueParams` parameter ; if using a queue, this is an associative array of `Store` object constructor parameters (root of queue subsystem and batch count)

The most interesting are the queue parameters, as it makes it possible to send emails either directly or through a queue, just with the same interface (MailSenderHelper class).




## API Reference

To read the entire API reference, please refer to the PHPDoc here :
https://nettools.ovh/api-reference/net-tools/namespaces/nettools-mailing.html



## PHPUnit

To test with PHPUnit, point the -c configuration option to the /phpunit.xml configuration file.

