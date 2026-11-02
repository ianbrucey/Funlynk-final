<x-galaxy-layout>
    <x-slot name="title">Poster — Account Connected</x-slot>

    <div class="container mx-auto px-6 py-8 max-w-2xl">
        <div class="relative p-8 glass-card">
            <div class="top-accent-center"></div>

            <h1 class="text-3xl font-bold text-white mb-4">Account connected</h1>
            <p class="text-gray-300 mb-6">
                The following platform account(s) are now connected to the Funlynk Poster.
                Tokens are stored encrypted and are never shown here.
            </p>

            <ul class="space-y-3 text-gray-200">
                @foreach ($accounts as $account)
                    <li class="p-4 bg-slate-800/50 border border-white/10 rounded-lg">
                        <strong>{{ $account->platform->label() }}</strong>
                        @if ($account->handle)
                            — {{ $account->handle }}
                        @endif
                        <br>
                        <span class="text-sm text-gray-400">Account id: {{ $account->external_id }} · Status: {{ $account->status }}</span>
                    </li>
                @endforeach
            </ul>

            @if (! empty($note))
                <p class="mt-6 p-4 bg-yellow-500/10 border border-yellow-500/30 rounded-lg text-yellow-200">{{ $note }}</p>
            @endif
        </div>
    </div>
</x-galaxy-layout>
