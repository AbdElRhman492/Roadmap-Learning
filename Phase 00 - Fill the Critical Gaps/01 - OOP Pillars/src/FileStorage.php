<?php

namespace App;

interface FileStorage
{
    public function put(string $path, string $contents);
}