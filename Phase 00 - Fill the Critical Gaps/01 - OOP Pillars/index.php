<?php

require 'vendor/autoload.php';

use Dotenv\Dotenv;
use App\Storage;

$dotenv = Dotenv::createImmutable(__DIR__);
$dotenv->load();


//$localStorage = new LocalStorage();
//$localStorage->put('test.txt', 'Hello, World!');
//
//echo "File saved successfully.\n";
//
//$s3Storage = new S3Storage($client, $_ENV['S3_BUCKET']);
//$s3Storage->put('test.txt', 'Hello, World!');
//
//echo "File saved successfully.\n";

Storage::resolve()->put('test.txt', 'Hello, World!');
