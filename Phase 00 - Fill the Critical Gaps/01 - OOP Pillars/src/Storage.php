<?php

namespace App;

class Storage
{
    public  static function resolve(): FileStorage {
        $storageMethod = $_ENV['STORAGE_METHOD'];

        if ($storageMethod === 'local') {
            return new LocalStorage();
        } else if ($storageMethod === 's3') {
            $client = new \Aws\S3\S3Client([
                'version' => 'latest',
                'region' => $_ENV['S3_REGION'],
                'credentials' => [
                    'key' => $_ENV['S3_KEY'],
                    'secret' => $_ENV['S3_SECRET'],
                ],
            ]);
            return new S3Storage($client, $_ENV['S3_BUCKET']);
        }
        throw new Exception('Invalid storage method');
    }
}