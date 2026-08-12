<?php

declare(strict_types=1);

namespace App\Filament\Resources\DocumentTemplateApprovalInstances\Tables;

use App\Domain\DMS\Services\TemplateApprovalDecisionService;
use App\Domain\Shared\Enums\ApprovalDecisionCode;
use App\Filament\Support\ApprovalNarrativeTextarea;
use App\Models\DocumentTemplateApprovalInstance;
use App\Models\User;
use Filament\Actions\Action;
use Filament\Actions\ViewAction;
use Filament\Notifications\Notification;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class DocumentTemplateApprovalInstancesTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('templateVersion.template.code')->label('Template')->searchable(),
                TextColumn::make('templateVersion.version')->label('Version'),
                TextColumn::make('workflow.name')->label('Workflow'),
                TextColumn::make('workflowStep.step_no')->label('Step')->sortable(),
                TextColumn::make('workflowStep.role.name')->label('Required Role'),
                TextColumn::make('decision_code')->label('Decision')->badge(),
                TextColumn::make('decider.name')->label('Decided By')->placeholder('—'),
                TextColumn::make('decided_at')->dateTime()->placeholder('—'),
            ])
            ->filters([
                SelectFilter::make('decision_code')->options([
                    'pending' => 'Pending',
                    'approved' => 'Approved',
                    'rejected' => 'Rejected',
                    'returned' => 'Returned',
                    'not_required' => 'Not Required',
                ])->default('pending'),
            ])
            ->recordActions([
                ViewAction::make(),
                self::decisionAction('approve', 'Approve', ApprovalDecisionCode::APPROVED, 'success'),
                self::decisionAction('reject', 'Reject', ApprovalDecisionCode::REJECTED, 'danger'),
                self::decisionAction('return', 'Return for Correction', ApprovalDecisionCode::RETURNED, 'warning'),
            ]);
    }

    private static function decisionAction(
        string $name,
        string $label,
        ApprovalDecisionCode $decision,
        string $color,
    ): Action {
        return Action::make($name)
            ->label($label)
            ->color($color)
            ->schema(fn (DocumentTemplateApprovalInstance $record): array => [
                ApprovalNarrativeTextarea::decisionRationale(
                    label: 'Decision reason',
                    context: [
                        'record_type' => 'Document template approval',
                        'subject' => trim(implode(' · ', array_filter([
                            $record->templateVersion?->template?->code,
                            $record->templateVersion?->template?->name,
                            $record->templateVersion !== null
                                ? 'v'.$record->templateVersion->version
                                : null,
                        ]))),
                        'department' => $record->templateVersion?->template?->department?->name,
                        'decision' => $label,
                    ],
                ),
            ])
            ->visible(function (DocumentTemplateApprovalInstance $record): bool {
                $user = auth()->user();

                return $user instanceof User
                    && app(TemplateApprovalDecisionService::class)->canDecide($record, $user);
            })
            ->action(function (DocumentTemplateApprovalInstance $record, array $data) use ($decision, $label): void {
                /** @var User $user */
                $user = auth()->user();
                app(TemplateApprovalDecisionService::class)->decide(
                    $record,
                    $user,
                    $decision,
                    $data['comments'],
                    request()->ip(),
                    request()->userAgent(),
                );
                Notification::make()->success()->title("Template approval: {$label}")->send();
            });
    }
}
