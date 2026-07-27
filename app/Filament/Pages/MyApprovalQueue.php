<?php

declare(strict_types=1);

namespace App\Filament\Pages;

use App\Filament\Support\MyApprovalQueueService;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Pages\Page;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;
use UnitEnum;

class MyApprovalQueue extends Page implements HasTable
{
    use InteractsWithTable;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedInboxStack;

    protected static string|UnitEnum|null $navigationGroup = 'DMS · Document Control';

    protected static ?string $navigationLabel = 'My Approval Queue';

    protected static ?string $title = 'My Approval Queue';

    protected static ?int $navigationSort = 3;

    protected static ?string $slug = 'my-approval-queue';

    protected string $view = 'filament.pages.my-approval-queue';

    public static function canAccess(): bool
    {
        $user = auth()->user();

        return $user !== null && (
            $user->can('Approve:SopDocument')
            || $user->can('Decide:SopTemplateApproval')
            || $user->can('Decide:QualityApproval')
        );
    }

    public static function getNavigationBadge(): ?string
    {
        $user = auth()->user();

        if ($user === null) {
            return null;
        }

        $count = app(MyApprovalQueueService::class)->forUser($user)->count();

        return $count > 0 ? (string) $count : null;
    }

    public function table(Table $table): Table
    {
        return $table
            ->records(function (
                ?string $search,
                ?string $sortColumn,
                ?string $sortDirection,
                array $filters,
                int $page,
                int $recordsPerPage,
            ): LengthAwarePaginator {
                $records = $this->records()
                    ->when(
                        filled($search),
                        fn (Collection $items): Collection => $items->filter(
                            fn (array $item): bool => Str::contains(
                                Str::lower(implode(' ', [
                                    $item['reference'],
                                    $item['title'],
                                    $item['department'],
                                    $item['required_role'],
                                ])),
                                Str::lower((string) $search),
                            ),
                        ),
                    )
                    ->when(
                        filled($filters['module']['value'] ?? null),
                        fn (Collection $items): Collection => $items->where(
                            'module',
                            $filters['module']['value'],
                        ),
                    )
                    ->when(
                        filled($filters['work_type']['value'] ?? null),
                        fn (Collection $items): Collection => $items->where(
                            'work_type',
                            $filters['work_type']['value'],
                        ),
                    );

                if (filled($sortColumn)) {
                    $records = $records->sortBy(
                        $sortColumn,
                        SORT_REGULAR,
                        $sortDirection === 'desc',
                    );
                }

                return new LengthAwarePaginator(
                    $records->forPage($page, $recordsPerPage),
                    $records->count(),
                    $recordsPerPage,
                    $page,
                );
            })
            ->columns([
                TextColumn::make('module')->badge(),
                TextColumn::make('work_type')->label('Approval Type')->badge(),
                TextColumn::make('reference')->label('Reference')->sortable(),
                TextColumn::make('title')->limit(45),
                TextColumn::make('department')->toggleable(),
                TextColumn::make('step')->sortable(),
                TextColumn::make('step_type')->label('Step Type')->badge(),
                TextColumn::make('required_role')->label('Required Role'),
                TextColumn::make('submitted_at')->label('Submitted')->dateTime()->sortable(),
            ])
            ->filters([
                SelectFilter::make('module')->options([
                    'DMS' => 'DMS',
                    'QMS' => 'QMS',
                ]),
                SelectFilter::make('work_type')->label('Approval Type')->options([
                    'SOP Document' => 'SOP Document',
                    'SOP Template' => 'SOP Template',
                    'Deviation' => 'Deviation',
                ]),
            ])
            ->recordActions([
                Action::make('review')
                    ->label('Review')
                    ->icon(Heroicon::Eye)
                    ->url(fn (array $record): string => $record['review_url']),
            ])
            ->searchable()
            ->defaultSort('submitted_at', 'desc')
            ->paginated([10, 25, 50])
            ->emptyStateHeading('No approvals waiting for you')
            ->emptyStateDescription('Only currently actionable steps assigned to your role and department appear here.')
            ->emptyStateIcon(Heroicon::OutlinedCheckBadge);
    }

    /**
     * @return Collection<string, array<string, int|string>>
     */
    private function records(): Collection
    {
        return app(MyApprovalQueueService::class)->forUser(auth()->user());
    }
}
