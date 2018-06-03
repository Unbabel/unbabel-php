<?php

if (file_exists($file = __DIR__.'/../vendor/autoload.php')) {
    $autoload = require_once $file;
} else {
    throw new RuntimeException('Install dependencies using Composer, to be able to run test suite.');
}

return $autoload;
