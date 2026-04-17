<?php

namespace App;
use Aws\S3\S3Client;

class S3Storage implements FileStorage
{
    public function __construct(protected S3Client $s3Client, protected string $bucket)
    {
    }

    public function put(string $path, string $content)
    {
        try {
            $this->s3Client->putObject([
                'Bucket' => $this->bucket,
                'Key' => $path,
                'Body' => $content,
            ]);
            echo "File uploaded successfully to S3.\n";
        } catch (Aws\S3\Exception\S3Exception $e) {
            echo "Error uploading file to S3: " . $e->getMessage() . "\n";
        }
    }
}