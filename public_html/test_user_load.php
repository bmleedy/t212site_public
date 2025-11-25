<?php
// Test file to replicate User.php loading
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "Step 1: Setting up paths...\n";

// Simulate what authHeader does - __ROOT__ should be public_html
define("__ROOT__", __DIR__);
echo "__ROOT__ = " . __ROOT__ . "\n";

echo "Step 2: Loading inc_login.php...\n";
require_once( __ROOT__ . '/login/inc_login.php');

echo "Step 3: Success!\n";
echo "DB_USER constant: " . (defined('DB_USER') ? DB_USER : 'NOT DEFINED') . "\n";


