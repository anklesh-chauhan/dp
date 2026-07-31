<?php

declare(strict_types=1);

namespace App\Domain\Reporting\Support;

use Illuminate\Support\Arr;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

final class PrintLayoutRegistry
{
    /** @return array<string, string> */
    public function tokenOptions(): array
    {
        return [
            'logo' => 'Organization Logo',
            'organization_name' => 'Organization Name',
            'organization_address' => 'Organization Address',
            'registration_number' => 'Registration / Licence Number',
            'document_title' => 'Document Title',
            'document_number' => 'Document Number',
            'document_version' => 'Version',
            'document_status' => 'Status',
            'department' => 'Department',
            'effective_date' => 'Effective Date',
            'review_date' => 'Review Date',
            'copy_status' => 'Controlled Copy Status',
            'printed_by' => 'Printed By',
            'printed_at' => 'Printed At',
            'template_reference' => 'Template Reference',
            'controlled_notice' => 'Controlled / Uncontrolled Notice',
            'page_number' => 'Page Number',
            'custom_text' => 'Custom Text',
        ];
    }

    /** @return array<string, mixed> */
    public function defaultPageSettings(): array
    {
        return [
            'paper_size' => 'a4',
            'orientation' => 'portrait',
            'font_family' => 'arial',
            'font_size' => 12,
            'primary_color' => '#1f2937',
            'secondary_color' => '#f1f5f9',
            'margin_top_mm' => 15,
            'margin_right_mm' => 15,
            'margin_bottom_mm' => 18,
            'margin_left_mm' => 15,
            'show_table_borders' => true,
        ];
    }

    /** @return array<string, mixed> */
    public function defaultHeaderZones(): array
    {
        return [
            'gap_mm' => 0,
            'show_borders' => true,
            'repeat_every_page' => true,
            'reserved_height_mm' => 32,
            'rows' => [
                $this->row('organization', [
                    $this->column('organization_name', 100, 'center', 'center', [
                        $this->item('organization_name', emphasized: true),
                    ]),
                ]),
                $this->row('document_title', [
                    $this->column('document_title', 100, 'center', 'center', [
                        $this->item('document_title', emphasized: true),
                    ]),
                ]),
                $this->row('document_identity', [
                    $this->column('document_number_label', 22, 'left', 'center', [
                        $this->item('custom_text', customText: 'Document No.', emphasized: true),
                    ]),
                    $this->column('document_number_value', 35, 'left', 'center', [
                        $this->item('document_number', showLabel: false),
                    ]),
                    $this->column('department_label', 18, 'left', 'center', [
                        $this->item('custom_text', customText: 'Department', emphasized: true),
                    ]),
                    $this->column('department_value', 25, 'left', 'center', [
                        $this->item('department', showLabel: false),
                    ]),
                ]),
                $this->row('revision_and_page', [
                    $this->column('revision_label', 22, 'left', 'center', [
                        $this->item('custom_text', customText: 'Revision No.', emphasized: true),
                    ]),
                    $this->column('revision_value', 35, 'left', 'center', [
                        $this->item('document_version', showLabel: false),
                    ]),
                    $this->column('page_label', 18, 'left', 'center', [
                        $this->item('custom_text', customText: 'Page No.', emphasized: true),
                    ]),
                    $this->column('page_value', 25, 'left', 'center', [
                        $this->item('page_number', showLabel: false),
                    ]),
                ]),
            ],
        ];
    }

    /** @return array<string, mixed> */
    public function defaultFooterZones(): array
    {
        return [
            'gap_mm' => 0,
            'show_borders' => true,
            'columns' => [
                $this->column('footer_left', 35, 'left', 'center', [
                    ['token' => 'printed_by', 'label' => 'Printed By', 'custom_text' => null],
                    ['token' => 'printed_at', 'label' => 'Printed At', 'custom_text' => null],
                ]),
                $this->column('footer_center', 40, 'center', 'center', [
                    ['token' => 'controlled_notice', 'label' => 'Controlled / Uncontrolled Notice', 'custom_text' => null],
                ]),
                $this->column('footer_right', 25, 'right', 'center', [
                    ['token' => 'document_number', 'label' => 'Document Number', 'custom_text' => null],
                    ['token' => 'page_number', 'label' => 'Page Number', 'custom_text' => null],
                ]),
            ],
        ];
    }

