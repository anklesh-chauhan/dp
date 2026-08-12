<?php

declare(strict_types=1);

namespace App\Filament\Pages;

use App\Domain\DMS\Services\SopReferenceService;
use App\Domain\DMS\Services\VariableResolverService;
use App\Enums\ProductModule;
use App\Filament\Resources\ControlledDocuments\ControlledDocumentResource;
use App\Models\ControlledDocumentDraftSession;
use App\Models\DocumentTemplate;
use App\Models\TemplateStatus;
use App\Models\User;
use App\Services\AI\Actions\CreateControlledDocumentFromAiDraftAction;
use App\Services\AI\ControlledDocumentDraftConversationService;
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
        $this->expectedPreviewHash = null;
        $this->userMessage = '';
        unset($this->session, $this->messages, $this->previewSections);
    }

    public function sendMessage(
        ControlledDocumentDraftConversationService $service,
    ): void {
        $data = $this->validate([
            'userMessage' => ['required', 'string', 'max:10000'],
            'draftSessionId' => ['required', 'integer'],
        ]);

        try {
            $result = $service->respond(
                session: $this->ownedSession(),
                user: auth()->user(),
                message: $data['userMessage'],
            );

            $this->expectedPreviewHash = (string) $result['preview_hash'];
            $this->userMessage = '';
            unset($this->session, $this->messages, $this->previewSections);
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

    public function createDraft(
        CreateControlledDocumentFromAiDraftAction $action,
    ): void {
        $session = $this->ownedSession();

        if (blank($this->expectedPreviewHash)) {
            throw ValidationException::withMessages([
                'confirmation' => 'Review the latest generated preview before creating the draft.',
            ]);
        }

        $document = $action->execute(
            session: $session,
            user: auth()->user(),
            expectedPreviewHash: $this->expectedPreviewHash,
        );

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
        $this->reset([
            'templateId',
            'referencedControlledDocumentId',
            'draftSessionId',
            'userMessage',
            'expectedPreviewHash',
        ]);
        $this->ownerId = auth()->id();
        unset($this->session, $this->messages, $this->previewSections);
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

        $variables = $session->draft_variables ?? [];
        $resolver = app(VariableResolverService::class);

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

        return is_array($decoded) && filled($decoded['assistant_message'] ?? null)
            ? (string) $decoded['assistant_message']
            : $content;
    }
}
