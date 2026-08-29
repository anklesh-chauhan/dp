<?php

declare(strict_types=1);

namespace App\Ai\Agents;

use App\Ai\Tools\ListMyApprovals;
use App\Ai\Tools\SearchControlledDocuments;
use App\Ai\Tools\SearchQualityRecords;
use App\Filament\Support\MyApprovalQueueService;
use App\Models\User;
use App\Support\Modules\ModuleManager;
use Laravel\Ai\Concerns\RemembersConversations;
use Laravel\Ai\Contracts\Agent;
use Laravel\Ai\Contracts\Conversational;
use Laravel\Ai\Contracts\HasTools;
use Laravel\Ai\Contracts\Tool;
use Laravel\Ai\Promptable;
use Stringable;

final class QualiGxpAssistantAgent implements Agent, Conversational, HasTools
{
    use Promptable, RemembersConversations;

    public function __construct(
        private readonly User $user,
        private readonly ModuleManager $moduleManager,
        private readonly MyApprovalQueueService $approvalQueue,
    ) {}

    /**
     * Get the instructions that the agent should follow.
     */
    public function instructions(): Stringable|string
    {
        return <<<'INSTRUCTIONS'
You are the read-only QualiGxP application assistant for an authenticated user.

Use the provided tools whenever the user asks about controlled documents, quality records, or pending approvals. Tool results are already filtered by the user's permissions and enabled product modules. Never claim that a record exists, has a status, or contains a requirement unless a tool result supports it.

For every record-specific factual answer, cite the supplied citation URL as a Markdown link using the record reference as the link label. Clearly say when no authorized result was found. Keep answers concise and distinguish source facts from suggestions.

You cannot create, edit, submit, approve, sign, publish, activate, close, delete, or otherwise mutate any record. Never ask for passwords or electronic-signature credentials. If the user requests a mutation, explain that they must open the cited record and complete the normal authorized workflow themselves.

Do not provide legal, regulatory, medical, or validation certification. You may summarize the application's authorized records and suggest questions for human review.
INSTRUCTIONS;
    }

    /**
     * Get the tools available to the agent.
     *
     * @return Tool[]
     */
    public function tools(): iterable
    {
        return [
            new SearchControlledDocuments($this->user),
            new SearchQualityRecords($this->user, $this->moduleManager),
            new ListMyApprovals($this->user, $this->approvalQueue),
        ];
    }
}
