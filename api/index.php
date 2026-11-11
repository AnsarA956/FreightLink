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

// Forward Vercel requests to normal Laravel public/index.php
require __DIR__.'/../public/index.php';