    /** @param array<string, mixed> $settings
     * @return array<string, mixed>
     */
    public function normalizePageSettings(array $settings): array
    {
        $defaults = $this->defaultPageSettings();
        $settings = [...$defaults, ...Arr::only($settings, array_keys($defaults))];
        $settings['paper_size'] = $this->allowed($settings['paper_size'], ['a4', 'letter'], 'paper size');
        $settings['orientation'] = $this->allowed($settings['orientation'], ['portrait', 'landscape'], 'orientation');
        $settings['font_family'] = $this->allowed($settings['font_family'], ['arial', 'times', 'georgia'], 'font family');
        $settings['font_size'] = $this->boundedInteger($settings['font_size'], 9, 16, 'font size');
        $settings['primary_color'] = $this->hexColor($settings['primary_color'], 'primary color');
        $settings['secondary_color'] = $this->hexColor($settings['secondary_color'], 'secondary color');
        $settings['show_table_borders'] = (bool) $settings['show_table_borders'];

        foreach (['margin_top_mm', 'margin_right_mm', 'margin_bottom_mm', 'margin_left_mm'] as $margin) {
            $settings[$margin] = $this->boundedInteger($settings[$margin], 5, 35, str_replace('_', ' ', $margin));
        }

        return $settings;
    }

    /**
     * @param  array<string, mixed>  $zones
     * @return array{gap_mm: int, show_borders: bool, columns: list<array{key: string, width: int, alignment: string, vertical_alignment: string, items: list<array{token: string, label: string, custom_text: string|null}>}>}
     */
    public function normalizeZones(array $zones, bool $footer = false): array
    {
        $defaults = $footer ? $this->defaultFooterZones() : $this->defaultHeaderZones();
        $options = $this->tokenOptions();

        if (! $footer) {
            if (isset($zones['columns']) && ! isset($zones['rows'])) {
                $zones['rows'] = [[
                    'key' => 'legacy_header',
                    'cells' => $zones['columns'],
                ]];
            } elseif (array_intersect(['left', 'center', 'right'], array_keys($zones)) && ! isset($zones['rows'])) {
                $zones['rows'] = [[
                    'key' => 'legacy_header',
                    'cells' => [
                        $this->column('legacy_left', 33, 'left', 'center', Arr::get($zones, 'left', [])),
                        $this->column('legacy_center', 34, 'center', 'center', Arr::get($zones, 'center', [])),
                        $this->column('legacy_right', 33, 'right', 'center', Arr::get($zones, 'right', [])),
                    ],
                ]];
            } elseif (! isset($zones['rows'])) {
                $zones = $defaults;
            }

            return $this->normalizeTableRows($zones, $options);
        }

        $zones = $this->convertLegacyZones($zones, $defaults);
        $columns = Arr::get($zones, 'columns');

        if (! is_array($columns) || count($columns) < 1 || count($columns) > 4) {
            throw ValidationException::withMessages(['columns' => 'Header and footer layouts require between one and four columns.']);
        }

        $normalizedColumns = collect($columns)
            ->map(function (mixed $column, int $index) use ($options): array {
                if (! is_array($column)) {
                    throw ValidationException::withMessages(['columns' => 'Every layout column must have valid configuration.']);
                }

                $items = Arr::get($column, 'items', []);

                if (! is_array($items) || count($items) > 8) {
                    throw ValidationException::withMessages(['columns' => 'Each column may contain up to eight approved items.']);
                }

                return $this->column(
                    key: Str::slug((string) Arr::get($column, 'key', 'column_'.($index + 1)), '_'),
                    width: $this->boundedInteger(Arr::get($column, 'width'), 10, 100, 'column width'),
                    alignment: $this->allowed(Arr::get($column, 'alignment'), ['left', 'center', 'right'], 'column alignment'),
                    verticalAlignment: $this->allowed(Arr::get($column, 'vertical_alignment'), ['top', 'center', 'bottom'], 'vertical alignment'),
                    items: collect($items)->map(function (mixed $item) use ($options): array {
                        if (! is_array($item) || ! isset($options[$item['token'] ?? ''])) {
                            throw ValidationException::withMessages(['columns' => 'The layout contains an unsupported token.']);
                        }

                        $token = (string) $item['token'];

                        return [
                            'token' => $token,
                            'label' => $options[$token],
                            'custom_text' => $token === 'custom_text'
                                ? Str::limit(Str::squish((string) ($item['custom_text'] ?? '')), 200, '')
                                : null,
                            'show_label' => (bool) ($item['show_label'] ?? true),
                            'emphasized' => (bool) ($item['emphasized'] ?? false),
                        ];
                    })->values()->all(),
                );
            })
            ->values();

        if ($normalizedColumns->pluck('key')->duplicates()->isNotEmpty()) {
            throw ValidationException::withMessages(['columns' => 'Every layout column must have a unique key.']);
        }

        if ($normalizedColumns->sum('width') !== 100) {
            throw ValidationException::withMessages(['columns' => 'Header and footer column widths must total exactly 100%.']);
        }

        return [
            'gap_mm' => $this->boundedInteger(Arr::get($zones, 'gap_mm', 0), 0, 10, 'column gap'),
            'show_borders' => (bool) Arr::get($zones, 'show_borders', true),
            'columns' => $normalizedColumns->all(),
        ];
    }

