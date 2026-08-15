<?php

namespace Osoobe\Quiz\Support;

class ViteManifest
{
    /**
     * Published assets (public/vendor/quiz) win when present — a perf opt-in
     * (real static files, cacheable by a CDN) that never requires re-publishing
     * to stay correct, since the package route always falls back to whatever the
     * currently-installed package version ships.
     */
    public static function root(): string
    {
        $published = public_path('vendor/quiz');

        if (is_file($published.'/manifest.json') || is_file($published.'/.vite/manifest.json')) {
            return $published;
        }

        return dirname(__DIR__, 2).'/resources/dist';
    }

    /**
     * @return array{js: ?string, css: array<int, string>}
     */
    public static function entry(string $entry = 'resources/js-src/main.tsx'): array
    {
        $root = self::root();
        $manifestPath = is_file($root.'/.vite/manifest.json') ? $root.'/.vite/manifest.json' : $root.'/manifest.json';

        if (! is_file($manifestPath)) {
            return ['js' => null, 'css' => []];
        }

        $manifest = json_decode(file_get_contents($manifestPath), true) ?? [];
        $chunk = $manifest[$entry] ?? (reset($manifest) ?: []);

        return [
            'js' => $chunk['file'] ?? null,
            'css' => $chunk['css'] ?? [],
        ];
    }
}
