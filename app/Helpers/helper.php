<?php

if (! function_exists('versioned_asset')) {
    /**
     * URL of a file in public/ with its modification time appended,
     * so browsers re-fetch static assets after a deploy.
     */
    function versioned_asset(string $path): string
    {
        $path = ltrim($path, '/');
        $file = public_path($path);

        return asset($path).(is_file($file) ? '?v='.filemtime($file) : '');
    }
}
