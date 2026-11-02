<x-galaxy-layout>
    <x-slot name="title">Poster — Connection Problem</x-slot>

    <div class="container mx-auto px-6 py-8 max-w-2xl">
        <div class="relative p-8 glass-card">
            <div class="top-accent-center"></div>

            <h1 class="text-3xl font-bold text-white mb-4">Something went wrong</h1>
            <p class="p-4 bg-red-500/10 border border-red-500/30 rounded-lg text-red-200">{{ $message }}</p>
            <p class="text-gray-400 mt-6">No account was connected. You can close this tab and start the connect flow again.</p>
        </div>
    </div>
</x-galaxy-layout>
