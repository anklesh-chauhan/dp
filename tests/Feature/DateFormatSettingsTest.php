<?php

declare(strict_types=1);

use App\Enums\DateDisplayFormat;
use App\Enums\DateTimeDisplayFormat;
use App\Enums\TimeDisplayFormat;
use App\Filament\Resources\Organizations\Pages\EditOrganization;
use App\Models\DocumentTemplateVariable;
use App\Models\Organization;
use App\Models\User;
use App\Models\VariableDataType;
use App\Support\Formatting\DateFormatSettings;
use App\Support\Sop\VariableTypes\Handlers\DateTimeVariableTypeHandler;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\DateTimePicker;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Livewire\Livewire;
use Spatie\Permission\Models\Permission;

uses(RefreshDatabase::class);

beforeEach(function (): void {
    config()->set('modules.enabled', ['dms']);
});

it('falls back to configuration defaults when no organization profile exists', function (): void {
    config()->set('formatting.date', DateDisplayFormat::Iso->value);
    config()->set('formatting.datetime', DateTimeDisplayFormat::IsoHm->value);
    config()->set('formatting.time', TimeDisplayFormat::TwelveHour->value);

    $formats = app(DateFormatSettings::class);

    expect($formats->date())->toBe(DateDisplayFormat::Iso->value)
        ->and($formats->dateTime())->toBe(DateTimeDisplayFormat::IsoHm->value)
        ->and($formats->time())->toBe(TimeDisplayFormat::TwelveHour->value)
        ->and($formats->formatDate(Carbon::parse('2026-08-13')))->toBe('2026-08-13')
        ->and($formats->formatDateTime(Carbon::parse('2026-08-13 14:30:00')))->toBe('2026-08-13 14:30')
        ->and($formats->formatTime(Carbon::parse('2026-08-13 14:30:00')))->toBe('02:30 PM');
});

it('uses the organization profile display formats when present', function (): void {
    Organization::factory()->create([
        'is_default' => true,
        'date_display_format' => DateDisplayFormat::DayMonYear->value,
        'datetime_display_format' => DateTimeDisplayFormat::DayMonYearHm->value,
        'time_display_format' => TimeDisplayFormat::TwentyFourHourWithSeconds->value,
    ]);

    $formats = app(DateFormatSettings::class);

    expect($formats->date())->toBe(DateDisplayFormat::DayMonYear->value)
        ->and($formats->dateTime())->toBe(DateTimeDisplayFormat::DayMonYearHm->value)
        ->and($formats->time())->toBe(TimeDisplayFormat::TwentyFourHourWithSeconds->value)
        ->and($formats->formatDate(Carbon::parse('2026-08-13')))->toBe('13-Aug-2026');
});

it('ignores invalid organization formats and keeps the configured fallback', function (): void {
    Organization::factory()->create([
        'is_default' => true,
        'date_display_format' => 'not-a-real-format',
        'datetime_display_format' => 'also-invalid',
        'time_display_format' => 'nope',
    ]);

    config()->set('formatting.date', DateDisplayFormat::DayMonthYear->value);
    config()->set('formatting.datetime', DateTimeDisplayFormat::DayMonthYearHm->value);
    config()->set('formatting.time', TimeDisplayFormat::TwentyFourHour->value);

    $formats = app(DateFormatSettings::class);

    expect($formats->date())->toBe(DateDisplayFormat::DayMonthYear->value)
        ->and($formats->dateTime())->toBe(DateTimeDisplayFormat::DayMonthYearHm->value)
        ->and($formats->time())->toBe(TimeDisplayFormat::TwentyFourHour->value);
});

