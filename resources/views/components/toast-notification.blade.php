{{-- Toast Notification Component --}}
<div x-data="{
        show: false,
        message: '',
        type: 'success',
        timeout: null
    }"
     @show-toast.window="
        message = $event.detail.message || $event.detail[0].message;
        type = $event.detail.type || $event.detail[0].type || 'success';
        show = true;
        clearTimeout(timeout);
        timeout = setTimeout(() => show = false, 5000);
     "
     x-show="show"
     x-transition:enter="transition ease-out duration-300"
     x-transition:enter-start="opacity-0 transform translate-x-full"
     x-transition:enter-end="opacity-100 transform translate-x-0"
     x-transition:leave="transition ease-in duration-300"
     x-transition:leave-start="opacity-100 transform translate-x-0"
     x-transition:leave-end="opacity-0 transform translate-x-full"
     class="fixed top-4 right-4 z-50 glass-card border rounded-xl p-4 max-w-sm shadow-2xl"
     :class="type === 'success' ? 'border-green-500/30' : 'border-red-500/30'"
     style="display: none;">
    <div class="flex items-center gap-3">
        <div class="text-2xl" x-text="type === 'success' ? '✅' : '❌'"></div>
        <p class="text-white font-medium" x-text="message"></p>
        <button @click="show = false" class="ml-auto text-gray-400 hover:text-white transition">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
            </svg>
        </button>
    </div>
</div>

{{-- Session Flash Messages (for page loads) --}}
@if (session()->has('success') || session()->has('error'))
    <div x-data="{ show: true }"
         x-show="show"
         x-init="setTimeout(() => show = false, 5000)"
         x-transition:enter="transition ease-out duration-300"
         x-transition:enter-start="opacity-0 transform translate-x-full"
         x-transition:enter-end="opacity-100 transform translate-x-0"
         x-transition:leave="transition ease-in duration-300"
         x-transition:leave-start="opacity-100 transform translate-x-0"
         x-transition:leave-end="opacity-0 transform translate-x-full"
         class="fixed top-20 right-4 z-50 glass-card border {{ session()->has('success') ? 'border-green-500/30' : 'border-red-500/30' }} rounded-xl p-4 max-w-sm shadow-2xl"
         style="display: none;">
        <div class="flex items-center gap-3">
            <div class="text-2xl">
                @if(session()->has('success'))
                    ✅
                @else
                    ❌
                @endif
            </div>
            <p class="text-white font-medium">
                {{ session('success') ?? session('error') }}
            </p>
            <button @click="show = false" class="ml-auto text-gray-400 hover:text-white transition">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                </svg>
            </button>
        </div>
    </div>
@endif

