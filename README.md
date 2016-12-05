# net-tools/mailing

## Composer library to send emails with PHP

This package contains all classes required to easily build e-mails with PHP, in an object-oriented way.

Attachments and embeddings are supported.


### Setup instructions

To install net-tools/mailing package, just require it through composer : `require net-tools/mailing:^1.0.0`.


### How to use ?

#### Quick email sending 

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


#### Build emails 

If you want to have more control when sending emails, you may build them with Mailer :

```php
$mail = Mailer::addTextHtmlFromText('\*\*This is a\*\* test');
$mail = Mailer::addAttachment($mail, '/home/tmp/invoice.pdf', 'your_invoice.pdf', 'application/pdf');
Mailer::getDefault()->sendmail($mail, 'from-user@test.com', 'recipient@test.com', 'This is a subject');
```

To send emails with SMTP protocol (or any other email sending strategy in the MailSenders subfolder), create the Mailer (instead of getting it throught `getDefault`) with the appropriate class name and an array of parameters :

```php
$smtpmailer = new Mailer(MailSender::SMTP, array('host'=>'mysmtphost.com', 'username'=>'user', 'password'=>'1234'));
$smtpmailer->sendmail($mail);
```


