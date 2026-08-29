<?php

declare(strict_types=1);

namespace App\Filament\Pages;

use App\Ai\Agents\QualiGxpAssistantAgent;
use App\Enums\ProductModule;
use App\Models\User;
use App\Services\AI\QualiGxpAssistantService;
use App\Support\Modules\ModuleManager;
use BackedEnum;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Support\Icons\Heroicon;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Contracts\Support\Htmlable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\HtmlString;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Laravel\Ai\Models\Conversation;
use Laravel\Ai\Models\ConversationMessage;
use Livewire\Attributes\Computed;
use Throwable;
use UnitEnum;

final class QualiGxpAssistant extends Page
{
    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedSparkles;

    protected static string|UnitEnum|null $navigationGroup = 'AI Management';

    protected static ?string $navigationLabel = 'QualiGxP Assistant';

    protected static ?string $title = 'QualiGxP Assistant';

    protected static ?string $slug = 'ai-assistant';

    protected static ?int $navigationSort = 0;

    protected static string|array $routeMiddleware = ['module:ai'];

    protected string $view = 'filament.pages.quali-gxp-assistant';

    public string $userMessage = '';

    public string $pendingMessage = '';

    public ?string $conversationId = null;

    public static function canAccess(): bool
    {
        $user = auth()->user();

        return $user instanceof User
            && app(ModuleManager::class)->enabled(ProductModule::AI)
            && $user->can('Use:AiAssistant');
    }

    public function sendMessage(): void
    {
        $data = $this->validate([
            'userMessage' => ['required', 'string', 'max:4000'],
            'conversationId' => ['nullable', 'uuid'],
        ]);

        $this->pendingMessage = $data['userMessage'];
        $this->userMessage = '';
        $this->js('$wire.streamResponse()');
    }

    public function streamResponse(QualiGxpAssistantService $assistant): void
    {
        $data = $this->validate([
            'pendingMessage' => ['required', 'string', 'max:4000'],
            'conversationId' => ['nullable', 'uuid'],
        ]);

        /** @var User $user */
        $user = auth()->user();

        try {
            $result = $assistant->stream(
                $user,
                $data['pendingMessage'],
                fn (string $delta) => $this->stream(
                    to: 'assistant-response',
                    content: e($delta),
                ),
                $data['conversationId'],
            );
            $this->conversationId = $result['conversation_id'];
            unset($this->messages, $this->conversationHistory);
        } catch (ValidationException|AuthorizationException $exception) {
            throw $exception;
        } catch (Throwable $exception) {
            report($exception);

            Notification::make()
                ->danger()
                ->title('The AI assistant could not respond')
                ->body('Please try again. If the problem continues, contact an administrator.')
                ->send();
        } finally {
            $this->pendingMessage = '';
        }
    }

    public function startNewConversation(): void
    {
        $this->reset(['userMessage', 'pendingMessage', 'conversationId']);
        unset($this->messages);
    }

    public function openConversation(string $conversationId): void
    {
        /** @var User $user */
        $user = auth()->user();
        $conversation = $this->ownedAssistantConversations($user)
            ->whereKey($conversationId)
            ->first();

        if ($conversation === null) {
            throw new AuthorizationException;
        }

        $this->conversationId = (string) $conversation->getKey();
        $this->reset(['userMessage', 'pendingMessage']);
        unset($this->messages);
    }

    /** @return list<array{id: string, title: string, updated_at: string}> */
    #[Computed]
    public function conversationHistory(): array
    {
        /** @var User $user */
        $user = auth()->user();
        $conversations = $this->ownedAssistantConversations($user)
            ->latest('updated_at')
            ->limit(20)
            ->get(['id', 'title', 'updated_at']);

        $firstMessages = ConversationMessage::query()
            ->whereIn('conversation_id', $conversations->modelKeys())
            ->where('agent', QualiGxpAssistantAgent::class)
            ->where('role', 'user')
            ->oldest('created_at')
            ->get(['conversation_id', 'content'])
            ->unique('conversation_id')
            ->keyBy('conversation_id');

        return $conversations
            ->map(function (Conversation $conversation) use ($firstMessages): array {
                $firstMessage = $firstMessages->get($conversation->getKey());
                $title = filled($conversation->title)
                    ? $conversation->title
                    : $firstMessage?->content;

                return [
                    'id' => (string) $conversation->getKey(),
                    'title' => Str::limit((string) ($title ?: 'New conversation'), 64),
                    'updated_at' => $conversation->updated_at->diffForHumans(),
                ];
            })
            ->all();
    }

    /** @return list<array{role: string, content: string}> */
    #[Computed]
    public function messages(): array
    {
        if ($this->conversationId === null) {
            return [];
        }

        /** @var User $user */
        $user = auth()->user();
        $conversation = Conversation::query()
            ->whereKey($this->conversationId)
            ->where('participant_type', $user->getMorphClass())
            ->where('participant_id', $user->getKey())
            ->first();

        if ($conversation === null) {
            return [];
        }

        return ConversationMessage::query()
            ->where('conversation_id', $conversation->getKey())
            ->whereIn('role', ['user', 'assistant'])
            ->orderBy('created_at')
            ->get()
            ->map(fn (ConversationMessage $message): array => [
                'role' => $message->role,
                'content' => $message->content,
            ])
            ->all();
    }

    public function renderMessage(string $message): Htmlable
    {
        return new HtmlString(Str::markdown($message, [
            'html_input' => 'strip',
            'allow_unsafe_links' => false,
        ]));
    }

    private function ownedAssistantConversations(User $user): Builder
    {
        return Conversation::query()
            ->where('participant_type', $user->getMorphClass())
            ->where('participant_id', $user->getKey())
            ->whereHas('messages', fn (Builder $query): Builder => $query
                ->where('agent', QualiGxpAssistantAgent::class));
    }
}
