SilverStripe AuthSMTP configuration module
------------------------------------------

This module intends to be a drop-in to configure your site to use the AuthSMTP service transparently instead of your
sites default configured email delivery service.

## Requirements:
```
silverstripe/cms: >=3.1
markguinn/silverstripe-email-helpers: 1.1.x
```


## Message Queuing Usage
eg:
extend AuthSMTPQueueEmail instead of Email in a custom email class 
```php
 class CustomEmail extend AuthSMTPQueueEmail{

 }

```
Use below to send email instantly or add to queue
```
$email = CustomEmail::create($from, $recipient, $message)
$email->queueOrSend(); 

```


And then add a cron task to process queued messages

```sh
../framework/sake dev/tasks/AuthSMTPQueueTask
```