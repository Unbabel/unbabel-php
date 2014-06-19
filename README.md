# Unbabel's PHP SDK #

Unbabel's PHP SDK is a wrapper around the HTTP API found at https://github.com/Unbabel/unbabel_api

## Requirements ##

* PHP >= 5.3

## Installation ##

The recommended way to install is through composer.

Just create a `composer.json` file for your project:

```json
{
    "require": {
        "unbabel/unbabel-php": "dev-master"
    }
}
```

**Tip:** browse [`unbabel/unbabel-php`](https://packagist.org/packages/unbabel/unbabel-php) page to choose a stable version to use, avoid the `dev-master` meta constraint.

And run these two commands to install it:

```bash
$ curl -sS https://getcomposer.org/installer | php
$ composer install
```

Now you can add the autoloader, and you will have access to the library:

```php
<?php

require 'vendor/autoload.php';
```

## Usage

```php
/**
 * Unbabel's PHP SDK is a wrapper around the HTTP API found at https://github.com/Unbabel/unbabel_api
 *
 * USAGE
 *
 * // just needed if you don't use composer
 * require 'Unbabel.php';
 *
 * use Unbabel/Unbabel;
 *
 * $unbabel = new Unbabel('username', 'apiKey', $sandbox = false);
 *
 * // $resp is an instance of a guzzle response object http://docs.guzzlephp.org/en/latest/http-messages.html#responses
 * $opts = array('callback_url' => 'http://my-awesome-app/unbabel_callback.php');
 * $resp = $unbabel->getTranslation($text, $target_language, $opts);
 * if ($resp->getStatusCode() == 201) {
 *     // Hooray! Now we need to get the uid so when we are called back we know which translation it corresponds to.
 *     $uid = $resp->json()['uid'];
 *     save_uid_for_callback($uid);
 * } else {
 *    // If you think everything should be working correctly and you still get an error,
 *    // send email to sam@unbabel.com to complain.
 * }
 *
 * ////////////////////////////////////////////////
 * //       Other examples
 * ////////////////////////////////////////////////
 *
 * var_dump($unbabel->getTopics()->json());
 * var_dump($unbabel->submit_translation('This is a test', 'pt')->json());
 * var_dump($unbabel->getJobsWithStatus('new')->json());
 * var_dump($unbabel->getTranslation('8a82e622dbBS')->json());
 * var_dump($unbabel->getTones()->json());
 * var_dump($unbabel->getLanguagePairs()->json());
 *
 * $bulk = [
 *     ['text' => 'This is a test', 'target_language' => 'pt'],
 *     ['text' => 'This is a test', 'target_language' => 'es']
 * ];
 * var_dump(Unbabel::submit_bulk_translation($bulk)->json());
 */
 ```
