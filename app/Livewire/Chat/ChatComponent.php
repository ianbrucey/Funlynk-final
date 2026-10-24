<?php

namespace App\Livewire\Chat;

use App\Events\ConversationUpdated;
use App\Models\Conversation;
use App\Models\Message;
use App\Services\ChatNotifier;
use App\Services\ChatService;
use Illuminate\Support\Facades\RateLimiter;
use Livewire\Attributes\Locked;
use Livewire\Component;

class ChatComponent extends Component
{
    private const PAGE_SIZE = 50;

    #[Locked]
    public $conversationId;

    public $conversationable; // Post or Activity model

    public $conversation; // Full conversation model

    public $newMessage = '';

    /** @var array<int, array<string, mixed>> */
    public $messages = [];

    public $replyingTo = null;

    /** @var array<int, string> */
    public array $mentionIds = [];

    #[Locked]
    public string $notifyLevel = 'all';

    #[Locked]
    public ?string $focusId = null;

    public bool $showHeader = true;

    public int $limit = self::PAGE_SIZE;

    public bool $hasEarlier = false;

    #[Locked]
    public ?string $firstUnreadId = null;

    #[Locked]
    public ?string $pinnedMessageId = null;

    /** @var array<string, mixed>|null */
    public ?array $pinned = null;

    protected ChatService $chatService;

    public function getListeners()
    {
        if (! $this->conversationId) {
            return [];
        }

        return [
            "echo-private:conversation.{$this->conversationId},MessageSent" => 'onMessageReceived',
            "echo-private:conversation.{$this->conversationId},ConversationUpdated" => 'refreshThread',
        ];
    }

    public function boot(ChatService $chatService)
    {
        $this->chatService = $chatService;
    }

    public function mount($conversationId = null, $conversationable = null, bool $showHeader = true)
    {
        $this->conversationId = $conversationId;
        $this->conversationable = $conversationable;
        $this->showHeader = $showHeader;

        $this->focusId = is_string(request()->query('m')) ? request()->query('m') : null;

        $this->loadMessages(firstLoad: true);
    }

    protected function loadMessages(bool $firstLoad = false)
    {
        if ($this->conversationable) {
            $conversation = $this->chatService->getOrCreateConversation($this->conversationable);
            $this->conversationId = $conversation->id;
        }

        if (! $this->conversationId) {
            $this->messages = [];
            $this->conversation = null;

            return;
        }

        $conversation = Conversation::query()->with(['participants'])->find((string) $this->conversationId);

        if (! $conversation) {
            $this->messages = [];
            $this->conversation = null;

            return;
        }

        $this->authorize('view', $conversation);

        $this->conversation = $conversation;
        $viewerId = auth()->id() !== null ? (string) auth()->id() : null;

        $rows = $this->chatService->getMessages($conversation, $this->limit + 1);
        $this->hasEarlier = $rows->count() > $this->limit;
        $rows = $this->hasEarlier ? $rows->slice(1)->values() : $rows;

        if ($firstLoad && $viewerId) {
            $this->firstUnreadId = $this->findFirstUnreadId($conversation, $rows, $viewerId);
        }

        $this->messages = $rows->map(fn (Message $m) => $this->chatService->messagePayload($m, $viewerId))->all();

        $this->pinnedMessageId = $conversation->pinned_message_id;
        $pinnedMessage = $this->pinnedMessageId ? Message::with('user')->find($this->pinnedMessageId) : null;
        $this->pinned = $pinnedMessage ? [
            'id' => $pinnedMessage->id,
            'body' => $pinnedMessage->body,
            'user' => $pinnedMessage->user->display_name ?? $pinnedMessage->user->username,
        ] : null;

        if ($viewerId) {
            $this->chatService->markAsRead($conversation, auth()->user());
            $this->notifyLevel = app(ChatNotifier::class)->levelFor($conversation, auth()->user());
        }
    }

    /**
     * First message from someone else sent after the viewer last read the chat.
     */
    /**
     * @param  \Illuminate\Support\Collection<int, Message>  $rows
     */
    protected function findFirstUnreadId(Conversation $conversation, $rows, string $viewerId): ?string
    {
        $lastRead = \App\Models\ConversationParticipant::query()
            ->where('conversation_id', $conversation->id)
            ->where('user_id', $viewerId)
            ->value('last_read_at');

        if ($lastRead === null) {
            return null;
        }

        return $rows->first(fn (Message $m) => $m->user_id !== $viewerId && $m->created_at->gt($lastRead))?->id;
    }

