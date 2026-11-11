<?php

// Ensure writable storage subdirectories exist in /tmp for serverless execution
$storageDirs = [
    '/tmp/storage/framework/views',
    '/tmp/storage/framework/cache',
    '/tmp/storage/framework/sessions',
    '/tmp/storage/logs',
];

foreach ($storageDirs as $dir) {
    if (! is_dir($dir)) {
        @mkdir($dir, 0755, true);
    }
}

// Ensure SQLite database exists in writable /tmp
$tmpDb = '/tmp/database.sqlite';
if (! file_exists($tmpDb)) {
    $seededDb = __DIR__.'/../database/database.sqlite';
    if (file_exists($seededDb)) {
        @copy($seededDb, $tmpDb);
    } else {
        @touch($tmpDb);
    }
}

// Forward Vercel requests to normal Laravel public/index.php
require __DIR__.'/../public/index.php';
