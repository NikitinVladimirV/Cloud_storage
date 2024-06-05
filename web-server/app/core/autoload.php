<?php

spl_autoload_register(function ($class): ?string {
    $path = str_replace('\\', '/', $class . '.php');
    if (file_exists($path)) {
        require $path;
    }
    return null;
});