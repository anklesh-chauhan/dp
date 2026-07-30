<?php

declare(strict_types=1);

namespace App\Models;

use Database\Factories\OrganizationFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Validation\ValidationException;

final class Organization extends Model
{
    /** @use HasFactory<OrganizationFactory> */
    use HasFactory;

    protected $fillable = [
        'code', 'legal_name', 'display_name', 'registration_number', 'tax_identifier',
        'regulatory_identifiers', 'address_line_1', 'address_line_2', 'city', 'state',
        'postal_code', 'country_code', 'phone', 'email', 'website', 'logo_path',
        'document_header', 'document_footer', 'timezone', 'is_active', 'is_default',
        'created_by',
    ];

    protected static function booted(): void
    {
        self::creating(function (self $organization): void {
            if (self::query()->exists()) {
                throw ValidationException::withMessages([
                    'organization' => 'This deployment already has an Organization Profile.',
                ]);
            }

            $organization->is_active = true;
            $organization->is_default = true;
        });
        self::saving(function (self $organization): void {
            $organization->code = str($organization->code)->trim()->upper()->toString();
            $organization->country_code = str($organization->country_code)->trim()->upper()->toString();
            $organization->is_active = true;
            $organization->is_default = true;
            $organization->default_key = 'default';
        });
    }

    protected function casts(): array
    {
        return [
            'regulatory_identifiers' => 'array',
            'is_active' => 'boolean',
            'is_default' => 'boolean',
        ];
    }

    /** @return array<string, mixed> */
    public function identitySnapshot(): array
    {
        return $this->only([
            'id', 'code', 'legal_name', 'display_name', 'registration_number',
            'tax_identifier', 'regulatory_identifiers', 'address_line_1',
            'address_line_2', 'city', 'state', 'postal_code', 'country_code',
            'phone', 'email', 'website', 'logo_path', 'document_header',
            'document_footer', 'timezone',
        ]);
    }

    /** @return array{organization: array<string, mixed>} */
    public function templateVariables(): array
    {
        return ['organization' => $this->identitySnapshot()];
    }

    public static function defaultActive(): ?self
    {
        return self::query()
            ->where('is_active', true)
            ->where('is_default', true)
            ->first();
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function controlledDocuments(): HasMany
    {
        return $this->hasMany(ControlledDocument::class);
    }
}
