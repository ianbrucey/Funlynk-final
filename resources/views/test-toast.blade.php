<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="user-id" content="{{ auth()->id() }}">
    <title>Toast Notification Test</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="bg-gradient-to-br from-slate-900 via-purple-900 to-slate-900 min-h-screen text-white">
    <div class="container mx-auto px-6 py-12">
        <div class="max-w-2xl mx-auto">
            <!-- Header -->
            <div class="mb-8">
                <h1 class="text-4xl font-bold mb-2">🚀 Reverb Toast Test</h1>
                <p class="text-gray-400">Debug WebSocket notifications in real-time</p>
            </div>

            <!-- Status Card -->
            <div class="glass-card p-6 rounded-xl border border-white/10 mb-6">
                <h2 class="text-xl font-semibold mb-4">Connection Status</h2>
                
                <div class="space-y-3">
                    <div class="flex items-center justify-between p-3 bg-slate-800/50 rounded-lg">
                        <span class="text-gray-300">User ID:</span>
                        <span class="font-mono text-cyan-400" id="user-id">{{ auth()->id() }}</span>
                    </div>
                    
                    <div class="flex items-center justify-between p-3 bg-slate-800/50 rounded-lg">
                        <span class="text-gray-300">Echo Status:</span>
                        <span class="font-mono" id="echo-status">
                            <span class="text-yellow-400">⏳ Checking...</span>
                        </span>
                    </div>
                    
                    <div class="flex items-center justify-between p-3 bg-slate-800/50 rounded-lg">
                        <span class="text-gray-300">Channel:</span>
                        <span class="font-mono text-purple-400" id="channel-name">user.{{ auth()->id() }}</span>
                    </div>
                    
                    <div class="flex items-center justify-between p-3 bg-slate-800/50 rounded-lg">
                        <span class="text-gray-300">Notifications Received:</span>
                        <span class="font-mono text-green-400" id="notification-count">0</span>
                    </div>
                </div>
            </div>

            <!-- Console Output -->
            <div class="glass-card p-6 rounded-xl border border-white/10 mb-6">
                <h2 class="text-xl font-semibold mb-4">Console Output</h2>
                <div id="console-output" class="bg-slate-900/50 rounded-lg p-4 font-mono text-sm text-gray-300 max-h-64 overflow-y-auto border border-white/5">
                    <div class="text-gray-500">Waiting for events...</div>
                </div>
            </div>

            <!-- Test Instructions -->
            <div class="glass-card p-6 rounded-xl border border-white/10 mb-6">
                <h2 class="text-xl font-semibold mb-4">How to Test</h2>
                <ol class="space-y-2 text-gray-300">
                    <li class="flex gap-3">
                        <span class="text-cyan-400 font-bold">1.</span>
                        <span>Keep this page open in your browser</span>
                    </li>
                    <li class="flex gap-3">
                        <span class="text-cyan-400 font-bold">2.</span>
                        <span>Open a terminal and run:</span>
                    </li>
                    <li class="ml-8 font-mono text-green-400 bg-slate-900/50 p-3 rounded">
                        php artisan reverb:test-notification
                    </li>
                    <li class="flex gap-3">
                        <span class="text-cyan-400 font-bold">3.</span>
                        <span>Watch for a toast notification in the top-right corner</span>
                    </li>
                    <li class="flex gap-3">
                        <span class="text-cyan-400 font-bold">4.</span>
                        <span>Check the console output below for debug info</span>
                    </li>
                </ol>
            </div>

            <!-- Manual Test Button -->
            <div class="glass-card p-6 rounded-xl border border-white/10">
                <h2 class="text-xl font-semibold mb-4">Manual Test</h2>
                <button onclick="testToastManually()" class="w-full px-6 py-3 bg-gradient-to-r from-pink-500 to-purple-500 rounded-xl font-semibold hover:scale-105 transition-all">
                    Show Test Toast
                </button>
                <p class="text-gray-400 text-sm mt-3">This simulates a notification without using Reverb</p>
            </div>
        </div>
    </div>

    <script>
        let notificationCount = 0;

        // Log to console output
        function logToConsole(message, type = 'info') {
            const output = document.getElementById('console-output');
            const timestamp = new Date().toLocaleTimeString();
            const colors = {
                'info': 'text-blue-400',
                'success': 'text-green-400',
                'error': 'text-red-400',
                'warning': 'text-yellow-400',
            };
            
            const line = document.createElement('div');
            line.className = `${colors[type] || 'text-gray-300'} mb-1`;
            line.textContent = `[${timestamp}] ${message}`;
            output.appendChild(line);
            output.scrollTop = output.scrollHeight;
        }

        // Check Echo status
        function checkEchoStatus() {
            const statusEl = document.getElementById('echo-status');
            
            if (typeof window.Echo === 'undefined') {
                statusEl.innerHTML = '<span class="text-red-400">❌ Echo not loaded</span>';
                logToConsole('Echo not loaded', 'error');
                return;
            }
            
            statusEl.innerHTML = '<span class="text-green-400">✅ Echo loaded</span>';
            logToConsole('Echo loaded successfully', 'success');
        }

        // Subscribe to notifications
        function subscribeToNotifications() {
            const userId = document.querySelector('meta[name="user-id"]')?.content;
            
            if (!userId) {
                logToConsole('User ID not found in meta tag', 'error');
                return;
            }
            
            logToConsole(`Subscribing to channel: user.${userId}`, 'info');
            
            if (typeof window.Echo !== 'undefined') {
                window.Echo.channel(`user.${userId}`)
                    .listen('.notification', (notification) => {
                        notificationCount++;
                        document.getElementById('notification-count').textContent = notificationCount;
                        
                        logToConsole(`Notification received: ${JSON.stringify(notification)}`, 'success');
                        showToast(notification);
                    });
                
                logToConsole('Subscribed to notification channel', 'success');
            } else {
                logToConsole('Echo not available', 'error');
            }
        }

        // Show toast notification
        function showToast(notification) {
            const toast = document.createElement('div');
            toast.className = 'fixed top-4 right-4 glass-card p-4 rounded-xl border border-white/10 z-50 animate-slide-in max-w-sm shadow-2xl';
            
            const icon = notification.type === 'test' ? '🚀' : '🔔';
            const title = notification.data?.reactor_name || 'Notification';
            const message = notification.data?.message || 'New notification';
            
            toast.innerHTML = `
                <div class="flex items-start gap-3">
                    <div class="text-2xl">${icon}</div>
                    <div class="flex-1">
                        <p class="text-white font-semibold text-sm">${title}</p>
                        <p class="text-gray-400 text-xs mt-1">${message}</p>
                    </div>
                    <button onclick="this.parentElement.parentElement.remove()" class="text-gray-400 hover:text-white transition">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                        </svg>
                    </button>
                </div>
            `;
            
            document.body.appendChild(toast);
            
            setTimeout(() => {
                toast.style.opacity = '0';
                toast.style.transform = 'translateX(100%)';
                setTimeout(() => toast.remove(), 300);
            }, 5000);
        }

        // Manual test
        function testToastManually() {
            logToConsole('Manual test triggered', 'info');
            showToast({
                type: 'test',
                data: {
                    reactor_name: 'Manual Test',
                    message: 'This is a manual test toast! 🎉'
                }
            });
        }

        // Initialize on page load
        document.addEventListener('DOMContentLoaded', () => {
            logToConsole('Page loaded', 'info');
            checkEchoStatus();
            subscribeToNotifications();
        });

        // Add slide-in animation
        if (!document.querySelector('#notification-animations')) {
            const style = document.createElement('style');
            style.id = 'notification-animations';
            style.textContent = `
                @keyframes slide-in {
                    from {
                        opacity: 0;
                        transform: translateX(100%);
                    }
                    to {
                        opacity: 1;
                        transform: translateX(0);
                    }
                }
                .animate-slide-in {
                    animation: slide-in 0.3s ease-out;
                }
            `;
            document.head.appendChild(style);
        }
    </script>
</body>
</html>

