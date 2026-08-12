<?php

declare(strict_types=1);

namespace App\Filament\Resources\CsvValidationProjects\Pages;

use App\Domain\QMS\Enums\CsvValidationProjectStatus;
use App\Domain\QMS\Services\CsvValidationProjectService;
use App\Domain\Reporting\Enums\ReportScope;
use App\Filament\Resources\CsvValidationProjects\CsvValidationProjectResource;
use App\Filament\Support\ApprovalNarrativeTextarea;
use App\Models\ReportTemplate;
use App\Models\User;
use Filament\Actions\Action;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Select;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ViewRecord;
use Filament\Support\Icons\Heroicon;
use Illuminate\Validation\ValidationException;

final class ViewCsvValidationProject extends ViewRecord
{
    protected static string $resource = CsvValidationProjectResource::class;

    protected function getHeaderActions(): array
    {
        return [
            EditAction::make()->visible(fn (): bool => CsvValidationProjectResource::canEdit($this->record)),
            Action::make('exportValidationReport')
                ->label('Export')
                ->icon(Heroicon::ArrowDownTray)
                ->schema([
                    Select::make('template')
                        ->label('Report Template & Format')
                        ->options(fn (): array => ReportTemplate::query()
                            ->active()
                            ->whereIn('scope', [
                                ReportScope::CsvValidationTraceability,
                                ReportScope::CsvValidationSummary,
                            ])
                            ->get()
                            ->mapWithKeys(fn (ReportTemplate $template): array => [
                                $template->id => "{$template->name} ({$template->format->label()})",
                            ])
                            ->all())
                        ->required(),
                ])
                ->action(fn (array $data): mixed => $this->redirect(route('csv-validation-projects.report', [
                    'csvValidationProject' => $this->record,
                    'template' => $data['template'],
                ]))),
            ...collect($this->availableTransitions())
                ->map(fn (array $transition): Action => $this->transitionAction(...$transition))
                ->all(),
        ];
    }

    /**
     * @return list<array{string, string, CsvValidationProjectStatus, string, string}>
     */
    private function availableTransitions(): array
    {
        return match ($this->record->status) {
            CsvValidationProjectStatus::Draft => [
                ['assess', 'Complete GxP Assessment', CsvValidationProjectStatus::GxpAssessment, 'Assess:CsvValidationProject', 'primary'],
            ],
            CsvValidationProjectStatus::GxpAssessment => [
                ['plan', 'Begin Validation Planning', CsvValidationProjectStatus::Planning, 'Plan:CsvValidationProject', 'primary'],
            ],
            CsvValidationProjectStatus::Planning => [
                ['specify', 'Begin Specification', CsvValidationProjectStatus::Specification, 'Specify:CsvValidationProject', 'primary'],
            ],
            CsvValidationProjectStatus::Specification => [
                ['test', 'Begin Testing', CsvValidationProjectStatus::Testing, 'Test:CsvValidationProject', 'primary'],
            ],
            CsvValidationProjectStatus::Testing => [
                ['review', 'Begin Validation Review', CsvValidationProjectStatus::ValidationReview, 'Review:CsvValidationProject', 'warning'],
                ['resolve', 'Resolve Deviations', CsvValidationProjectStatus::DeviationResolution, 'Test:CsvValidationProject', 'warning'],
            ],
            CsvValidationProjectStatus::DeviationResolution => [
                ['resume', 'Resume Testing', CsvValidationProjectStatus::Testing, 'Test:CsvValidationProject', 'primary'],
                ['review', 'Begin Validation Review', CsvValidationProjectStatus::ValidationReview, 'Review:CsvValidationProject', 'warning'],
            ],
            CsvValidationProjectStatus::ValidationReview => [
                ['release', 'QA Release', CsvValidationProjectStatus::Released, 'Release:CsvValidationProject', 'success'],
                ['retest', 'Return to Testing', CsvValidationProjectStatus::Testing, 'Test:CsvValidationProject', 'warning'],
            ],
            CsvValidationProjectStatus::Released => [
                ['periodic', 'Begin Periodic Review', CsvValidationProjectStatus::PeriodicReview, 'PeriodicReview:CsvValidationProject', 'primary'],
                ['retire', 'Retire System', CsvValidationProjectStatus::Retired, 'Manage:CsvValidationProject', 'danger'],
            ],
            CsvValidationProjectStatus::PeriodicReview => [
                ['continue', 'Continue Validated Use', CsvValidationProjectStatus::Released, 'Release:CsvValidationProject', 'success'],
                ['revalidate', 'Require Revalidation', CsvValidationProjectStatus::Testing, 'Test:CsvValidationProject', 'warning'],
                ['retire', 'Retire System', CsvValidationProjectStatus::Retired, 'Manage:CsvValidationProject', 'danger'],
            ],
            CsvValidationProjectStatus::Retired, CsvValidationProjectStatus::Cancelled => [],
        };
    }

    private function transitionAction(
        string $name,
        string $label,
        CsvValidationProjectStatus $toStatus,
        string $permission,
        string $color,
    ): Action {
        return Action::make($name)
            ->label($label)
            ->color($color)
            ->schema([
                ApprovalNarrativeTextarea::decisionRationale(
                    name: 'reason',
                    label: 'Decision reason',
                    helperText: 'This reason is retained with the signed audit event.',
                    context: fn (): array => [
                        'record_type' => 'CSV validation project decision',
                        'subject' => $this->record->project_number
                            ?? $this->record->system_name
                            ?? (string) $this->record->getKey(),
                        'department' => $this->record->department?->name,
                        'decision' => $label,
                    ],
                ),
            ])
            ->visible(fn (): bool => (bool) auth()->user()?->can($permission))
            ->action(function (array $data, Action $action) use ($toStatus, $label): void {
                /** @var User $user */
                $user = auth()->user();

                try {
                    app(CsvValidationProjectService::class)->transition(
                        $this->record,
                        $toStatus,
                        $user,
                        $data['reason'],
                        ipAddress: request()->ip(),
                        userAgent: request()->userAgent(),
                    );
                } catch (ValidationException $exception) {
                    Notification::make()
                        ->danger()
                        ->title($toStatus === CsvValidationProjectStatus::Released
                            ? 'QA release blocked'
                            : "{$label} blocked")
                        ->body(collect($exception->errors())
                            ->flatten()
                            ->unique()
                            ->implode("\n"))
                        ->persistent()
                        ->send();

                    $action->halt();

                    return;
                }

                $this->record->refresh();
                $this->refreshFormData(['status', 'released_by', 'released_at', 'retired_at']);

                Notification::make()->success()->title($label)->send();
            });
    }
}
