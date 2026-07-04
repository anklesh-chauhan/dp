<?php

declare(strict_types=1);

namespace App\Filament\Resources\ApprovalDecisions;

use App\Filament\Resources\ApprovalDecisions\Pages\CreateApprovalDecision;
use App\Filament\Resources\ApprovalDecisions\Pages\EditApprovalDecision;
use App\Filament\Resources\ApprovalDecisions\Pages\ListApprovalDecisions;
use App\Filament\Resources\LookupResource;
use App\Models\ApprovalDecision;

class ApprovalDecisionResource extends LookupResource
{
    protected static ?string $model = ApprovalDecision::class;

    public static function getPages(): array
    {
        return [
            'index' => ListApprovalDecisions::route('/'),
            'create' => CreateApprovalDecision::route('/create'),
            'edit' => EditApprovalDecision::route('/{record}/edit'),
        ];
    }
}
