# Event Edit Protection - UI Components

## Component Overview

```
Host Side:
├── EditActivity (existing) - Modified with field indicators & warnings
├── EditWarningModal (new) - Pre-edit confirmation for paid events
└── EditReasonInput (new) - Optional reason field for significant changes

Attendee Side:
├── EventChangeNotification (new) - In-app notification component
├── EventChangeModal (new) - Full change details with accept/refund buttons
├── EventAuditTrail (new) - History of all changes
└── RefundWindowBanner (new) - Persistent banner on event detail page
```

## Host Components

### 1. Edit Warning Modal
Shown when host clicks "Edit" on event with paid attendees.

```blade
<!-- Triggered before loading edit form -->
<div class="glass-card p-6 max-w-md">
    <div class="text-yellow-400 text-4xl mb-4">⚠️</div>
    <h3 class="text-xl font-bold text-white mb-2">Edit Paid Event</h3>
    <p class="text-gray-400 mb-4">
        This event has <span class="text-cyan-400 font-bold">{{ $paidCount }}</span> 
        paid attendees.
    </p>
    
    <div class="space-y-2 text-sm text-gray-300 mb-6">
        <div class="flex items-center gap-2">
            <span class="w-3 h-3 rounded-full bg-green-500"></span>
            Some fields can be edited freely
        </div>
        <div class="flex items-center gap-2">
            <span class="w-3 h-3 rounded-full bg-yellow-500"></span>
            Some changes will notify attendees
        </div>
        <div class="flex items-center gap-2">
            <span class="w-3 h-3 rounded-full bg-red-500"></span>
            Major changes give attendees a 72hr refund window
        </div>
        <div class="flex items-center gap-2">
            <span class="w-3 h-3 rounded-full bg-gray-500"></span>
            Price cannot be increased
        </div>
    </div>
    
    <div class="flex gap-3">
        <button @click="$dispatch('close')" class="secondary-btn flex-1">Cancel</button>
        <button @click="proceedToEdit" class="primary-btn flex-1">Continue to Edit</button>
    </div>
</div>
```

### 2. Field Permission Indicators (in Edit Form)
Each field shows its restriction level.

```blade
<!-- Example: Title field with refund-window indicator -->
<div class="space-y-2">
    <div class="flex items-center justify-between">
        <label class="text-white font-medium">Event Title</label>
        @if($isLocked)
            <span class="text-xs px-2 py-1 rounded-full bg-red-500/20 text-red-400 flex items-center gap-1">
                <svg class="w-3 h-3"><!-- warning icon --></svg>
                Major changes trigger refund window
            </span>
        @endif
    </div>
    <input type="text" wire:model="title" ... />
    @if($titleChangeCategory === 'significant')
        <p class="text-yellow-400 text-sm">
            ⚠️ This change is significant (>30% different). 
            Attendees will have 72hrs to request a refund.
        </p>
    @endif
</div>

<!-- Example: Price field locked -->
<div class="space-y-2">
    <div class="flex items-center justify-between">
        <label class="text-white font-medium">Ticket Price</label>
        <span class="text-xs px-2 py-1 rounded-full bg-gray-500/20 text-gray-400 flex items-center gap-1">
            <svg class="w-3 h-3"><!-- lock icon --></svg>
            Cannot increase after sales
        </span>
    </div>
    <input type="number" wire:model="price" 
           @if($isLocked && $price >= $originalPrice) max="{{ $originalPrice }}" @endif />
    @if($isLocked)
        <p class="text-gray-500 text-sm">
            Original price: ${{ $originalPrice }}. You can only decrease.
        </p>
    @endif
</div>
```

### 3. Edit Confirmation with Reason
Shown before submitting significant changes.

```blade
<div class="glass-card p-6">
    <h3 class="text-lg font-bold text-white mb-4">Confirm Changes</h3>
    
    <div class="space-y-3 mb-4">
        @foreach($significantChanges as $change)
            <div class="p-3 bg-red-500/10 border border-red-500/30 rounded-lg">
                <div class="text-red-400 font-medium">{{ $change['field'] }}</div>
                <div class="text-sm text-gray-400">
                    {{ $change['old'] }} → {{ $change['new'] }}
                </div>
            </div>
        @endforeach
    </div>
    
    <div class="p-4 bg-yellow-500/10 border border-yellow-500/30 rounded-lg mb-4">
        <p class="text-yellow-400 text-sm">
            These changes will give {{ $paidCount }} attendees a 72-hour window 
            to request a full refund.
        </p>
    </div>
    
    <div class="mb-4">
        <label class="text-white font-medium block mb-2">
            Reason for changes (visible to attendees)
        </label>
        <textarea wire:model="editReason" rows="2" 
                  placeholder="e.g., Venue had a scheduling conflict..."
                  class="w-full input-field"></textarea>
    </div>
    
    <div class="flex gap-3">
        <button wire:click="cancelEdit" class="secondary-btn flex-1">Cancel</button>
        <button wire:click="confirmEdit" class="primary-btn flex-1">
            Confirm & Notify Attendees
        </button>
    </div>
</div>
```

## Attendee Components

### 4. Refund Window Banner (Event Detail Page)
Persistent banner when there's an active refund window.

```blade
@if($activeRefundWindow && !$userResponse)
<div class="bg-gradient-to-r from-yellow-500/20 to-orange-500/20 border border-yellow-500/30 
            rounded-xl p-4 mb-6">
    <div class="flex items-start justify-between gap-4">
        <div>
            <h4 class="text-yellow-400 font-bold flex items-center gap-2">
                <svg class="w-5 h-5"><!-- alert icon --></svg>
                This event has been modified
            </h4>
            <p class="text-gray-300 text-sm mt-1">
                Review the changes below. You have 
                <span class="text-white font-bold">{{ $timeRemaining }}</span> 
                to request a refund if needed.
            </p>
        </div>
        <div class="flex gap-2 flex-shrink-0">
            <button wire:click="acceptChanges" class="secondary-btn text-sm">
                Accept
            </button>
            <button wire:click="requestRefund" class="danger-btn text-sm">
                Request Refund
            </button>
        </div>
    </div>
</div>
@endif
```

