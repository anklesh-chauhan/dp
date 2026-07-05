<?php

declare(strict_types=1);

namespace App\Filament\Resources\ApprovalDecisions;

use App\Filament\Resources\ApprovalDecisions\Pages\CreateApprovalDecision;
use App\Filament\Resources\ApprovalDecisions\Pages\EditApprovalDecision;
use App\Filament\Resources\ApprovalDecisions\Pages\ListApprovalDecisions;
use App\Filament\Resources\LookupResource;
use App\Models\ApprovalDecision;
use Filament\Support\Icons\Heroicon;
use UnitEnum;

class ApprovalDecisionResource extends LookupResource
{
    protected static ?string $model = ApprovalDecision::class;

    protected static string|UnitEnum|null $navigationGroup = 'Global Configuration';

    protected static string|\BackedEnum|null $navigationIcon = Heroicon::CheckCircle;

    public static function getPages(): array
    {
        return [
            'index' => ListApprovalDecisions::route('/'),
            // 'create' => CreateApprovalDecision::route('/create'),
            // 'edit' => EditApprovalDecision::route('/{record}/edit'),
        ];
    }
}