it('applies organization date formats through filament picker defaults', function (): void {
    Organization::factory()->create([
        'is_default' => true,
        'date_display_format' => DateDisplayFormat::DayMonthYear->value,
        'datetime_display_format' => DateTimeDisplayFormat::DayMonthYearHm->value,
        'time_display_format' => TimeDisplayFormat::TwentyFourHour->value,
    ]);

    $datePicker = DatePicker::make('effective_date');
    $dateTimePicker = DateTimePicker::make('issued_at');

    expect($datePicker->getDefaultDateDisplayFormat())->toBe('d/m/Y')
        ->and($dateTimePicker->getDefaultDateDisplayFormat())->toBe('d/m/Y')
        ->and($dateTimePicker->getDefaultDateTimeDisplayFormat())->toBe('d/m/Y H:i')
        ->and($dateTimePicker->getDefaultTimeDisplayFormat())->toBe('H:i');
});

it('allows administrators to update organization date display formats', function (): void {
    foreach (['ViewAny:Organization', 'View:Organization', 'Update:Organization'] as $permission) {
        Permission::findOrCreate($permission, 'web');
    }

    $user = User::factory()->create();
    $user->givePermissionTo([
        'ViewAny:Organization',
        'View:Organization',
        'Update:Organization',
    ]);
    $this->actingAs($user);

    $organization = Organization::factory()->create([
        'is_default' => true,
        'phone' => '+91 79 1234 5678',
        'date_display_format' => DateDisplayFormat::DayMonthYear->value,
        'datetime_display_format' => DateTimeDisplayFormat::DayMonthYearHm->value,
        'time_display_format' => TimeDisplayFormat::TwentyFourHour->value,
    ]);

    Livewire::test(EditOrganization::class, ['record' => $organization->getKey()])
        ->fillForm([
            'phone' => '+91 79 1234 5678',
            'date_display_format' => DateDisplayFormat::Iso->value,
            'datetime_display_format' => DateTimeDisplayFormat::IsoHm->value,
            'time_display_format' => TimeDisplayFormat::TwelveHour->value,
        ])
        ->call('save')
        ->assertHasNoFormErrors()
        ->assertNotified();

    expect($organization->refresh()->date_display_format)->toBe(DateDisplayFormat::Iso->value)
        ->and($organization->datetime_display_format)->toBe(DateTimeDisplayFormat::IsoHm->value)
        ->and($organization->time_display_format)->toBe(TimeDisplayFormat::TwelveHour->value)
        ->and(app(DateFormatSettings::class)->date())->toBe(DateDisplayFormat::Iso->value);
});

it('formats printed controlled-document dates with the organization display setting', function (): void {
    Organization::factory()->create([
        'is_default' => true,
        'timezone' => 'Asia/Kolkata',
        'date_display_format' => DateDisplayFormat::DayMonthYear->value,
        'datetime_display_format' => DateTimeDisplayFormat::DayMonthYearHm->value,
    ]);

    $html = view('reports.partials.print-zone', [
        'items' => [
            ['token' => 'effective_date', 'label' => 'Effective Date'],
            ['token' => 'printed_at', 'label' => 'Printed At'],
        ],
        'document' => (object) [
            'effective_date' => Carbon::parse('2026-08-13'),
        ],
        'preview' => false,
    ])->render();

    expect($html)
        ->toContain('Effective Date: 13/08/2026')
        ->not->toContain('Aug 13, 2026');
});

it('substitutes template date variables using the display format', function (): void {
    Organization::factory()->create([
        'is_default' => true,
        'date_display_format' => DateDisplayFormat::DayMonthYear->value,
        'datetime_display_format' => DateTimeDisplayFormat::DayMonthYearHm->value,
        'time_display_format' => TimeDisplayFormat::TwentyFourHour->value,
    ]);

    $variable = new DocumentTemplateVariable;
    $variable->setRelation('variableDataType', new VariableDataType(['code' => VariableDataType::DATE]));

    $handler = new DateTimeVariableTypeHandler;

    expect($handler->formatForSubstitution($variable, Carbon::parse('2026-08-13')))
        ->toBe('13/08/2026')
        ->and($handler->formatForStorage($variable, Carbon::parse('2026-08-13')))
        ->toBe('2026-08-13');
});
