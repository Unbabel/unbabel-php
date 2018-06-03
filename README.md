# Unbabel's PHP SDK #

Unbabel's PHP SDK is a wrapper around the [Unbabel HTTP API](https://developers.unbabel.com/v2/docs).

## Requirements ##

* PHP: 7.x

## Installation ##

The recommended way to install is through [Composer](https://getcomposer.org):

```bash
$ composer require unbabel/unbabel-php
```

## Usage

```php
<?php

require 'vendor/autoload.php';

use Unbabel\Unbabel;
use GuzzleHttp\Client;

$httpClient = new Client();
$unbabel = new Unbabel(
    'username', 
    'apiKey',
     false, // Use sandbox server?
     $httpClient
);

$opts = array('callback_url' => 'http://example.com/unbabel_callback.php');
$resp = $unbabel->submitTranslation('This is a test', 'pt', $opts);
if ($resp->getStatusCode() === 201) {
    // Hooray! Now we need to get the uid so when we are called back we know which translation it corresponds to.
    var_dump(json_decode($resp->getBody()->getContents(), true)['uid']);
} else {
    // If you think everything should be working correctly and you still get an error,
    // send email to tech-support@unbabel.com to complain.
    var_dump($resp->getBody());
    
    exit;
}

// Other examples:
var_dump($unbabel->getTopics()->getBody());
var_dump($unbabel->getJobsWithStatus('new')->getBody());
var_dump($unbabel->getTranslation('8a82e622dbBS')->getBody());
var_dump($unbabel->getTones()->getBody());
var_dump($unbabel->getLanguagePairs()->getBody());

$bulk = [
    ['text' => 'This is a test', 'target_language' => 'pt'],
    ['text' => 'This is a test', 'target_language' => 'es']
];
var_dump($unbabel->submitBulkTranslation($bulk)->getBody());
```

## Contributing

[Read about](CONTRIBUTING.md)
