<?php

declare(strict_types=1);

namespace App\Filament\Pages;

use App\Enums\NumberSeriesOverflowBehavior;
use App\Filament\Clusters\Settings\SettingsCluster;
use App\Models\NumberSeriesSetting;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use UnitEnum;

class ManageNumberSeriesSettings extends Page implements HasForms
{
    use InteractsWithForms;

    protected static ?string $cluster = SettingsCluster::class;

    protected static string|UnitEnum|null $navigationGroup = 'General Masters';

    protected static ?int $navigationSort = 1008;

    protected static ?string $navigationLabel = 'Number Series Defaults';

    protected static ?string $title = 'Number Series Defaults';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedCog6Tooth;

    protected static ?string $slug = 'number-series-defaults';

    protected string $view = 'filament.pages.manage-number-series-settings';

    /** @var array<string, mixed>|null */
    public ?array $data = [];

    public function mount(): void
    {
        $this->authorize('view', NumberSeriesSetting::current());

        $this->form->fill(NumberSeriesSetting::current()->attributesToArray());
    }

    public function form(Schema $schema): Schema
    {
        return $schema
            ->statePath('data')
            ->components([
                Section::make('Default Number Format')
                    ->icon(Heroicon::Hashtag)
                    ->description('Applied to every document type unless overridden on the Number Series screen.')
                    ->schema([
                        TextInput::make('default_prefix_pattern')
                            ->label('Prefix pattern')
                            ->required()
                            ->helperText('Placeholders: {type}, {department}')
                            ->maxLength(255),
                        TextInput::make('default_padding_length')
                            ->label('Padding length')
                            ->required()
                            ->numeric()
                            ->minValue(1)
                            ->maxValue(10),
                        TextInput::make('default_suffix')
                            ->label('Suffix')
                            ->maxLength(50),
                    ])
                    ->columns(3),
                Section::make('Overflow Behavior')
                    ->icon(Heroicon::ExclamationTriangle)
                    ->schema([
                        Select::make('overflow_behavior')
                            ->label('When counter exceeds padding width')
                            ->options(collect(NumberSeriesOverflowBehavior::cases())
                                ->mapWithKeys(fn (NumberSeriesOverflowBehavior $behavior): array => [
                                    $behavior->value => $behavior->label(),
                                ])
                                ->all())
                            ->required()
                            ->native(false),
                    ]),
            ]);
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('save')
                ->label('Save')
                ->action('save'),
        ];
    }

    public function save(): void
    {
        $this->authorize('update', NumberSeriesSetting::current());

        $data = $this->form->getState();

        NumberSeriesSetting::current()->update($data);

        Notification::make()
            ->title('Number series defaults saved')
            ->success()
            ->send();
    }
}
