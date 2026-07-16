<?php

declare(strict_types=1);

use App\Foundation\AI\Validation\ValueObjects\RepairResult;

it('stores the repaired artifact', function (): void {
    $artifact = ['name' => 'John'];

    $result = new RepairResult(
        artifact: $artifact,
        successful: true,
        modified: true,
    );

    expect($result->artifact())->toBe($artifact);
});

it('reports a successful repair', function (): void {
    $result = new RepairResult(
        artifact: [],
        successful: true,
        modified: false,
    );

    expect($result->isSuccessful())->toBeTrue();
});

it('reports an unsuccessful repair', function (): void {
    $result = new RepairResult(
        artifact: [],
        successful: false,
        modified: false,
    );

    expect($result->isSuccessful())->toBeFalse();
});

it('reports whether the artifact was modified', function (): void {
    $result = new RepairResult(
        artifact: [],
        successful: true,
        modified: true,
    );

    expect($result->wasModified())->toBeTrue();
});

it('returns metadata', function (): void {
    $metadata = [
        'strategy' => 'JsonRepairStrategy',
    ];

    $result = new RepairResult(
        artifact: [],
        successful: true,
        modified: false,
        metadata: $metadata,
    );

    expect($result->metadata())
        ->toBe($metadata);
});

it('defaults metadata to an empty array', function (): void {
    $result = new RepairResult(
        artifact: [],
        successful: true,
        modified: false,
    );

    expect($result->metadata())
        ->toBe([]);
});
