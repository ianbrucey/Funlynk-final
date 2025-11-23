<div class="min-h-screen py-12">
    <div class="container mx-auto px-6">
        <div class="max-w-2xl mx-auto">
            
            {{-- Header --}}
            <div class="mb-8 text-center">
                <h1 class="text-4xl font-bold mb-2">
                    <span class="gradient-text">Complete Your Payment</span>
                </h1>
                <p class="text-gray-400">Secure checkout powered by Stripe</p>
            </div>

            {{-- Activity Summary Card --}}
            <div class="relative p-8 glass-card mb-6">
                <div class="top-accent-center"></div>
                
                <h2 class="text-2xl font-bold mb-4 text-white">{{ $activity->title }}</h2>
                
                <div class="space-y-3 text-gray-300">
                    <div class="flex justify-between">
                        <span>Date:</span>
                        <span class="font-semibold">{{ $activity->start_time->format('F j, Y @ g:i A') }}</span>
                    </div>
                    <div class="flex justify-between">
                        <span>Location:</span>
                        <span class="font-semibold">{{ $activity->location_name }}</span>
                    </div>
                    <div class="flex justify-between border-t border-white/10 pt-3 mt-3">
                        <span class="text-lg">Total:</span>
                        <span class="text-2xl font-bold text-cyan-400">${{ number_format($activity->price_cents / 100, 2) }}</span>
                    </div>
                </div>
            </div>

            {{-- Payment Form Card --}}
            <div class="relative p-8 glass-card">
                <div class="top-accent-center"></div>
                
                <h3 class="text-xl font-bold mb-6 text-white">Payment Information</h3>

                @if($errorMessage)
                    <div class="mb-6 p-4 bg-red-500/20 border border-red-500/50 rounded-xl text-red-300">
                        {{ $errorMessage }}
                    </div>
                @endif

                <form id="payment-form">
                    {{-- Stripe Card Element --}}
                    <div class="mb-6">
                        <label class="block text-sm font-semibold text-gray-300 mb-2">Card Details</label>
                        <div id="card-element" class="p-4 bg-slate-800/50 border border-white/10 rounded-2xl"></div>
                        <div id="card-errors" class="text-red-400 text-sm mt-2"></div>
                    </div>

                    {{-- Submit Button --}}
                    <button 
                        type="submit"
                        id="submit-button"
                        class="w-full py-4 bg-gradient-to-r from-pink-500 to-purple-500 rounded-xl font-bold text-white hover:scale-105 transition-all shadow-lg disabled:opacity-50 disabled:cursor-not-allowed disabled:hover:scale-100"
                        wire:loading.attr="disabled"
                    >
                        <span id="button-text">
                            <span wire:loading.remove>Pay ${{ number_format($activity->price_cents / 100, 2) }}</span>
                            <span wire:loading>Processing...</span>
                        </span>
                    </button>
                </form>

                {{-- Security Notice --}}
                <div class="mt-6 text-center text-sm text-gray-400">
                    <svg class="w-4 h-4 inline mr-1" fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd" d="M5 9V7a5 5 0 0110 0v2a2 2 0 012 2v5a2 2 0 01-2 2H5a2 2 0 01-2-2v-5a2 2 0 012-2zm8-2v2H7V7a3 3 0 016 0z" clip-rule="evenodd"></path>
                    </svg>
                    Secured by Stripe. Your payment information is encrypted.
                </div>
            </div>

            {{-- Cancel Link --}}
            <div class="mt-6 text-center">
                <a href="{{ route('activities.show', $activity) }}" class="text-gray-400 hover:text-white transition">
                    ← Back to activity
                </a>
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

        /* Stripe Element Styling */
        .StripeElement {
            padding: 12px;
        }

        .StripeElement--focus {
            border-color: rgba(6, 182, 212, 0.5);
        }

        .StripeElement--invalid {
            border-color: rgba(239, 68, 68, 0.5);
        }
    </style>

    <script src="https://js.stripe.com/v3/"></script>
    <script>
        document.addEventListener('livewire:navigated', () => {
            const stripe = Stripe('{{ config('services.stripe.public') }}');
            const elements = stripe.elements();
            
            // Create card element with custom styling
            const cardElement = elements.create('card', {
                style: {
                    base: {
                        color: '#fff',
                        fontFamily: 'system-ui, sans-serif',
                        fontSize: '16px',
                        '::placeholder': {
                            color: '#94a3b8',
                        },
                    },
                    invalid: {
                        color: '#ef4444',
                        iconColor: '#ef4444',
                    },
                },
            });
            
            cardElement.mount('#card-element');

            // Handle real-time validation errors
            cardElement.on('change', (event) => {
                const displayError = document.getElementById('card-errors');
                if (event.error) {
                    displayError.textContent = event.error.message;
                } else {
                    displayError.textContent = '';
                }
            });

            // Handle form submission
            const form = document.getElementById('payment-form');
            const submitButton = document.getElementById('submit-button');
            
            form.addEventListener('submit', async (event) => {
                event.preventDefault();
                
                submitButton.disabled = true;
                document.getElementById('button-text').innerHTML = '<span>Processing...</span>';

                try {
                    // Confirm payment with Stripe
                    const {error, paymentIntent} = await stripe.confirmCardPayment(
                        '{{ $clientSecret }}',
                        {
                            payment_method: {
                                card: cardElement,
                            },
                        }
                    );

                    if (error) {
                        // Show error to customer
                        document.getElementById('card-errors').textContent = error.message;
                        submitButton.disabled = false;
                        document.getElementById('button-text').innerHTML = '<span>Pay ${{ number_format($activity->price_cents / 100, 2) }}</span>';
                    } else if (paymentIntent.status === 'succeeded') {
                        // Payment successful! Verify with backend
                        @this.call('verifyPayment');
                    }
                } catch (e) {
                    console.error('Payment error:', e);
                    document.getElementById('card-errors').textContent = 'An unexpected error occurred.';
                    submitButton.disabled = false;
                    document.getElementById('button-text').innerHTML = '<span>Pay ${{ number_format($activity->price_cents / 100, 2) }}</span>';
                }
            });
        });
    </script>
</div>
