<?php

declare(strict_types=1);

namespace App\Filament\Resources\DocumentTemplateApprovalInstances\Schemas;

use App\Domain\DMS\Enums\TemplateApprovalStatus;
use App\Domain\Shared\Contracts\ElectronicSignatureVerifier;
use App\Models\DocumentTemplateApprovalInstance;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class DocumentTemplateApprovalInstanceInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Template under review')
                    ->description('Confirm the version and submission context before recording a decision.')
                    ->columns(3)
                    ->schema([
                        TextEntry::make('templateVersion.template.code')->label('Template Code')->copyable(),
                        TextEntry::make('templateVersion.template.name')->label('Template Name'),
                        TextEntry::make('templateVersion.version')->label('Version'),
                        TextEntry::make('templateVersion.approval_status')
                            ->label('Version Status')
                            ->badge()
                            ->color(fn (mixed $state): string => match ((string) ($state->value ?? $state)) {
                                'submitted' => 'warning',
                                'approved' => 'success',
                                'rejected' => 'danger',
                                default => 'gray',
                            }),
                        TextEntry::make('templateVersion.submitter.name')->label('Submitted By')->placeholder('Not recorded'),
                        TextEntry::make('templateVersion.submitted_at')->label('Submitted At')->dateTime()->placeholder('Not recorded'),
                        TextEntry::make('templateVersion.change_reason')
                            ->label('Version Change Reason')
                            ->columnSpanFull()
                            ->placeholder('No version change reason was provided.'),
                        TextEntry::make('submission_note')
                            ->label('Submission Note')
                            ->state(fn (DocumentTemplateApprovalInstance $record): ?string => $record->templateVersion->approvalEvents
                                ->where('to_status', TemplateApprovalStatus::Submitted)
                                ->last()?->reason)
                            ->columnSpanFull()
                            ->placeholder('No submission note was provided.'),
                    ]),
                Section::make('Your workflow task')
                    ->description('Only the currently actionable role and workflow step can record a decision.')
                    ->columns(3)
                    ->schema([
                        TextEntry::make('workflow.name')->label('Workflow'),
                        TextEntry::make('workflowStep.step_no')->label('Step Number')->badge()->color('info'),
                        TextEntry::make('workflowStep.approvalStepType.name')->label('Step Type')->badge(),
                        TextEntry::make('workflowStep.role.name')->label('Required Role'),
                        TextEntry::make('decision_code')
                            ->label('Decision')
                            ->badge()
                            ->color(fn (string $state): string => match ($state) {
                                'approved' => 'success',
                                'rejected' => 'danger',
                                'returned' => 'warning',
                                'pending' => 'info',
                                default => 'gray',
                            }),
                    ]),
                Section::make('Signed decision record')
                    ->description('Completed decisions retain their rationale, signer, timestamp, and signature integrity status.')
                    ->columns(3)
                    ->schema([
                        TextEntry::make('decider.name')->label('Decided By')->placeholder('Not yet decided'),
                        TextEntry::make('decided_at')->label('Decided At')->dateTime()->placeholder('Not yet decided'),
                        TextEntry::make('signature_status')
                            ->label('Electronic Signature')
                            ->state(fn (DocumentTemplateApprovalInstance $record): string => $record->signature_hash === null
                                ? 'Pending'
                                : (app(ElectronicSignatureVerifier::class)->isValid($record) ? 'Valid' : 'Invalid'))
                            ->badge()
                            ->color(fn (string $state): string => match ($state) {
                                'Valid' => 'success',
                                'Invalid' => 'danger',
                                default => 'gray',
                            }),
                        TextEntry::make('comments')
                            ->label('Decision Rationale')
                            ->columnSpanFull()
                            ->placeholder('No decision has been recorded.'),
                    ]),
            ]);
    }
}
