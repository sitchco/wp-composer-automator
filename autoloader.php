<?php

if (! defined('ABSPATH')) {
    exit; // Exit if accessed directly.
}
// ==============================
// Composer autoloader if present
// ==============================

$autoloader = dirname(__DIR__) . '/vendor/autoload.php';
if (! defined('USE_COMPOSER_AUTOLOADER') && file_exists($autoloader)) {
    define('USE_COMPOSER_AUTOLOADER', true);
    require_once $autoloader;
}

if (class_exists('\Roots\Bedrock\Autoloader')) {
    new \Roots\Bedrock\Autoloader();
}
