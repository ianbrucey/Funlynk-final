<div>
    <!-- Flash Messages -->
    @if (session()->has('success'))
        <div class="alert alert-success mb-4 p-4 rounded-xl bg-green-500/20 text-green-300">
            {{ session('success') }}
        </div>
    @endif

    @if (session()->has('error'))
        <div class="alert alert-error mb-4 p-4 rounded-xl bg-red-500/20 text-red-300">
            {{ session('error') }}
        </div>
    @endif

    <!-- Header with Stats -->
    <div class="flex flex-col md:flex-row justify-between items-start md:items-center gap-4 mb-6 p-3">
        <div>
            <h3 class="text-xl font-bold p-3">Join Requests</h3>
            @if($pendingCount > 0)
                <span class="text-cyan-400 text-sm">{{ $pendingCount }} pending request{{ $pendingCount > 1 ? 's' : '' }}</span>
            @else
                <span class="text-gray-500 text-sm">No pending requests</span>
            @endif
        </div>

        <!-- Filters -->
        <div class="flex flex-col sm:flex-row gap-3 w-full md:w-auto">
            <input type="text" wire:model.live.debounce.300ms="search" placeholder="Search by name..."
                class="rounded-xl bg-slate-800/50 border border-white/10 text-white px-4 py-2 focus:border-cyan-500 focus:ring-cyan-500 transition w-full sm:w-48">

            <select wire:model.live="statusFilter"
                class="rounded-xl bg-slate-800/50 border border-white/10 text-white px-4 py-2 focus:border-cyan-500 focus:ring-cyan-500 transition">
                <option value="pending">Pending</option>
                <option value="approved">Approved</option>
                <option value="denied">Denied</option>
                <option value="all">All</option>
            </select>
        </div>
    </div>

    <!-- Requests List -->
    @if($requests->count() > 0)
        <div class="space-y-4">
            @foreach($requests as $request)
                <div class="relative p-4 glass-card rounded-xl flex flex-col sm:flex-row items-start sm:items-center justify-between gap-4">
                    <!-- User Info -->
                    <div class="flex items-center gap-4">
                        @if($request->user->profile_image_url)
                            <img src="{{ Storage::url($request->user->profile_image_url) }}"
                                 alt="{{ $request->user->name ?: $request->user->username }}"
                                 class="w-12 h-12 rounded-full object-cover border-2 border-purple-500/50">
                        @else
                            <div class="w-12 h-12 rounded-full bg-gradient-to-br from-pink-500 to-purple-500 flex items-center justify-center text-white font-bold">
                                {{ strtoupper(substr($request->user->name ?: $request->user->username, 0, 1)) }}
                            </div>
                        @endif
                        <div>
                            <p class="font-semibold text-white">{{ $request->user->name ?: $request->user->username }}</p>
                            <p class="text-gray-400 text-sm">{{ '@' . $request->user->username }}</p>
                            <p class="text-gray-500 text-xs">Requested {{ $request->created_at->diffForHumans() }}</p>
                        </div>
                    </div>

                    <!-- Status / Actions -->
                    <div class="flex items-center gap-3">
                        @if($request->status === 'pending')
                            <button wire:click="approveRequest('{{ $request->id }}')"
                                wire:loading.attr="disabled"
                                class="px-4 py-2 bg-gradient-to-r from-green-500 to-emerald-500 rounded-xl font-semibold hover:scale-105 transition-all text-sm">
                                <span wire:loading.remove wire:target="approveRequest('{{ $request->id }}')">Approve</span>
                                <span wire:loading wire:target="approveRequest('{{ $request->id }}')">...</span>
                            </button>
                            <button wire:click="denyRequest('{{ $request->id }}')"
                                wire:loading.attr="disabled"
                                class="px-4 py-2 bg-red-600/50 border border-red-500/30 rounded-xl font-semibold hover:bg-red-600 transition text-sm">
                                <span wire:loading.remove wire:target="denyRequest('{{ $request->id }}')">Deny</span>
                                <span wire:loading wire:target="denyRequest('{{ $request->id }}')">...</span>
                            </button>
                        @elseif($request->status === 'approved')
                            <span class="px-3 py-1 bg-green-500/20 text-green-400 rounded-full text-sm">Approved</span>
                        @elseif($request->status === 'denied')
                            <span class="px-3 py-1 bg-red-500/20 text-red-400 rounded-full text-sm">Denied</span>
                        @endif
                    </div>
                </div>
            @endforeach
        </div>

        <!-- Pagination -->
        <div class="mt-6">
            {{ $requests->links() }}
        </div>
    @else
        <div class="text-center py-12">
            <div class="text-gray-500 text-6xl mb-4">📭</div>
            <p class="text-gray-400">
                @if($statusFilter === 'pending')
                    No pending join requests
                @elseif($statusFilter === 'approved')
                    No approved requests
                @elseif($statusFilter === 'denied')
                    No denied requests
                @else
                    No join requests found
                @endif
            </p>
        </div>
    @endif
</div>
