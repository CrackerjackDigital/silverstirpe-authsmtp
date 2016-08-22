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
add this to where email notifications are created
```php
 AuthSMTPQueueModel::addMessage(
            "youremail@example.com",
            "email subject",
            "This is the full email content",
            "EmailTemplateNameHere",
            ["Name" => "Name content"]
        );
```

And then add a cron task to process queued messages

```sh
../framework/sake dev/tasks/AuthSMTPQueueTask
```