<?php

declare(strict_types=1);

namespace App\Models;

use App\Domain\Reporting\Enums\ReportFormat;
use App\Domain\Reporting\Enums\ReportScope;
use App\Domain\Reporting\Support\PrintLayoutRegistry;
use App\Domain\Reporting\Support\ReportFieldRegistry;
use Database\Factories\ReportTemplateFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ReportTemplate extends Model
{
    /** @use HasFactory<ReportTemplateFactory> */
    use HasFactory;

    protected $fillable = [
        'layout_key',
        'name',
        'description',
        'scope',
        'format',
        'fields',
        'page_settings',
        'header_zones',
        'footer_zones',
        'toc_configuration',
        'is_active',
        'is_system',
        'created_by',
        'updated_by',
    ];

    protected $attributes = [
        'is_active' => true,
        'is_system' => false,
    ];

    protected function casts(): array
    {
        return [
            'scope' => ReportScope::class,
            'format' => ReportFormat::class,
            'fields' => 'array',
            'page_settings' => 'array',
            'header_zones' => 'array',
            'footer_zones' => 'array',
            'toc_configuration' => 'array',
            'is_active' => 'boolean',
            'is_system' => 'boolean',
        ];
    }

    protected static function booted(): void
    {
        static::saving(function (self $template): void {
            $scope = $template->scope instanceof ReportScope
                ? $template->scope
                : ReportScope::from((string) $template->scope);

            $template->fields = app(ReportFieldRegistry::class)->normalize(
                $scope,
                is_array($template->fields) ? $template->fields : [],
            );

            $layoutRegistry = app(PrintLayoutRegistry::class);
            $template->page_settings = $layoutRegistry->normalizePageSettings(
                is_array($template->page_settings) ? $template->page_settings : [],
            );
            $template->header_zones = $layoutRegistry->normalizeZones(
                is_array($template->header_zones) ? $template->header_zones : [],
            );
            $template->footer_zones = $layoutRegistry->normalizeZones(
                is_array($template->footer_zones) ? $template->footer_zones : [],
                footer: true,
            );
            $template->toc_configuration = $layoutRegistry->normalizeTocConfiguration(
                is_array($template->toc_configuration) ? $template->toc_configuration : [],
            );

            if (auth()->check()) {
                $template->updated_by = auth()->id();
                $template->created_by ??= auth()->id();
            }
        });
    }

    /** @param Builder<ReportTemplate> $query */
    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    /** @return BelongsTo<User, $this> */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /** @return BelongsTo<User, $this> */
    public function updater(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    /** @return list<string> */
    public function enabledFieldKeys(): array
    {
        return collect($this->fields)
            ->filter(fn (array $field): bool => (bool) ($field['enabled'] ?? false))
            ->pluck('key')
            ->filter(fn (mixed $key): bool => is_string($key))
            ->values()
            ->all();
    }

    /** @return array<string, mixed> */
    public function printPageSettings(): array
    {
        return app(PrintLayoutRegistry::class)->normalizePageSettings(
            is_array($this->page_settings) ? $this->page_settings : [],
        );
    }

    /** @return array<string, mixed> */
    public function printHeaderZones(): array
    {
        return app(PrintLayoutRegistry::class)->normalizeZones(
            is_array($this->header_zones) ? $this->header_zones : [],
        );
    }

    /** @return array<string, mixed> */
    public function printFooterZones(): array
    {
        return app(PrintLayoutRegistry::class)->normalizeZones(
            is_array($this->footer_zones) ? $this->footer_zones : [],
            footer: true,
        );
    }

    /** @return array<string, mixed> */
    public function tocConfiguration(): array
    {
        return app(PrintLayoutRegistry::class)->normalizeTocConfiguration(
            is_array($this->toc_configuration) ? $this->toc_configuration : [],
        );
    }
}
