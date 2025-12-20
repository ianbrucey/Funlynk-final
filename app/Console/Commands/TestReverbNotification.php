<?php

namespace App\Console\Commands;

use App\Events\TestNotification;
use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Config;

class TestReverbNotification extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'reverb:test-notification 
                            {user? : User ID or email to send test notification to}
                            {--message= : Custom message to display}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Test Reverb WebSocket by broadcasting a toast notification';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        // Get user
        $userInput = $this->argument('user');
        
        if ($userInput) {
            // Find user by ID or email
            $user = is_numeric($userInput) 
                ? User::find($userInput)
                : User::where('email', $userInput)->first();
            
            if (!$user) {
                $this->error("User not found: {$userInput}");
                return self::FAILURE;
            }
        } else {
            // Use first user if no user specified
            $user = User::first();
            
            if (!$user) {
                $this->error('No users found in database. Create a user first.');
                return self::FAILURE;
            }
            
            $this->info("No user specified. Using first user: {$user->email} (ID: {$user->id})");
        }

        // Get custom message or use default
        $message = $this->option('message') ?? 'Test notification from Reverb! 🚀';

        // Broadcast the test notification
        $this->info("Broadcasting test notification to user: {$user->email} (ID: {$user->id})");
        $this->info("Message: {$message}");
        $this->newLine();

        try {
            // Use sync queue driver for local testing to avoid Redis dependency
            $originalQueue = config('queue.default');
            if ($originalQueue === 'redis') {
                Config::set('queue.default', 'sync');
            }

            broadcast(new TestNotification($user->id, $message));

            // Restore original queue driver
            if ($originalQueue === 'redis') {
                Config::set('queue.default', $originalQueue);
            }

            $this->components->info('✅ Test notification broadcasted successfully!');
            $this->newLine();
            $this->line('📡 <fg=cyan>Check your browser - a toast notification should appear in the top-right corner.</>');
            $this->line('🔍 <fg=yellow>If nothing appears, check:');
            $this->line('   • Browser console for WebSocket connection errors');
            $this->line('   • Reverb server is running: php artisan reverb:start');
            $this->line('   • User is logged in and on a page with notifications.js loaded');
            $this->line('   • VITE_REVERB_* environment variables are correct');
            
            return self::SUCCESS;
        } catch (\Exception $e) {
            $this->components->error('❌ Failed to broadcast notification');
            $this->error($e->getMessage());
            $this->newLine();
            $this->line('💡 <fg=yellow>Troubleshooting:');
            $this->line('   • Ensure Reverb is running: php artisan reverb:start');
            $this->line('   • Check BROADCAST_CONNECTION=reverb in .env');
            $this->line('   • Verify Reverb credentials match in .env');
            
            return self::FAILURE;
        }
    }
}

