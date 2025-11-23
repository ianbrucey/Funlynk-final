<div class="min-h-screen py-12">
    <div class="container mx-auto ">
        <div class="max-w-2xl mx-auto">
            
            {{-- Header --}}
            <div class="mb-8 text-center">
                <h1 class="text-4xl font-bold mb-2">
                    <span class="gradient-text">Connect with Stripe</span>
                </h1>
                <p class="text-gray-400">Accept payments for your activities</p>
            </div>

            {{-- Status Card --}}
            <div class="relative p-8 glass-card mb-6">
                <div class="top-accent-center"></div>
                
                {{-- Error Message --}}
                @if($errorMessage)
                    <div class="mb-6 p-4 bg-red-500/20 border border-red-500/50 rounded-xl text-red-300">
                        {{ $errorMessage }}
                    </div>
                @endif
                
                @if($status === 'not_connected')
                    {{-- Not Connected --}}
                    <div class="text-center">
                        <div class="w-20 h-20 mx-auto mb-6 bg-slate-800/50 rounded-full flex items-center justify-center">
                            <svg class="w-10 h-10 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"></path>
                            </svg>
                        </div>
                        
                        <h2 class="text-2xl font-bold mb-3 text-white">Connect Your Stripe Account</h2>
                        <p class="text-gray-300 mb-6">
                            To create paid activities and receive payments, you need to connect your Stripe account.
                            Stripe handles all payment processing, payouts, and compliance.
                        </p>

                        <div class="bg-slate-800/30 border border-white/10 rounded-xl p-6 mb-6 text-left">
                            <h3 class="font-semibold text-white mb-3">What you'll need:</h3>
                            <ul class="space-y-2 text-gray-300 text-sm">
                                <li class="flex items-start gap-2">
                                    <svg class="w-5 h-5 text-cyan-400 mt-0.5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                                    </svg>
                                    <span>Government-issued ID (driver's license or passport)</span>
                                </li>
                                <li class="flex items-start gap-2">
                                    <svg class="w-5 h-5 text-cyan-400 mt-0.5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                                    </svg>
                                    <span>Bank account information for payouts</span>
                                </li>
                                <li class="flex items-start gap-2">
                                    <svg class="w-5 h-5 text-cyan-400 mt-0.5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                                    </svg>
                                    <span>Social Security Number or Tax ID</span>
                                </li>
                            </ul>
                        </div>

                        <button 
                            wire:click="startOnboarding"
                            class="w-full py-4 bg-gradient-to-r from-pink-500 to-purple-500 rounded-xl font-bold text-white hover:scale-105 transition-all shadow-lg"
                        >
                            Connect with Stripe
                        </button>
                    </div>

                @elseif($status === 'incomplete')
                    {{-- Onboarding Started but Incomplete --}}
                    <div class="text-center">
                        <div class="w-20 h-20 mx-auto mb-6 bg-yellow-500/20 rounded-full flex items-center justify-center">
                            <svg class="w-10 h-10 text-yellow-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                            </svg>
                        </div>
                        
                        <h2 class="text-2xl font-bold mb-3 text-white">Complete Your Onboarding</h2>
                        <p class="text-gray-300 mb-6">
                            You've started the onboarding process but haven't completed it yet.
                            Click below to continue where you left off.
                        </p>

                        {{-- Show Requirements if Available --}}
                        @if($requirements && isset($requirements['currently_due']) && count($requirements['currently_due']) > 0)
                            <div class="bg-yellow-500/10 border border-yellow-500/30 rounded-xl p-4 mb-6 text-left">
                                <h3 class="font-semibold text-yellow-300 mb-2 text-sm">Missing Information:</h3>
                                <ul class="space-y-1 text-yellow-200 text-xs">
                                    @foreach($requirements['currently_due'] as $requirement)
                                        <li>• {{ str_replace('_', ' ', ucfirst($requirement)) }}</li>
                                    @endforeach
                                </ul>
                            </div>
                        @endif

                        <button 
                            wire:click="startOnboarding"
                            class="w-full py-4 bg-gradient-to-r from-yellow-500 to-orange-500 rounded-xl font-bold text-white hover:scale-105 transition-all shadow-lg"
                        >
                            Continue Onboarding
                        </button>
                    </div>

                @elseif($status === 'pending_approval')
                    {{-- Onboarded but Pending Approval --}}
                    <div class="text-center">
                        <div class="w-20 h-20 mx-auto mb-6 bg-blue-500/20 rounded-full flex items-center justify-center">
                            <svg class="w-10 h-10 text-blue-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                            </svg>
                        </div>
                        
                        <h2 class="text-2xl font-bold mb-3 text-white">Pending Approval</h2>
                        <p class="text-gray-300 mb-6">
                            Your information has been submitted to Stripe for review.
                            This usually takes a few minutes to a few hours.
                        </p>

                        <button 
                            wire:click="checkStatus"
                            class="px-6 py-3 bg-slate-800/50 border border-white/10 rounded-xl hover:border-cyan-500/50 transition font-semibold"
                        >
                            Refresh Status
                        </button>
                    </div>

                @elseif($status === 'complete')
                    {{-- Fully Onboarded --}}
                    <div class="text-center">
                        <div class="w-20 h-20 mx-auto mb-6 bg-green-500/20 rounded-full flex items-center justify-center">
                            <svg class="w-10 h-10 text-green-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                            </svg>
                        </div>
                        
                        <h2 class="text-2xl font-bold mb-3 text-white">You're All Set!</h2>
                        <p class="text-gray-300 mb-6">
                            Your Stripe account is connected and ready to accept payments.
                            You can now create paid activities.
                        </p>

                        <a 
                            href="{{ route('activities.create') }}"
                            class="inline-block px-8 py-4 bg-gradient-to-r from-pink-500 to-purple-500 rounded-xl font-bold text-white hover:scale-105 transition-all shadow-lg"
                        >
                            Create Paid Activity
                        </a>
                    </div>
                @endif
            </div>

            {{-- Info Card --}}
            <div class="relative p-6 glass-card">
                <h3 class="font-semibold text-white mb-3">How it works:</h3>
                <div class="space-y-3 text-gray-300 text-sm">
                    <div class="flex items-start gap-3">
                        <div class="w-6 h-6 rounded-full bg-cyan-500/20 flex items-center justify-center flex-shrink-0 mt-0.5">
                            <span class="text-cyan-400 text-xs font-bold">1</span>
                        </div>
                        <p>Connect your Stripe account and complete identity verification</p>
                    </div>
                    <div class="flex items-start gap-3">
                        <div class="w-6 h-6 rounded-full bg-cyan-500/20 flex items-center justify-center flex-shrink-0 mt-0.5">
                            <span class="text-cyan-400 text-xs font-bold">2</span>
                        </div>
                        <p>Create paid activities and set your own prices</p>
                    </div>
                    <div class="flex items-start gap-3">
                        <div class="w-6 h-6 rounded-full bg-cyan-500/20 flex items-center justify-center flex-shrink-0 mt-0.5">
                            <span class="text-cyan-400 text-xs font-bold">3</span>
                        </div>
                        <p>Receive payments directly to your bank account (minus 10% platform fee)</p>
                    </div>
                    <div class="flex items-start gap-3">
                        <div class="w-6 h-6 rounded-full bg-cyan-500/20 flex items-center justify-center flex-shrink-0 mt-0.5">
                            <span class="text-cyan-400 text-xs font-bold">4</span>
                        </div>
                        <p>Stripe handles all compliance, taxes, and payouts</p>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <style>
        .gradient-text {
            background: linear-gradient(to right, #fbbf24, #06b6d4);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
        }

       

        .top-accent-center {
            position: absolute;
            top: 0;
            left: 50%;
            transform: translateX(-50%);
            width: 8rem;
            height: 0.25rem;
            background: linear-gradient(to right, transparent, #ec4899, transparent);
        }
    </style>
</div>
