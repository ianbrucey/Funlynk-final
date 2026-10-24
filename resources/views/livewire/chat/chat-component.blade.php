@php
    $viewer = auth()->user();
    $uid = $this->getId();
    $subject = $conversation?->conversationable;
@endphp
<div class="flex h-full min-h-0 flex-col overflow-hidden rounded-2xl border border-white/10 bg-slate-900/50 backdrop-blur-lg"
     x-data="{
        draft: '', pending: [], sheet: null, sending: false,
        thread() { return this.$refs.thread; },
        toEnd() { this.$nextTick(() => { const t = this.thread(); if (t) t.scrollTop = t.scrollHeight; }); },
        jump(id) { const el = document.getElementById('msg-{{ $uid }}-' + id); if (!el) return; el.scrollIntoView({ block: 'center', behavior: 'smooth' }); el.classList.add('bg-cyan-400/20'); setTimeout(() => el.classList.remove('bg-cyan-400/20'), 1200); },
        grow(el) { el.style.height = 'auto'; el.style.height = Math.min(el.scrollHeight, 110) + 'px'; },
        async send() {
            const text = this.draft.trim(); if (!text || this.sending) return;
            this.sending = true; this.pending.push({ text, failed: false }); this.draft = ''; this.$nextTick(() => this.grow(this.$refs.box)); this.toEnd();
            $wire.newMessage = text;
            try { await $wire.sendMessage(); } catch (e) { $wire.newMessage = ''; this.pending.at(-1).failed = true; this.sending = false; return; }
            if ($wire.newMessage !== '') { this.draft = text; $wire.newMessage = ''; this.pending.pop(); } else { this.pending.pop(); }
            this.sending = false; this.toEnd();
        },
     }"
     x-init="toEnd()"
     x-on:message-received.window="toEnd()"
     x-on:keydown.escape.window="sheet = null">

    @if($showHeader)
        <div class="flex-none border-b border-white/10 bg-gradient-to-r from-slate-800/50 to-slate-900/50 px-4 py-3">
            @if($conversation && $conversation->type === 'private')
                @php $otherUser = $conversation->participants->where('id', '!=', auth()->id())->first(); @endphp
                <div class="flex items-center gap-3">
                    <x-fl.avatar :name="$otherUser->display_name ?? $otherUser->username" :src="$otherUser->profile_image_url" class="h-10 w-10 border-2 border-white/20" />
                    <div class="min-w-0">
                        <h3 class="truncate text-base font-semibold text-white">{{ $otherUser->display_name ?? $otherUser->username }}</h3>
                        <p class="text-xs text-gray-400">{{ '@'.$otherUser->username }}</p>
                    </div>
                </div>
            @else
                <h3 class="truncate text-base font-semibold text-white">{{ $subject?->title ?? 'Group Chat' }}</h3>
                <p class="text-xs text-gray-400">{{ $conversation ? $conversation->participants->count() : 0 }} {{ \Illuminate\Support\Str::plural('person', $conversation ? $conversation->participants->count() : 0) }} in this chat</p>
            @endif
        </div>
    @endif

    @if($pinned)
        <button type="button" x-on:click="jump('{{ $pinned['id'] }}')" class="flex min-h-[44px] flex-none items-center gap-2 border-b border-cyan-400/25 bg-cyan-400/10 px-4 py-2 text-left text-xs text-cyan-200">
            <span aria-hidden="true">📌</span><span class="flex-1 truncate">{{ $pinned['body'] }}</span>
        </button>
    @endif

    <div x-ref="thread" class="min-h-0 flex-1 overflow-y-auto px-3 py-3" role="log" aria-live="polite">
        @if($hasEarlier)
            <button type="button" wire:click="loadEarlier" wire:loading.attr="disabled" wire:target="loadEarlier"
                    class="mx-auto mb-3 block min-h-[44px] rounded-full border border-white/10 bg-slate-800/60 px-5 text-xs text-slate-300 hover:border-cyan-400/40">
                <span wire:loading.remove wire:target="loadEarlier">Load earlier messages</span><span wire:loading wire:target="loadEarlier">Loading…</span>
            </button>
        @endif

        @php $prev = null; @endphp
        @forelse($messages as $message)
            @php
                $at = \Illuminate\Support\Carbon::parse($message['created_at'])->setTimezone(config('app.timezone'));
                $newDay = ! $prev || $prev['at']->toDateString() !== $at->toDateString();
                $first = $newDay || $prev['user_id'] !== $message['user_id'] || $at->diffInMinutes($prev['at']) > 5 || $firstUnreadId === $message['id'];
                $name = $message['user']['display_name'];
                $dayLabel = $at->isToday() ? 'Today' : ($at->isYesterday() ? 'Yesterday' : $at->format('M j'));
            @endphp
            @if($newDay)
                <div class="my-3 flex items-center gap-3 text-[0.7rem] text-gray-400"><span class="h-px flex-1 bg-white/10"></span>{{ $dayLabel }}<span class="h-px flex-1 bg-white/10"></span></div>
            @endif
            @if($firstUnreadId === $message['id'])
                <div class="my-3 flex items-center gap-3 text-[0.7rem] text-fuchsia-300"><span class="h-px flex-1 bg-fuchsia-300/40"></span>New messages<span class="h-px flex-1 bg-fuchsia-300/40"></span></div>
            @endif
            <div wire:key="m-{{ $message['id'] }}" id="msg-{{ $uid }}-{{ $message['id'] }}" class="flex gap-2 rounded-lg transition-colors duration-700 {{ $first ? 'mt-3' : 'mt-0.5' }} {{ $message['is_mine'] ? 'flex-row-reverse' : '' }}">
                @unless($message['is_mine'])
                    <div class="w-8 flex-none">@if($first)<x-fl.avatar :name="$name" :src="$message['user']['profile_image_url']" class="h-8 w-8 text-sm" />@endif</div>
                @endunless
                <div class="flex max-w-[82%] flex-col {{ $message['is_mine'] ? 'items-end' : 'items-start' }}">
                    @if($first && ! $message['is_mine'])<div class="mb-0.5 px-1 text-xs text-gray-400">{{ $name }}</div>@endif
                    @if($message['reply_to'])
                        <button type="button" x-on:click="jump('{{ $message['reply_to']['id'] }}')" class="mb-1 max-w-full rounded-lg border-l-2 border-cyan-400 bg-white/5 px-2.5 py-1.5 text-left text-xs text-gray-400">
                            <span class="block font-semibold text-cyan-300">{{ $message['reply_to']['user']['display_name'] }}</span><span class="block truncate">{{ $message['reply_to']['body'] }}</span>
                        </button>
                    @endif
                    <button type="button" x-on:click="sheet = '{{ $message['id'] }}'" aria-label="Message options"
                            class="break-words rounded-2xl px-3 py-2 text-left text-sm leading-snug {{ $message['is_mine'] ? 'bg-gradient-to-r from-pink-500 to-purple-500 text-white' : 'border border-white/10 bg-slate-700/60 text-gray-100' }}">{{ $message['body'] }}</button>
                    @if(! empty($message['reactions']))
                        <div class="mt-1 flex flex-wrap gap-1">
                            @foreach($message['reactions'] as $emoji => $info)
                                <button type="button" wire:click="reactToMessage('{{ $message['id'] }}', '{{ $emoji }}')" wire:key="rx-{{ $message['id'] }}-{{ $emoji }}"
                                        class="min-h-[28px] rounded-full border px-2 text-xs {{ $info['mine'] ? 'border-cyan-400 bg-cyan-400/15' : 'border-white/10 bg-white/5' }}">{{ $emoji }} {{ $info['count'] }}</button>
                            @endforeach
                        </div>
                    @endif
                    <div class="mt-0.5 px-1 text-[0.65rem] text-gray-500">{{ $at->format('g:i A') }}</div>
                </div>
            </div>
            @php $prev = ['user_id' => $message['user_id'], 'at' => $at]; @endphp
        @empty
            <div class="px-6 py-14 text-center"><div class="text-4xl" aria-hidden="true">💬</div><p class="mt-2 text-sm font-semibold text-white">No messages yet</p><p class="text-xs text-gray-400">Say hi and start planning.</p></div>
        @endforelse

        <template x-for="(p, i) in pending" :key="i">
            <div class="mt-1 flex flex-row-reverse"><div class="flex max-w-[82%] flex-col items-end">
                <div class="break-words rounded-2xl px-3 py-2 text-sm text-white" :class="p.failed ? 'border border-red-400/50 bg-red-400/20' : 'bg-gradient-to-r from-pink-500 to-purple-500 opacity-60'" x-text="p.text"></div>
                <div class="mt-0.5 px-1 text-[0.65rem]" :class="p.failed ? 'text-red-300' : 'text-gray-500'" x-text="p.failed ? 'Not sent' : 'Sending…'"></div>
            </div></div>
        </template>
    </div>

    @if($canSend)
        <div class="flex-none border-t border-white/10 bg-slate-900/60 px-2.5 pb-2.5 pt-2">
            @if($replyingTo)
                <div class="flex items-center gap-2 px-1.5 pb-1 text-xs text-gray-400">↩ Replying to <span class="font-semibold text-cyan-300">{{ $replyingTo['user']['display_name'] }}</span>
                    <span class="min-w-0 flex-1 truncate">{{ $replyingTo['body'] }}</span>
                    <button type="button" wire:click="cancelReply" aria-label="Cancel reply" class="min-h-[44px] min-w-[44px] text-gray-400 hover:text-white">✕</button></div>
            @endif
            @error('newMessage')<p class="px-1.5 pb-1 text-xs text-red-300">{{ $message }}</p>@enderror
            <div class="flex items-end gap-2">
                <textarea x-ref="box" x-model="draft" x-on:input="grow($el)" x-on:keydown.enter="if (! $event.shiftKey) { $event.preventDefault(); send(); }" rows="1" maxlength="2000"
                          name="message" aria-label="Message" placeholder="Message the group"
                          class="min-h-[44px] max-h-[110px] flex-1 resize-none rounded-2xl border border-white/10 bg-black/35 px-3.5 py-2.5 text-sm text-white placeholder-gray-500 focus:border-cyan-400 focus:outline-none focus:ring-2 focus:ring-cyan-400/25"></textarea>
                <button type="button" x-on:click="send()" x-bind:disabled="! draft.trim()" aria-label="Send"
                        class="h-11 w-11 flex-none rounded-2xl bg-gradient-to-r from-pink-500 to-purple-500 text-white transition disabled:opacity-40">
                    <svg class="mx-auto h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 19l9 2-9-18-9 18 9-2zm0 0v-8"/></svg>
                </button>
            </div>
        </div>
    @else
        <div class="flex-none border-t border-white/10 px-4 py-3 text-center text-sm text-slate-300">
            @if($conversation && $conversation->group_id === null && $subject instanceof \App\Models\Post && $subject->group_id)
                Only members of {{ $subject->group?->name ?? 'this group' }} can send messages here.
                @if($subject->group) <a href="{{ route('groups.show', $subject->group->slug) }}" wire:navigate class="mt-2 flex min-h-[44px] items-center justify-center rounded-xl bg-gradient-to-r from-pink-500 to-purple-500 font-semibold text-white">Go to group</a> @endif
            @else
                You can read this chat but not send messages.
            @endif
        </div>
    @endif

    <div x-show="sheet" x-cloak class="fixed inset-0 z-50 flex items-end justify-center bg-black/55 p-0 md:items-center md:p-4" x-on:click.self="sheet = null" role="dialog" aria-modal="true">
        <div class="w-full max-w-md rounded-t-2xl border border-white/15 bg-[#14102b] p-3.5 md:rounded-2xl">
            @if($canSend)
                <div class="mb-2 flex gap-1.5">
                    @foreach($reactionChoices as $emoji)
                        <button type="button" x-on:click="$wire.reactToMessage(sheet, '{{ $emoji }}'); sheet = null" class="min-h-[48px] flex-1 rounded-xl border border-white/10 bg-white/5 text-xl">{{ $emoji }}</button>
                    @endforeach
                </div>
                <button type="button" x-on:click="$wire.replyTo(sheet); sheet = null; $nextTick(() => $refs.box.focus())" class="flex min-h-[44px] w-full items-center rounded-xl px-3 text-left text-sm text-gray-100 hover:bg-white/5">↩ Reply</button>
            @endif
            @if($canPin)
                <button type="button" x-on:click="$wire.pinMessage(sheet === '{{ $pinnedMessageId }}' ? null : sheet); sheet = null" class="flex min-h-[44px] w-full items-center rounded-xl px-3 text-left text-sm text-gray-100 hover:bg-white/5">📌 Pin or unpin message</button>
            @endif
            <button type="button" x-on:click="sheet = null" class="mt-1 flex min-h-[44px] w-full items-center rounded-xl px-3 text-left text-sm text-gray-400 hover:bg-white/5">Close</button>
        </div>
    </div>
</div>
