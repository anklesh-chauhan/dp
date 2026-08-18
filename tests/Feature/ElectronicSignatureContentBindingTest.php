<?php

declare(strict_types=1);

use App\Domain\Shared\Contracts\ElectronicSignatureHasher;
use App\Domain\Shared\Contracts\ElectronicSignatureRecord;
use App\Domain\Shared\Contracts\HasSignatureContentDigest;
use App\Domain\Shared\Services\CanonicalElectronicSignatureVerifier;
use App\Domain\Shared\Services\ElectronicSignatureContentDigester;
use App\Models\ControlledDocument;
use App\Models\ControlledDocumentSection;
use App\Models\DocumentStatus;
use App\Models\DocumentTemplate;
use App\Models\DocumentTemplateVersion;
use App\Models\TemplateStatus;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('fails signature verification when approved document content changes', function (): void {
    DocumentStatus::query()->create([
        'code' => DocumentStatus::DRAFT,
        'name' => 'Draft',
    ]);
    TemplateStatus::query()->create([
        'code' => TemplateStatus::DRAFT,
        'name' => 'Draft',
    ]);

    $user = User::factory()->create();
    $template = DocumentTemplate::factory()->create();
    $templateVersion = DocumentTemplateVersion::factory()->create([
        'document_template_id' => $template->id,
    ]);
    $document = ControlledDocument::factory()->create([
        'template_id' => $template->id,
        'template_version_id' => $templateVersion->id,
        'title' => 'Content-bound SOP',
        'created_by' => $user->id,
        'owner_id' => $user->id,
    ]);
    $section = ControlledDocumentSection::factory()->create([
        'document_id' => $document->id,
        'title' => 'Procedure',
        'section_order' => 1,
        'content' => '<p>Approved procedure text.</p>',
    ]);

    $digester = app(ElectronicSignatureContentDigester::class);
    $hasher = app(ElectronicSignatureHasher::class);
    $signedAt = now();
    $contentDigest = $digester->digest($document->fresh()->electronicSignatureContentPayload());
    $storedHash = $hasher->hashFor(
        recordKey: $document->getKey(),
        meaning: 'approved',
        signerId: $user->id,
        signedAt: $signedAt,
        reason: 'Content reviewed and approved.',
        ipAddress: '203.0.113.25',
        userAgent: 'QualiGxP-Test/1.0',
        contentDigest: $contentDigest,
    );

    $signature = contentBoundSignature($document, $user->id, $signedAt, $storedHash, $digester);
    $verifier = new CanonicalElectronicSignatureVerifier($hasher);

    expect($verifier->isValid($signature))->toBeTrue();

    $section->update(['content' => '<p>Unapproved post-signature edit.</p>']);

    expect($verifier->isValid($signature))->toBeFalse()
        ->and($digester->digest($document->fresh()->electronicSignatureContentPayload()))
        ->not->toBe($contentDigest);
});

function contentBoundSignature(
    ControlledDocument $document,
    int $signerId,
    DateTimeInterface $signedAt,
    string $storedHash,
    ElectronicSignatureContentDigester $digester,
): ElectronicSignatureRecord {
    return new class($document, $signerId, $signedAt, $storedHash, $digester) implements ElectronicSignatureRecord, HasSignatureContentDigest
    {
        public function __construct(
            private readonly ControlledDocument $document,
            private readonly int $signerId,
            private readonly DateTimeInterface $signedAt,
            private readonly string $storedHash,
            private readonly ElectronicSignatureContentDigester $digester,
        ) {}

        public function signatureRecordKey(): int|string|null
        {
            return $this->document->getKey();
        }

        public function signatureMeaning(): ?string
        {
            return 'approved';
        }

        public function signatureSignerId(): int|string|null
        {
            return $this->signerId;
        }

        public function signatureTimestamp(): ?DateTimeInterface
        {
            return $this->signedAt;
        }

        public function signatureHash(): ?string
        {
            return $this->storedHash;
        }

        public function signatureReason(): ?string
        {
            return 'Content reviewed and approved.';
        }

        public function signatureIpAddress(): ?string
        {
            return '203.0.113.25';
        }

        public function signatureUserAgent(): ?string
        {
            return 'QualiGxP-Test/1.0';
        }

        public function signatureContentDigest(): ?string
        {
            return $this->digester->digest(
                $this->document->fresh()->electronicSignatureContentPayload(),
            );
        }
    };
}