    /**
     * @param  array<string, mixed>  $zones
     * @param  array<string, string>  $options
     * @return array{gap_mm: int, show_borders: bool, repeat_every_page: bool, reserved_height_mm: int, rows: list<array{key: string, cells: list<array<string, mixed>>}>}
     */
    private function normalizeTableRows(array $zones, array $options): array
    {
        $rows = Arr::get($zones, 'rows');

        if (! is_array($rows) || count($rows) < 1 || count($rows) > 8) {
            throw ValidationException::withMessages(['rows' => 'Header tables require between one and eight rows.']);
        }

        $normalizedRows = collect($rows)->map(function (mixed $row, int $rowIndex) use ($options): array {
            if (! is_array($row)) {
                throw ValidationException::withMessages(['rows' => 'Every header row must have valid configuration.']);
            }

            $cells = Arr::get($row, 'cells');

            if (! is_array($cells) || count($cells) < 1 || count($cells) > 6) {
                throw ValidationException::withMessages(['rows' => 'Each header row requires between one and six cells.']);
            }

            $normalizedCells = collect($cells)->map(function (mixed $cell, int $cellIndex) use ($options): array {
                if (! is_array($cell)) {
                    throw ValidationException::withMessages(['rows' => 'Every header cell must have valid configuration.']);
                }

                $items = Arr::get($cell, 'items', []);

                if (! is_array($items) || count($items) > 8) {
                    throw ValidationException::withMessages(['rows' => 'Each header cell may contain up to eight approved items.']);
                }

                return $this->column(
                    key: Str::slug((string) Arr::get($cell, 'key', 'cell_'.($cellIndex + 1)), '_'),
                    width: $this->boundedInteger(Arr::get($cell, 'width'), 10, 100, 'cell width'),
                    alignment: $this->allowed(Arr::get($cell, 'alignment'), ['left', 'center', 'right'], 'cell alignment'),
                    verticalAlignment: $this->allowed(Arr::get($cell, 'vertical_alignment'), ['top', 'center', 'bottom'], 'vertical alignment'),
                    items: collect($items)->map(function (mixed $item) use ($options): array {
                        if (! is_array($item) || ! isset($options[$item['token'] ?? ''])) {
                            throw ValidationException::withMessages(['rows' => 'The header table contains an unsupported token.']);
                        }

                        $token = (string) $item['token'];

                        return [
                            'token' => $token,
                            'label' => $options[$token],
                            'custom_text' => $token === 'custom_text'
                                ? Str::limit(Str::squish((string) ($item['custom_text'] ?? '')), 200, '')
                                : null,
                            'show_label' => (bool) ($item['show_label'] ?? true),
                            'emphasized' => (bool) ($item['emphasized'] ?? false),
                        ];
                    })->values()->all(),
                );
            })->values();

            if ($normalizedCells->sum('width') !== 100) {
                throw ValidationException::withMessages(['rows' => 'Cell widths in every header row must total exactly 100%.']);
            }

            return [
                'key' => Str::slug((string) Arr::get($row, 'key', 'row_'.($rowIndex + 1)), '_'),
                'cells' => $normalizedCells->all(),
            ];
        })->values();

        if ($normalizedRows->pluck('key')->duplicates()->isNotEmpty()) {
            throw ValidationException::withMessages(['rows' => 'Every header row must have a unique key.']);
        }

        return [
            'gap_mm' => $this->boundedInteger(Arr::get($zones, 'gap_mm', 0), 0, 10, 'cell gap'),
            'show_borders' => (bool) Arr::get($zones, 'show_borders', true),
            'repeat_every_page' => (bool) Arr::get($zones, 'repeat_every_page', true),
            'reserved_height_mm' => $this->boundedInteger(Arr::get($zones, 'reserved_height_mm', 32), 15, 60, 'reserved header height'),
            'rows' => $normalizedRows->all(),
        ];
    }

