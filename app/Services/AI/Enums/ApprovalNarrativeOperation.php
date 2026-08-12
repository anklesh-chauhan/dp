<?php

declare(strict_types=1);

namespace App\Services\AI\Enums;

enum ApprovalNarrativeOperation: string
{
    case Create = 'create';

    case Polish = 'polish';

    case Shorten = 'shorten';

    public function instruction(ApprovalNarrativeKind $kind): string
    {
        return match ($this) {
            self::Create => match ($kind) {
                ApprovalNarrativeKind::SubmissionNote => 'Draft a clear submission note for reviewers.',
                ApprovalNarrativeKind::DecisionRationale => 'Draft a clear decision rationale for the signed approval record.',
            },
            self::Polish => 'Polish and formalize the text using clear, professional pharmaceutical QMS language while preserving meaning.',
            self::Shorten => 'Shorten the text while preserving all important meaning, controls, and attributable intent.',
        };
    }
}
