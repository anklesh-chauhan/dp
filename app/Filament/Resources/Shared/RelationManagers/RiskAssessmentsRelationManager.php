<?php

declare(strict_types=1);

namespace App\Filament\Resources\Shared\RelationManagers;

use App\Domain\QMS\Models\RiskAssessment;
use App\Domain\QMS\Models\RiskAssessmentLink;
use App\Domain\QMS\Services\RiskAssessmentLinkService;
use App\Enums\ProductModule;
use App\Models\User;
use App\Support\Modules\ModuleManager;
use Filament\Actions\Action;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Model;

final class RiskAssessmentsRelationManager extends RelationManager
{
    protected static string $relationship = 'riskAssessments';

    protected static ?string $title = 'Linked Risk Assessments';

    public static function canViewForRecord(Model $ownerRecord, string $pageClass): bool
    {
        return app(ModuleManager::class)->enabled(ProductModule::QMS)
            && (bool) auth()->user()?->can('View:RiskAssessment');
    }

    public function isReadOnly(): bool
    {
        return ! (bool) auth()->user()?->can('Update:RiskAssessment');
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('risk_number')
            ->columns([
                TextColumn::make('risk_number')->searchable()->sortable(),
                TextColumn::make('title')->wrap()->limit(60),
                TextColumn::make('status')->badge(),
                TextColumn::make('pivot.link_role')->label('Link role')->placeholder('—'),
            ])
            ->headerActions([
                Action::make('linkRiskAssessment')
                    ->label('Link risk assessment')
                    ->schema([
                        Select::make('risk_assessment_id')
                            ->label('Risk assessment')
                            ->options(
                                fn (): array => RiskAssessment::query()
                                    ->latest('id')
                                    ->limit(100)
                                    ->get()
                                    ->mapWithKeys(fn (RiskAssessment $risk): array => [
                                        $risk->getKey() => "{$risk->risk_number} — {$risk->title}",
                                    ])
                                    ->all()
                            )
                            ->searchable()
                            ->required(),
                        TextInput::make('link_role')
                            ->label('Link role')
                            ->placeholder('source')
                            ->maxLength(100),
                    ])
                    ->action(function (array $data): void {
                        /** @var User $actor */
                        $actor = auth()->user();
                        $risk = RiskAssessment::query()->findOrFail((int) $data['risk_assessment_id']);

                        app(RiskAssessmentLinkService::class)->attachToEvent(
                            $this->getOwnerRecord(),
                            $risk,
                            $actor,
                            $data['link_role'] ?? null,
                        );
                    })
                    ->visible(fn (): bool => (bool) auth()->user()?->can('Update:RiskAssessment')),
            ])
            ->recordActions([
                Action::make('unlink')
                    ->label('Unlink')
                    ->color('danger')
                    ->requiresConfirmation()
                    ->action(function (RiskAssessment $record): void {
                        /** @var User $actor */
                        $actor = auth()->user();

                        /** @var RiskAssessmentLink $link */
                        $link = RiskAssessmentLink::query()
                            ->where('risk_assessment_id', $record->getKey())
                            ->where('linkable_type', $this->getOwnerRecord()->getMorphClass())
                            ->where('linkable_id', $this->getOwnerRecord()->getKey())
                            ->firstOrFail();

                        app(RiskAssessmentLinkService::class)->detach($link, $actor);
                    })
                    ->visible(fn (): bool => (bool) auth()->user()?->can('Update:RiskAssessment')),
            ]);
    }
}
