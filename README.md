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


#### Parse an EML file/string to create a MailContent object

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


## API Reference

To read the entire API reference, please refer to the PHPDoc here :
http://net-tools.ovh/api-reference/net-tools/Nettools/Mailing.html
