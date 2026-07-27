<?php

declare(strict_types=1);

namespace App\Filament\Resources\SopTemplateApprovalInstances\Tables;

use App\Domain\DMS\Services\TemplateApprovalDecisionService;
use App\Domain\Shared\Enums\ApprovalDecisionCode;
use App\Models\SopTemplateApprovalInstance;
use App\Models\User;
use Filament\Actions\Action;
use Filament\Actions\ViewAction;
use Filament\Forms\Components\Textarea;
use Filament\Notifications\Notification;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class SopTemplateApprovalInstancesTable
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
            ->schema([
                Textarea::make('comments')->label('Decision reason')->required()->maxLength(2_000),
            ])
            ->visible(function (SopTemplateApprovalInstance $record): bool {
                $user = auth()->user();

                return $user instanceof User
                    && app(TemplateApprovalDecisionService::class)->canDecide($record, $user);
            })
            ->action(function (SopTemplateApprovalInstance $record, array $data) use ($decision, $label): void {
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
