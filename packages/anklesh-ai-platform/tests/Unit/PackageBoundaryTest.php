<?php

declare(strict_types=1);

use Illuminate\Support\Str;

it('declares the expected Composer identity and Laravel discovery provider', function () {
    $manifest = json_decode(
        file_get_contents(dirname(__DIR__, 2).'/composer.json'),
        true,
        flags: JSON_THROW_ON_ERROR,
    );

    expect($manifest['name'])->toBe('anklesh/ai-platform')
        ->and($manifest['type'])->toBe('library')
        ->and($manifest['extra']['laravel']['providers'])->toContain(
            'Anklesh\\AiPlatform\\AiPlatformServiceProvider',
        );
});

it('does not depend on the host applications namespace', function () {
    $sourceDirectory = dirname(__DIR__, 2).'/src';
    $iterator = new RecursiveIteratorIterator(
        new RecursiveDirectoryIterator($sourceDirectory),
    );

    foreach ($iterator as $file) {
        if (! $file->isFile() || $file->getExtension() !== 'php') {
            continue;
        }

        $contents = file_get_contents($file->getPathname());

        expect(Str::contains($contents, ['namespace App\\', 'use App\\']))
            ->toBeFalse(sprintf('Host application dependency found in [%s].', $file->getPathname()));
    }
});
