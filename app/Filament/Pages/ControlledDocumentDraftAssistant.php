<?php

declare(strict_types=1);

namespace App\Filament\Pages;

use App\Domain\DMS\Services\SopReferenceService;
use App\Domain\DMS\Services\VariableResolverService;
use App\Enums\ProductModule;
use App\Filament\Resources\ControlledDocuments\ControlledDocumentResource;
use App\Models\ControlledDocumentDraftRequest;
use App\Models\ControlledDocumentDraftSession;
use App\Models\DocumentTemplate;
use App\Models\TemplateStatus;
use App\Models\User;
use App\Services\AI\Actions\CreateControlledDocumentFromAiDraftAction;
use App\Services\AI\AiDraftVariableNormalizer;
use App\Services\AI\ControlledDocumentDraftConversationService;
use App\Services\AI\Enums\ControlledDocumentDraftRequestStatus;
use App\Support\Modules\ModuleManager;
use BackedEnum;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Support\Icons\Heroicon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Validation\ValidationException;
use Laravel\Ai\Models\ConversationMessage;
use Livewire\Attributes\Computed;
use Throwable;
use UnitEnum;

final class ControlledDocumentDraftAssistant extends Page
{
    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedChatBubbleLeftRight;

    protected static string|UnitEnum|null $navigationGroup = 'AI Management';

    protected static ?string $navigationLabel = 'Document Draft Assistant';

    protected static ?string $title = 'Controlled Document Draft Assistant';

    protected static ?string $slug = 'controlled-document-draft-assistant';

    protected static ?int $navigationSort = 1;

    protected string $view = 'filament.pages.controlled-document-draft-assistant';

    public ?int $templateId = null;

    public ?int $ownerId = null;

    public ?int $referencedControlledDocumentId = null;

    public ?int $draftSessionId = null;

    public string $userMessage = '';

    public ?int $draftRequestId = null;

    public ?string $expectedPreviewHash = null;

    public static function canAccess(): bool
    {
        $user = auth()->user();

        return $user instanceof User
            && app(ModuleManager::class)->enabled(ProductModule::AI)
            && $user->can('Create:ControlledDocument');
    }

    public function mount(): void
    {
        $this->ownerId = auth()->id();
    }

    public function startConversation(
        ControlledDocumentDraftConversationService $service,
    ): void {
        $data = $this->validate([
            'templateId' => ['required', 'integer'],
            'ownerId' => ['required', 'integer', 'exists:users,id'],
            'referencedControlledDocumentId' => ['nullable', 'integer', 'exists:controlled_documents,id'],
        ]);

        $template = DocumentTemplate::query()
            ->with('publishedVersion')
            ->findOrFail($data['templateId']);

        if ($template->publishedVersion === null) {
            throw ValidationException::withMessages([
                'templateId' => 'Select a template with a published version.',
            ]);
        }

        $session = $service->start(
            user: auth()->user(),
            templateVersionId: (int) $template->publishedVersion->getKey(),
            ownerId: (int) $data['ownerId'],
            referencedControlledDocumentId: $data['referencedControlledDocumentId'],
        );

        $this->draftSessionId = (int) $session->getKey();
        $this->draftRequestId = null;
        $this->expectedPreviewHash = null;
        $this->userMessage = '';
        unset($this->session, $this->messages, $this->previewSections, $this->draftHistory);
    }

    public function sendMessage(
        ControlledDocumentDraftConversationService $service,
    ): void {
        $data = $this->validate([
            'userMessage' => ['required', 'string', 'max:10000'],
            'draftSessionId' => ['required', 'integer'],
        ]);

        try {
            $request = $service->queueResponse(
                session: $this->ownedSession(),
                user: auth()->user(),
                message: $data['userMessage'],
            );

            $this->draftRequestId = (int) $request->getKey();
            $this->expectedPreviewHash = null;
            $this->userMessage = '';
            unset($this->draftRequest, $this->draftRequestActive);

            Notification::make()
                ->info()
                ->title('Drafting request queued')
                ->body('You can keep this page open while the background worker prepares the response.')
                ->send();
        } catch (ValidationException $exception) {
            throw $exception;
        } catch (Throwable $exception) {
            report($exception);

            Notification::make()
                ->danger()
                ->title('The drafting assistant could not respond')
                ->body('Please try again. If the problem continues, contact an administrator.')
                ->send();
        }
    }

