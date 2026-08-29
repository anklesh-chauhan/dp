<?php

declare(strict_types=1);

namespace App\Ai\Tools;

use App\Filament\Support\MyApprovalQueueService;
use App\Models\User;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Ai\Contracts\Tool;
use Laravel\Ai\Tools\Request;
use Stringable;

final class ListMyApprovals implements Tool
{
    public function __construct(
        private readonly User $user,
        private readonly MyApprovalQueueService $approvalQueue,
    ) {}

    /**
     * Get the description of the tool's purpose.
     */
    public function description(): Stringable|string
    {
        return 'List the authenticated user’s currently actionable approval steps with read-only citation URLs.';
    }

    /**
     * Execute the tool.
     */
    public function handle(Request $request): Stringable|string
    {
        $approvals = $this->approvalQueue->forUser($this->user)
            ->take(20)
            ->values();

        return $approvals->isEmpty()
            ? 'No approvals are currently waiting for this user.'
            : $approvals->toJson(JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    }

    /**
     * Get the tool's schema definition.
     */
    public function schema(JsonSchema $schema): array
    {
        return [];
    }
}
