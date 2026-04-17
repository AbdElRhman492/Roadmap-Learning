<?php

namespace App;

class LocalStorage implements FileStorage
{
    public function put(string $path, string $content)
    {
        $root = __DIR__ . '/../storage';
        $savePath = $root . '/' . $path;
        if (! is_dir(dirname($savePath))) {
            mkdir(dirname($savePath), 0777, true);
        }
        file_put_contents($savePath, $content);
    }
}