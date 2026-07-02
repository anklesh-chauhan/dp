<?php

declare(strict_types=1);

namespace App\Enums;

enum ApprovalStepType: string
{
    case Checker = 'checker';
    case Review = 'review';
    case QAReview = 'qa_review';
    case Approver = 'approver';
    case Approval = 'approval';

    public function label(): string
    {
        return match ($this) {
            self::Checker, self::Review => 'Checker',
            self::QAReview => 'QA Review',
            self::Approver, self::Approval => 'Approver',
        };
    }

    /**
     * @return array<string, string>
     */
    public static function options(): array
    {
        return [
            self::Checker->value => self::Checker->label(),
            self::QAReview->value => self::QAReview->label(),
            self::Approver->value => self::Approver->label(),
        ];
    }
}