    public function sendMessage()
    {
        if (trim($this->newMessage) === '') {
            return;
        }

        if (! auth()->check()) {
            return;
        }

        $conversation = Conversation::query()->find((string) $this->conversationId);

        if (! $conversation) {
            return;
        }

        $this->authorize('sendMessage', $conversation);

        $this->validate(['newMessage' => ['required', 'string', 'max:2000']], [
            'newMessage.max' => 'Messages can be up to 2000 characters.',
        ]);

        if (! RateLimiter::attempt('chat-send:'.auth()->id(), 30, fn () => true, 60)) {
            $this->addError('newMessage', 'You are sending messages too fast. Wait a moment.');

            return;
        }

        $message = $this->chatService->sendMessage(
            $conversation,
            auth()->user(),
            $this->newMessage,
            $this->replyingTo['id'] ?? null,
            $this->chatService->resolveMentions($conversation, $this->newMessage, $this->mentionIds)
        );
        $this->mentionIds = [];

        $this->messages[] = $this->chatService->messagePayload($message, (string) auth()->id());
        $this->firstUnreadId = null;

        $this->newMessage = '';
        $this->replyingTo = null;
        $this->dispatch('message-sent');
    }

    public function loadEarlier(): void
    {
        $this->limit += self::PAGE_SIZE;
        $this->loadMessages();
    }

    public function refreshThread(): void
    {
        $this->loadMessages();
    }

    public function replyTo($messageId)
    {
        $message = collect($this->messages)->firstWhere('id', $messageId);
        if ($message) {
            $this->replyingTo = [
                'id' => $message['id'],
                'body' => $message['body'],
                'user' => $message['user'],
            ];
        }
    }

    public function reactToMessage(string $messageId, string $emoji): void
    {
        $conversation = Conversation::query()->findOrFail((string) $this->conversationId);
        $this->authorize('view', $conversation);

        $message = Message::query()->where('conversation_id', $conversation->id)->findOrFail($messageId);
        $this->chatService->reactToMessage($message, auth()->user(), $emoji);

        $this->loadMessages();
        broadcast(new ConversationUpdated($conversation->id))->toOthers();
    }

    public function pinMessage(?string $messageId): void
    {
        $conversation = Conversation::query()->findOrFail((string) $this->conversationId);
        $this->authorize('pin', $conversation);

        $message = $messageId ? Message::query()->where('conversation_id', $conversation->id)->findOrFail($messageId) : null;
        $this->chatService->pinMessage($conversation, $message, auth()->user());

        $this->loadMessages();
        broadcast(new ConversationUpdated($conversation->id))->toOthers();
    }

    public function onMessageReceived($event)
    {
        if (collect($this->messages)->contains('id', $event['id'])) {
            return;
        }

        $event['is_mine'] = false;
        $event['reactions'] = $event['reactions'] ?? [];
        $event['mentions'] = $event['mentions'] ?? [];
        $this->messages[] = $event;

        if (auth()->check() && $this->conversation) {
            $this->chatService->markAsRead($this->conversation, auth()->user());
        }

        $this->dispatch('message-received');
    }

    public function setNotifyLevel(string $level): void
    {
        $conversation = Conversation::query()->findOrFail((string) $this->conversationId);
        $this->authorize('sendMessage', $conversation);

        $this->chatService->setNotifyLevel($conversation, auth()->user(), $level);
        $this->notifyLevel = $level;
    }

    public function cancelReply()
    {
        $this->replyingTo = null;
    }

    public function render()
    {
        $conversation = $this->conversation;

        return view('livewire.chat.chat-component', [
            'canSend' => $conversation && auth()->check() && auth()->user()->can('sendMessage', $conversation),
            'canPin' => $conversation && auth()->check() && auth()->user()->can('pin', $conversation),
            'reactionChoices' => ChatService::REACTIONS,
            'mentionable' => $conversation && auth()->check()
                ? $conversation->participants->where('id', '!=', auth()->id())->map(fn ($p) => [
                    'id' => (string) $p->id,
                    'name' => $p->display_name ?? $p->username,
                    'handle' => $this->chatService->mentionHandle($p),
                ])->values()->all()
                : [],
        ]);
    }
}
