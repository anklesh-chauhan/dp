<?php

declare(strict_types=1);

namespace App\Jobs;

use App\Models\ControlledDocumentDraftRequest;
use App\Services\AI\ControlledDocumentDraftConversationService;
use App\Services\AI\Enums\ControlledDocumentDraftRequestStatus;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Throwable;

final class ProcessControlledDocumentDraftRequest implements ShouldBeUnique, ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;

    /** @var list<int> */
    public array $backoff = [30, 120];

    public int $uniqueFor = 3900;

    public int $timeout;

    public function __construct(public int $draftRequestId)
    {
        $this->timeout = (int) config('ai.draft_queue.timeout', 900);

        if (filled(config('ai.draft_queue.connection'))) {
            $this->onConnection((string) config('ai.draft_queue.connection'));
        }

        $this->onQueue((string) config('ai.draft_queue.queue', 'default'));
    }

    public function uniqueId(): string
    {
        return (string) $this->draftRequestId;
    }

    public function handle(ControlledDocumentDraftConversationService $service): void
    {
        $request = ControlledDocumentDraftRequest::query()
            ->with(['session', 'requester'])
            ->findOrFail($this->draftRequestId);

        if ($request->status === ControlledDocumentDraftRequestStatus::COMPLETED) {
            return;
        }

        if ($this->finalizePreviouslySavedResponse($request)) {
            return;
        }

        $request->forceFill([
            'status' => ControlledDocumentDraftRequestStatus::PROCESSING,
            'initial_preview_revision' => $request->initial_preview_revision
                ?? $request->session->preview_revision,
            'started_at' => $request->started_at ?? now(),
            'failure_message' => null,
            'failed_at' => null,
        ])->save();

        $result = $service->respond(
            session: $request->session,
            user: $request->requester,
            message: $request->message,
        );

        $request->forceFill([
            'status' => ControlledDocumentDraftRequestStatus::COMPLETED,
            'preview_hash' => $result['preview_hash'],
            'completed_at' => now(),
            'failure_message' => null,
            'failed_at' => null,
        ])->save();
    }

    public function failed(?Throwable $exception): void
    {
        $request = ControlledDocumentDraftRequest::query()
            ->with('session')
            ->find($this->draftRequestId);

        if ($request === null || $this->finalizePreviouslySavedResponse($request)) {
            return;
        }

        $request->forceFill([
            'status' => ControlledDocumentDraftRequestStatus::FAILED,
            'failure_message' => 'The drafting assistant could not complete this request. You can try again.',
            'failed_at' => now(),
        ])->save();
    }

    private function finalizePreviouslySavedResponse(ControlledDocumentDraftRequest $request): bool
    {
        if (
            $request->initial_preview_revision === null
            || $request->session->preview_revision <= $request->initial_preview_revision
            || blank($request->session->preview_hash)
        ) {
            return false;
        }

        $request->forceFill([
            'status' => ControlledDocumentDraftRequestStatus::COMPLETED,
            'preview_hash' => $request->session->preview_hash,
            'completed_at' => $request->completed_at ?? now(),
            'failure_message' => null,
            'failed_at' => null,
        ])->save();

        return true;
    }
}
