<?php

declare(strict_types=1);

namespace App\Filament\Resources\ApprovalStepTypes;

use App\Filament\Clusters\Settings\SettingsCluster;
use App\Filament\Resources\ApprovalStepTypes\Pages\CreateApprovalStepType;
use App\Filament\Resources\ApprovalStepTypes\Pages\EditApprovalStepType;
use App\Filament\Resources\ApprovalStepTypes\Pages\ListApprovalStepTypes;
use App\Filament\Resources\LookupResource;
use App\Models\ApprovalStepType;
use Filament\Support\Icons\Heroicon;
use UnitEnum;

class ApprovalStepTypeResource extends LookupResource
{
    protected static ?string $model = ApprovalStepType::class;

    protected static ?string $cluster = SettingsCluster::class;

    protected static string|UnitEnum|null $navigationGroup = 'DMS Configuration';

    protected static ?int $navigationSort = 1002;

    protected static string|\BackedEnum|null $navigationIcon = Heroicon::CheckCircle;

    public static function getPages(): array
    {
        return [
            'index' => ListApprovalStepTypes::route('/'),
            // 'create' => CreateApprovalStepType::route('/create'),
            // 'edit' => EditApprovalStepType::route('/{record}/edit'),
        ];
    }
}
