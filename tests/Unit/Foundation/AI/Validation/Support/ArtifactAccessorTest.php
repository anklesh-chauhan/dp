<?php

declare(strict_types=1);

use App\Foundation\AI\Validation\Support\ArtifactAccessor;

it('gets a top level value', function (): void {
    $artifact = [
        'title' => 'SOP',
    ];

    $accessor = new ArtifactAccessor();

    expect($accessor->get($artifact, 'title'))
        ->toBe('SOP');
});

it('gets a nested value', function (): void {
    $artifact = [
        'metadata' => [
            'version' => '1.0',
        ],
    ];

    $accessor = new ArtifactAccessor();

    expect($accessor->get($artifact, 'metadata.version'))
        ->toBe('1.0');
});

it('gets an indexed array value', function (): void {
    $artifact = [
        'sections' => [
            [
                'title' => 'Introduction',
            ],
        ],
    ];

    $accessor = new ArtifactAccessor();

    expect($accessor->get($artifact, 'sections.0.title'))
        ->toBe('Introduction');
});

it('returns the default value when the path does not exist', function (): void {
    $accessor = new ArtifactAccessor();

    expect(
        $accessor->get([], 'missing.path', 'default')
    )->toBe('default');
});

it('returns null when the path does not exist and no default is supplied', function (): void {
    $accessor = new ArtifactAccessor();

    expect(
        $accessor->get([], 'missing.path')
    )->toBeNull();
});

it('determines whether a path exists', function (): void {
    $artifact = [
        'title' => null,
    ];

    $accessor = new ArtifactAccessor();

    expect($accessor->exists($artifact, 'title'))->toBeTrue()
        ->and($accessor->exists($artifact, 'missing'))->toBeFalse();
});

it('distinguishes between existing null values and missing values', function (): void {
    $artifact = [
        'title' => null,
        'version' => '1.0',
    ];

    $accessor = new ArtifactAccessor();

    expect($accessor->has($artifact, 'title'))->toBeFalse()
        ->and($accessor->has($artifact, 'version'))->toBeTrue()
        ->and($accessor->has($artifact, 'missing'))->toBeFalse();
});

it('returns the entire artifact when an empty path is supplied', function (): void {
    $artifact = [
        'title' => 'SOP',
    ];

    $accessor = new ArtifactAccessor();

    expect($accessor->get($artifact, ''))
        ->toBe($artifact);
});

it('returns false for non-array artifacts', function (): void {
    $accessor = new ArtifactAccessor();

    expect($accessor->exists('text', 'title'))->toBeFalse()
        ->and($accessor->has('text', 'title'))->toBeFalse()
        ->and($accessor->get('text', 'title'))->toBeNull();
});
