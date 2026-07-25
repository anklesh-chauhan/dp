<?php

declare(strict_types=1);

use App\Domain\Shared\Contracts\ElectronicSignatureMetadata;
use App\Models\ApprovalDecision;
use App\Models\SopApproval;

it('adapts existing SOP approval signature data to Shared electronic signature metadata', function (): void {
    $signedAtTimestamp = strtotime('2026-07-25 10:30:00 UTC');
    $decision = new ApprovalDecision([
        'code' => ApprovalDecision::APPROVED,
        'name' => 'Approved',
    ]);

    $approval = new SopApproval([
        'approved_by' => 21,
        'comments' => 'I approve this controlled document.',
        'signature_hash' => 'signature-hash',
        'signature_ip_address' => '203.0.113.42',
        'signature_user_agent' => 'DocuPharma Contract Test',
    ]);
    $approval->setRawAttributes([
        ...$approval->getAttributes(),
        'approved_at' => $signedAtTimestamp,
    ], true);
    $approval->setRelation('approvalDecision', $decision);

    expect($approval)
        ->toBeInstanceOf(ElectronicSignatureMetadata::class)
        ->and($approval->signatureMeaning())->toBe(ApprovalDecision::APPROVED)
        ->and($approval->signatureSignerId())->toBe(21)
        ->and($approval->signatureTimestamp()?->getTimestamp())->toBe($signedAtTimestamp)
        ->and($approval->signatureHash())->toBe('signature-hash')
        ->and($approval->signatureReason())->toBe('I approve this controlled document.')
        ->and($approval->signatureIpAddress())->toBe('203.0.113.42')
        ->and($approval->signatureUserAgent())->toBe('DocuPharma Contract Test');
});

it('exposes unsigned SOP approvals as empty electronic signature metadata', function (): void {
    $approval = new SopApproval;
    $approval->setRelation('approvalDecision', null);

    expect($approval->signatureMeaning())->toBeNull()
        ->and($approval->signatureSignerId())->toBeNull()
        ->and($approval->signatureTimestamp())->toBeNull()
        ->and($approval->signatureHash())->toBeNull()
        ->and($approval->signatureReason())->toBeNull()
        ->and($approval->signatureIpAddress())->toBeNull()
        ->and($approval->signatureUserAgent())->toBeNull();
});

arch('Shared electronic signature metadata is a module-neutral contract')
    ->expect(ElectronicSignatureMetadata::class)
    ->toBeInterface()
    ->not->toUse([
        'App\Domain\AI',
        'App\Domain\DMS',
        'App\Domain\QMS',
        'App\Foundation\AI',
        'App\Services\AI',
        'App\Services\Sop',
    ]);
