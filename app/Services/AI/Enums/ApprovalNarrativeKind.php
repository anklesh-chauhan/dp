<?php

declare(strict_types=1);

namespace App\Services\AI\Enums;

enum ApprovalNarrativeKind: string
{
    case SubmissionNote = 'submission_note';

    case DecisionRationale = 'decision_rationale';

    public function useCase(): AIUseCase
    {
        return match ($this) {
            self::SubmissionNote => AIUseCase::APPROVAL_SUBMISSION_NOTE,
            self::DecisionRationale => AIUseCase::APPROVAL_DECISION_RATIONALE,
        };
    }

    public function label(): string
    {
        return match ($this) {
            self::SubmissionNote => 'submission note',
            self::DecisionRationale => 'decision rationale',
        };
    }

    public function purpose(): string
    {
        return match ($this) {
            self::SubmissionNote => 'Tell reviewers what changed and what they should focus on.',
            self::DecisionRationale => 'Explain what you reviewed and why you are making this decision. This text becomes part of the signed approval record.',
        };
    }
}
