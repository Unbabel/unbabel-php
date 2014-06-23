<?php

$errorDependencies = 'Install dependencies to run test suite. "php composer.phar install --dev"';

$file = __DIR__ . '/../vendor/autoload.php';
if (!file_exists($file)) {
    throw new RuntimeException($errorDependencies);
}

/** @var \Composer\Autoload\ClassLoader $loader */
$loader = require($file);
$loader->add('Unbabel\Tests', __DIR__);