    /** @param list<array{token: string, label: string, custom_text: string|null}> $items
     * @return array{key: string, width: int, alignment: string, vertical_alignment: string, items: array}
     */
    private function column(
        string $key,
        int $width,
        string $alignment,
        string $verticalAlignment,
        array $items,
    ): array {
        return [
            'key' => $key,
            'width' => $width,
            'alignment' => $alignment,
            'vertical_alignment' => $verticalAlignment,
            'items' => $items,
        ];
    }

    /** @param list<array<string, mixed>> $cells
     * @return array{key: string, cells: array}
     */
    private function row(string $key, array $cells): array
    {
        return ['key' => $key, 'cells' => $cells];
    }

    /** @return array{token: string, label: string, custom_text: string|null, show_label: bool, emphasized: bool} */
    private function item(string $token, ?string $customText = null, bool $showLabel = true, bool $emphasized = false): array
    {
        return [
            'token' => $token,
            'label' => $this->tokenOptions()[$token],
            'custom_text' => $customText,
            'show_label' => $showLabel,
            'emphasized' => $emphasized,
        ];
    }

    /** @param array<string, mixed> $zones
     * @param  array<string, mixed>  $defaults
     * @return array<string, mixed>
     */
    private function convertLegacyZones(array $zones, array $defaults): array
    {
        if (isset($zones['columns'])) {
            return $zones;
        }

        if (isset($defaults['rows'])) {
            return $defaults;
        }

        if (! array_intersect(['left', 'center', 'right'], array_keys($zones))) {
            return $defaults;
        }

        return [
            'gap_mm' => 0,
            'show_borders' => true,
            'columns' => [
                $this->column('legacy_left', 33, 'left', 'center', Arr::get($zones, 'left', [])),
                $this->column('legacy_center', 34, 'center', 'center', Arr::get($zones, 'center', [])),
                $this->column('legacy_right', 33, 'right', 'center', Arr::get($zones, 'right', [])),
            ],
        ];
    }

    private function allowed(mixed $value, array $allowed, string $attribute): string
    {
        if (! is_string($value) || ! in_array($value, $allowed, true)) {
            throw ValidationException::withMessages([$attribute => "The selected {$attribute} is invalid."]);
        }

        return $value;
    }

    private function boundedInteger(mixed $value, int $minimum, int $maximum, string $attribute): int
    {
        $value = filter_var($value, FILTER_VALIDATE_INT);

        if ($value === false || $value < $minimum || $value > $maximum) {
            throw ValidationException::withMessages([$attribute => "The {$attribute} must be between {$minimum} and {$maximum}."]);
        }

        return $value;
    }

    private function hexColor(mixed $value, string $attribute): string
    {
        if (! is_string($value) || preg_match('/^#[0-9a-fA-F]{6}$/', $value) !== 1) {
            throw ValidationException::withMessages([$attribute => "The {$attribute} must be a six-digit hex color."]);
        }

        return Str::lower($value);
    }
}
