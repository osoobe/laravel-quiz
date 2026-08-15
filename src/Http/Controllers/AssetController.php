<?php

namespace Osoobe\Quiz\Http\Controllers;

use Illuminate\Http\Response;
use Osoobe\Quiz\Support\ViteManifest;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class AssetController
{
    // Symfony's content-based mime guesser frequently misreads plain-text JS/CSS as
    // text/plain, which browsers then refuse to execute as a module script — set the
    // type explicitly from the extension instead of trusting auto-detection.
    private const MIME_TYPES = [
        'js' => 'text/javascript',
        'mjs' => 'text/javascript',
        'css' => 'text/css',
        'json' => 'application/json',
        'svg' => 'image/svg+xml',
        'woff' => 'font/woff',
        'woff2' => 'font/woff2',
        'png' => 'image/png',
        'jpg' => 'image/jpeg',
        'jpeg' => 'image/jpeg',
        'webp' => 'image/webp',
    ];

    public function __invoke(string $path): BinaryFileResponse|Response
    {
        $root = realpath(ViteManifest::root());
        $file = $root ? realpath($root.'/'.$path) : false;

        // Guard against path traversal escaping the resolved asset root.
        if (! $root || ! $file || ! str_starts_with($file, $root) || ! is_file($file)) {
            abort(404);
        }

        $extension = strtolower(pathinfo($file, PATHINFO_EXTENSION));

        return response()->file($file, [
            'Content-Type' => self::MIME_TYPES[$extension] ?? 'application/octet-stream',
            // Filenames are content-hashed by Vite, so a far-future cache is safe.
            'Cache-Control' => 'public, max-age=31536000, immutable',
        ]);
    }
}