    public function openDraftSession(int $draftSessionId): void
    {
        $session = ControlledDocumentDraftSession::query()
            ->with('latestDraftRequest')
            ->where('created_by', auth()->id())
            ->findOrFail($draftSessionId);

        $this->draftSessionId = (int) $session->getKey();
        $this->templateId = (int) $session->template_id;
        $this->ownerId = (int) $session->owner_id;
        $this->referencedControlledDocumentId = $session->referenced_controlled_document_id;
        $this->draftRequestId = $session->latestDraftRequest?->getKey();
        $this->expectedPreviewHash = $session->latestDraftRequest?->status === ControlledDocumentDraftRequestStatus::COMPLETED
            ? $session->latestDraftRequest->preview_hash
            : null;
        $this->userMessage = '';

        unset(
            $this->session,
            $this->messages,
            $this->previewSections,
            $this->draftRequest,
            $this->draftRequestActive,
        );
    }

    public function refreshDraftStatus(): void
    {
        unset(
            $this->draftRequest,
            $this->draftRequestActive,
            $this->session,
            $this->messages,
            $this->previewSections,
        );

        $request = $this->draftRequest;

        if ($request?->status === ControlledDocumentDraftRequestStatus::COMPLETED) {
            $this->expectedPreviewHash = $request->preview_hash;
        }

        if ($request?->status === ControlledDocumentDraftRequestStatus::FAILED) {
            $this->expectedPreviewHash = null;
        }
    }

    public function createDraft(
        CreateControlledDocumentFromAiDraftAction $action,
    ): void {
        $session = $this->ownedSession();

        if (blank($this->expectedPreviewHash)) {
            throw ValidationException::withMessages([
                'confirmation' => 'Review the latest generated preview before creating the draft.',
            ]);
        }

        try {
            $document = $action->execute(
                session: $session,
                user: auth()->user(),
                expectedPreviewHash: $this->expectedPreviewHash,
            );
        } catch (ValidationException $exception) {
            throw ValidationException::withMessages([
                'confirmation' => collect($exception->errors())->flatten()->first()
                    ?? 'The draft could not be created. Review the preview and try again.',
            ]);
        }

        Notification::make()
            ->success()
            ->title('Draft controlled document created')
            ->body('The document remains a Draft and must follow the normal review and approval workflow.')
            ->send();

        $this->redirect(
            ControlledDocumentResource::getUrl('view', ['record' => $document]),
            navigate: true,
        );
    }

    public function resetConversation(): void
    {
        if ($this->draftRequestActive) {
            throw ValidationException::withMessages([
                'userMessage' => 'Wait for the current drafting request to finish before starting over.',
            ]);
        }

        $this->reset([
            'templateId',
            'referencedControlledDocumentId',
            'draftSessionId',
            'draftRequestId',
            'userMessage',
            'expectedPreviewHash',
        ]);
        $this->ownerId = auth()->id();
        unset(
            $this->session,
            $this->messages,
            $this->previewSections,
            $this->draftRequest,
            $this->draftRequestActive,
        );
    }

    /**
     * @return array<int, string>
     */
    #[Computed]
    public function templateOptions(): array
    {
        return DocumentTemplate::query()
            ->whereHas('templateStatus', fn (Builder $query): Builder => $query->where('code', TemplateStatus::PUBLISHED))
            ->whereHas('publishedVersion')
            ->with('documentType')
            ->orderBy('name')
            ->get()
            ->mapWithKeys(fn (DocumentTemplate $template): array => [
                $template->getKey() => "{$template->name} ({$template->documentType->code})",
            ])
            ->all();
    }

    /** @return list<array{id: int, title: string, context: string, status: string, updated_at: string}> */
    #[Computed]
    public function draftHistory(): array
    {
        return ControlledDocumentDraftSession::query()
            ->with(['template.documentType', 'latestDraftRequest'])
            ->where('created_by', auth()->id())
            ->latest('updated_at')
            ->limit(20)
            ->get()
            ->map(function (ControlledDocumentDraftSession $session): array {
                $requestStatus = $session->latestDraftRequest?->status->value;

                return [
                    'id' => (int) $session->getKey(),
                    'title' => $session->title ?: $session->template->name,
                    'context' => $session->template->documentType->code,
                    'status' => str($requestStatus ?: $session->status->value)->replace('_', ' ')->headline()->toString(),
                    'updated_at' => $session->updated_at->diffForHumans(),
                ];
            })
            ->all();
    }

