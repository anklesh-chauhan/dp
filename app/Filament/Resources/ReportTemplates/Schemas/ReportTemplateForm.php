<?php

declare(strict_types=1);

namespace App\Filament\Resources\ReportTemplates\Schemas;

use App\Domain\Reporting\Enums\ReportFormat;
use App\Domain\Reporting\Enums\ReportScope;
use App\Domain\Reporting\Support\PrintLayoutRegistry;
use App\Domain\Reporting\Support\ReportFieldRegistry;
use Closure;
use Filament\Forms\Components\ColorPicker;
use Filament\Forms\Components\Hidden;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Tabs;
use Filament\Schemas\Components\Tabs\Tab;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Schemas\Schema;
use Illuminate\Support\Str;

final class ReportTemplateForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Template Identity')
                    ->schema([
                        Grid::make(2)->schema([
                            TextInput::make('name')
                                ->required()
                                ->maxLength(255),
                            TextInput::make('layout_key')
                                ->label('Layout Key')
                                ->required()
                                ->alphaDash()
                                ->unique(ignoreRecord: true)
                                ->maxLength(100),
                            Select::make('scope')
                                ->options(collect(ReportScope::cases())->mapWithKeys(
                                    fn (ReportScope $scope): array => [$scope->value => $scope->label()],
                                ))
                                ->required()
                                ->live()
                                ->afterStateUpdated(fn (Set $set, string $state): mixed => $set(
                                    'fields',
                                    app(ReportFieldRegistry::class)->defaultFields(ReportScope::from($state)),
                                )),
                            Select::make('format')
                                ->options(collect(ReportFormat::cases())->mapWithKeys(
                                    fn (ReportFormat $format): array => [$format->value => $format->label()],
                                ))
                                ->required(),
                            Toggle::make('is_active')
                                ->label('Available for Selection')
                                ->default(true),
                            Toggle::make('is_system')
                                ->label('System Standard')
                                ->helperText('System standards are seeded GMP/ALCOA+ reference layouts.'),
                        ]),
                        Textarea::make('description')
                            ->rows(3)
                            ->maxLength(2_000)
                            ->columnSpanFull(),
                    ])
                    ->columnSpanFull(),
                Tabs::make('Print Layout')
                    ->tabs([
                        Tab::make('Page & Style')
                            ->schema([
                                Grid::make(3)->schema([
                                    Select::make('page_settings.paper_size')
                                        ->label('Paper Size')
                                        ->options(['a4' => 'A4', 'letter' => 'Letter'])
                                        ->default('a4')
                                        ->required(),
                                    Select::make('page_settings.orientation')
                                        ->options(['portrait' => 'Portrait', 'landscape' => 'Landscape'])
                                        ->default('portrait')
                                        ->required(),
                                    Select::make('page_settings.font_family')
                                        ->label('Font')
                                        ->options(['arial' => 'Arial', 'times' => 'Times New Roman', 'georgia' => 'Georgia'])
                                        ->default('arial')
                                        ->required(),
                                    TextInput::make('page_settings.font_size')
                                        ->label('Base Font Size')
                                        ->numeric()
                                        ->minValue(9)
                                        ->maxValue(16)
                                        ->default(12)
                                        ->suffix('pt')
                                        ->required(),
                                    ColorPicker::make('page_settings.primary_color')
                                        ->label('Primary Color')
                                        ->default('#1f2937')
                                        ->required(),
                                    ColorPicker::make('page_settings.secondary_color')
                                        ->label('Table Header Color')
                                        ->default('#f1f5f9')
                                        ->required(),
                                ]),
                                Grid::make(5)->schema([
                                    ...collect(['top', 'right', 'bottom', 'left'])
                                        ->map(fn (string $side): TextInput => TextInput::make("page_settings.margin_{$side}_mm")
                                            ->label(str($side)->title().' Margin')
                                            ->numeric()
                                            ->minValue(5)
                                            ->maxValue(35)
                                            ->default($side === 'bottom' ? 18 : 15)
                                            ->suffix('mm')
                                            ->required())
                                        ->all(),
                                ]),
                                Toggle::make('page_settings.show_table_borders')
                                    ->label('Show Table Borders')
                                    ->default(true),
                            ]),
                        Tab::make('Header')
                            ->schema([
                                Grid::make(4)->schema([
                                    TextInput::make('header_zones.gap_mm')
                                        ->label('Cell Gap')
                                        ->numeric()
                                        ->minValue(0)
                                        ->maxValue(10)
                                        ->default(0)
                                        ->suffix('mm')
                                        ->required(),
                                    Toggle::make('header_zones.show_borders')
                                        ->label('Show Table Borders')
                                        ->default(true),
                                    Toggle::make('header_zones.repeat_every_page')
                                        ->label('Repeat on Every Page')
                                        ->helperText('Repeats this header at the top of every printed or PDF page.')
                                        ->default(true)
                                        ->live(),
                                    TextInput::make('header_zones.content_gap_mm')
                                        ->label('Gap After Header')
                                        ->helperText('Blank space between the repeated header and page content.')
                                        ->numeric()
                                        ->minValue(0)
                                        ->maxValue(20)
                                        ->default(5)
                                        ->suffix('mm')
                                        ->visible(fn (Get $get): bool => (bool) $get('header_zones.repeat_every_page'))
                                        ->required(fn (Get $get): bool => (bool) $get('header_zones.repeat_every_page')),
                                ]),
                                self::rowRepeater(),
                            ]),
                        Tab::make('Body Blocks')
                            ->schema([
                                Repeater::make('fields')
                                    ->label('')
                                    ->schema([
                                        Hidden::make('key')->required(),
                                        Hidden::make('group'),
                                        TextInput::make('label')
                                            ->label('Print Label')
                                            ->required()
                                            ->maxLength(80),
                                        Select::make('width')
                                            ->options([
                                                'full' => 'Full Width (spans 2 columns)',
                                                'half' => 'Half Width (1 column)',
                                            ])
                                            ->helperText('Use Full Width for long content such as the document title.')
                                            ->default('full')
                                            ->required(),
                                        Toggle::make('enabled')
                                            ->label('Include')
                                            ->default(true),
                                        Toggle::make('hide_when_empty')
                                            ->label('Hide When Empty'),
                                        Toggle::make('page_break_before')
                                            ->label('Page Break Before'),
                                    ])
                                    ->columns(3)
                                    ->addable(false)
                                    ->deletable(false)
                                    ->reorderable()
                                    ->reorderableWithButtons()
                                    ->itemLabel(fn (array $state): string => ($state['group'] ?? 'Field').' · '.($state['label'] ?? $state['key'] ?? ''))
                                    ->required(),
                            ]),
                        Tab::make('Footer')
                            ->schema([
                                Grid::make(4)->schema([
                                    TextInput::make('footer_zones.gap_mm')
                                        ->label('Column Gap')
                                        ->numeric()
                                        ->minValue(0)
                                        ->maxValue(10)
                                        ->default(0)
                                        ->suffix('mm')
                                        ->required(),
                                    Toggle::make('footer_zones.show_borders')
                                        ->label('Show Column Borders')
                                        ->default(true),
                                    Toggle::make('footer_zones.repeat_every_page')
                                        ->label('Repeat on Every Page')
                                        ->helperText('Repeats this footer at the bottom of every printed or PDF page.')
                                        ->default(true)
                                        ->live(),
                                    TextInput::make('footer_zones.content_gap_mm')
                                        ->label('Gap Before Footer')
                                        ->helperText('Blank space between page content and the repeated footer.')
                                        ->numeric()
                                        ->minValue(0)
                                        ->maxValue(20)
                                        ->default(5)
                                        ->suffix('mm')
                                        ->visible(fn (Get $get): bool => (bool) $get('footer_zones.repeat_every_page'))
                                        ->required(fn (Get $get): bool => (bool) $get('footer_zones.repeat_every_page')),
                                ]),
                                self::columnRepeater('footer_zones.columns', footer: true),
                            ]),
                    ])
                    ->columnSpanFull(),
            ]);
    }

    private static function columnRepeater(string $name, bool $footer): Repeater
    {
        $defaults = $footer
            ? app(PrintLayoutRegistry::class)->defaultFooterZones()
            : app(PrintLayoutRegistry::class)->defaultHeaderZones();

        return Repeater::make($name)
            ->label('Columns')
            ->schema([
                Hidden::make('key')
                    ->default(fn (): string => 'column_'.Str::lower(Str::random(8)))
                    ->required(),
                TextInput::make('width')
                    ->label('Width')
                    ->numeric()
                    ->minValue(10)
                    ->maxValue(100)
                    ->suffix('%')
                    ->helperText('Set 50% to occupy the space of two 25% columns; widths in this row must total 100%.')
                    ->live(onBlur: true)
                    ->required(),
                Select::make('alignment')
                    ->label('Horizontal Alignment')
                    ->options(['left' => 'Left', 'center' => 'Center', 'right' => 'Right'])
                    ->default('left')
                    ->required(),
                Select::make('vertical_alignment')
                    ->label('Vertical Alignment')
                    ->options(['top' => 'Top', 'center' => 'Center', 'bottom' => 'Bottom'])
                    ->default('center')
                    ->required(),
                self::tokenRepeater('items'),
            ])
            ->columns(3)
            ->default($defaults['columns'])
            ->itemLabel(fn (array $state): string => ($state['width'] ?? '?').'% · '.str($state['alignment'] ?? 'left')->title().' aligned')
            ->reorderable()
            ->reorderableWithButtons()
            ->collapsible()
            ->minItems(1)
            ->maxItems(4)
            ->addActionLabel('Add Column')
            ->rules([
                fn (): Closure => function (string $attribute, mixed $value, Closure $fail): void {
                    if (! is_array($value) || collect($value)->sum(fn (mixed $column): int => (int) data_get($column, 'width', 0)) !== 100) {
                        $fail('Column widths must total exactly 100%.');
                    }
                },
            ]);
    }

    private static function rowRepeater(): Repeater
    {
        $defaults = app(PrintLayoutRegistry::class)->defaultHeaderZones();

        return Repeater::make('header_zones.rows')
            ->label('Table Rows')
            ->schema([
                Hidden::make('key')
                    ->default(fn (): string => 'row_'.Str::lower(Str::random(8)))
                    ->required(),
                Repeater::make('cells')
                    ->label('Cells')
                    ->schema([
                        Hidden::make('key')
                            ->default(fn (): string => 'cell_'.Str::lower(Str::random(8)))
                            ->required(),
                        TextInput::make('width')
                            ->label('Width')
                            ->numeric()
                            ->minValue(10)
                            ->maxValue(100)
                            ->suffix('%')
                            ->live(onBlur: true)
                            ->required(),
                        Select::make('alignment')
                            ->options(['left' => 'Left', 'center' => 'Center', 'right' => 'Right'])
                            ->default('left')
                            ->required(),
                        Select::make('vertical_alignment')
                            ->options(['top' => 'Top', 'center' => 'Center', 'bottom' => 'Bottom'])
                            ->default('center')
                            ->required(),
                        self::tokenRepeater('items'),
                    ])
                    ->columns(3)
                    ->itemLabel(fn (array $state): string => ($state['width'] ?? '?').'% cell')
                    ->reorderable()
                    ->reorderableWithButtons()
                    ->collapsible()
                    ->minItems(1)
                    ->maxItems(6)
                    ->addActionLabel('Add Cell')
                    ->rules([
                        fn (): Closure => function (string $attribute, mixed $value, Closure $fail): void {
                            if (! is_array($value) || collect($value)->sum(fn (mixed $cell): int => (int) data_get($cell, 'width', 0)) !== 100) {
                                $fail('Cell widths in this row must total exactly 100%.');
                            }
                        },
                    ]),
            ])
            ->default($defaults['rows'])
            ->itemLabel(fn (array $state): string => count($state['cells'] ?? []).' cell row')
            ->reorderable()
            ->reorderableWithButtons()
            ->collapsible()
            ->minItems(1)
            ->maxItems(8)
            ->addActionLabel('Add Row');
    }

    private static function tokenRepeater(string $name): Repeater
    {
        return Repeater::make($name)
            ->label('Content')
            ->schema([
                Select::make('token')
                    ->options(fn (): array => app(PrintLayoutRegistry::class)->tokenOptions())
                    ->disableOptionsWhenSelectedInSiblingRepeaterItems()
                    ->live()
                    ->required(),
                TextInput::make('custom_text')
                    ->label('Text')
                    ->visible(fn (Get $get): bool => $get('token') === 'custom_text')
                    ->required(fn (Get $get): bool => $get('token') === 'custom_text')
                    ->maxLength(200),
                Toggle::make('show_label')
                    ->label('Show Label')
                    ->helperText('Turn off to print only the value.')
                    ->default(true)
                    ->visible(fn (Get $get): bool => ! in_array($get('token'), ['custom_text', 'logo', 'organization_name', 'document_title', 'copy_status', 'controlled_notice'], true)),
                Toggle::make('emphasized')
                    ->label('Bold')
                    ->default(false),
            ])
            ->itemLabel(fn (array $state): string => app(PrintLayoutRegistry::class)->tokenOptions()[$state['token'] ?? ''] ?? 'Header / footer item')
            ->reorderable()
            ->reorderableWithButtons()
            ->collapsible()
            ->maxItems(8);
    }
}
