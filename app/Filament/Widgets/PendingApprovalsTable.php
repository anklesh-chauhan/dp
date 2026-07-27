<?php

declare(strict_types=1);

namespace App\Filament\Widgets;

use App\Enums\ProductModule;
use App\Filament\Concerns\RequiresProductModule;
use App\Filament\Support\MyApprovalQueueService;
use Filament\Actions\Action;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget;
use Illuminate\Support\Facades\Auth;

class PendingApprovalsTable extends TableWidget
{
    use RequiresProductModule;

    protected static ?int $sort = 4;

    protected int|string|array $columnSpan = 'full';

    public static function productModule(): ProductModule
    {
        return ProductModule::DMS;
    }

    public function table(Table $table): Table
    {
        return $table
            ->heading('My Approval Queue')
            ->description('Your next actionable approvals across DMS and QMS.')
            ->records(fn () => app(MyApprovalQueueService::class)
                ->forUser(Auth::user())
                ->take(5))
            ->columns([
                TextColumn::make('module')->badge(),
                TextColumn::make('work_type')
                    ->label('Approval Type')
                    ->badge(),
                TextColumn::make('reference')
                    ->label('Reference'),
                TextColumn::make('title')
                    ->label('Title')
                    ->limit(40),
                TextColumn::make('step')->label('Step'),
                TextColumn::make('required_role')
                    ->label('Required Role'),
                TextColumn::make('submitted_at')
                    ->label('Submitted')
                    ->dateTime(),
            ])
            ->paginated(false)
            ->recordActions([
                Action::make('review')
                    ->label('Review')
                    ->icon(Heroicon::Eye)
                    ->url(fn (array $record): string => $record['review_url']),
            ])
            ->emptyStateHeading('No pending approvals')
            ->emptyStateDescription('You are all caught up. New submissions will appear here.')
            ->emptyStateIcon(Heroicon::OutlinedCheckBadge);
    }
}