    /**
     * @return array<int, string>
     */
    #[Computed]
    public function ownerOptions(): array
    {
        return User::query()
            ->orderBy('name')
            ->pluck('name', 'id')
            ->all();
    }

    /**
     * @return array<int, string>
     */
    #[Computed]
    public function referenceOptions(): array
    {
        if ($this->templateId === null) {
            return [];
        }

        return app(SopReferenceService::class)->sopSelectOptions($this->templateId);
    }

    #[Computed]
    public function requiresSopReference(): bool
    {
        if ($this->templateId === null) {
            return false;
        }

        return (bool) DocumentTemplate::query()
            ->whereKey($this->templateId)
            ->whereHas('documentType', fn (Builder $query): Builder => $query->where('requires_sop_reference', true))
            ->exists();
    }

    #[Computed]
    public function session(): ?ControlledDocumentDraftSession
    {
        if ($this->draftSessionId === null) {
            return null;
        }

        return ControlledDocumentDraftSession::query()
            ->with(['template.documentType', 'templateVersion.sections', 'templateVersion.variables', 'owner'])
            ->where('created_by', auth()->id())
            ->find($this->draftSessionId);
    }

    #[Computed]
    public function draftRequest(): ?ControlledDocumentDraftRequest
    {
        if ($this->draftRequestId === null || $this->draftSessionId === null) {
            return null;
        }

        return ControlledDocumentDraftRequest::query()
            ->whereKey($this->draftRequestId)
            ->where('controlled_document_draft_session_id', $this->draftSessionId)
            ->where('requested_by', auth()->id())
            ->first();
    }

    #[Computed]
    public function draftRequestActive(): bool
    {
        return $this->draftRequest?->status->isActive() ?? false;
    }

    /**
     * @return list<array{role: string, content: string}>
     */
    #[Computed]
    public function messages(): array
    {
        $conversationId = $this->session?->conversation_id;

        if ($conversationId === null) {
            return [];
        }

        return ConversationMessage::query()
            ->where('conversation_id', $conversationId)
            ->orderBy('created_at')
            ->get()
            ->map(fn (ConversationMessage $message): array => [
                'role' => $message->role,
                'content' => $this->displayMessage($message->content, $message->role),
            ])
            ->all();
    }

    /**
     * @return list<array{title: string, content: string}>
     */
    #[Computed]
    public function previewSections(): array
    {
        $session = $this->session;

        if ($session === null) {
            return [];
        }

        $variables = app(AiDraftVariableNormalizer::class)->normalize(
            $session->templateVersion,
            $session->draft_variables ?? [],
        );
        $resolver = app(VariableResolverService::class);

        try {
            $variables = $resolver->resolveValues($session->templateVersion, $variables)['substitution'];
        } catch (ValidationException) {
            // Legacy previews can contain values that predate AI normalization.
            // Confirmation displays the specific validation problem to the user.
        }

        return $session->templateVersion->sections
            ->map(fn ($section): array => [
                'title' => $section->title,
                'content' => $resolver->replace((string) $section->content, $variables),
            ])
            ->all();
    }

    private function ownedSession(): ControlledDocumentDraftSession
    {
        return ControlledDocumentDraftSession::query()
            ->where('created_by', auth()->id())
            ->findOrFail($this->draftSessionId);
    }

    private function displayMessage(string $content, string $role): string
    {
        if ($role !== 'assistant') {
            return $content;
        }

        $decoded = json_decode($content, true);

        if (
            is_array($decoded)
            && ($decoded['ready_for_preview'] ?? false) === true
            && empty($decoded['missing_details'] ?? [])
        ) {
            return 'The controlled-document preview is ready for review.';
        }

        return is_array($decoded) && filled($decoded['assistant_message'] ?? null)
            ? (string) $decoded['assistant_message']
            : $content;
    }
}
