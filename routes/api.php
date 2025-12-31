<?php

use App\Http\Controllers\Api\PostReactionController;
use App\Http\Controllers\Api\V1\VideoController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "api" middleware group. Make something great!
|
*/

Route::middleware('auth:sanctum')->group(function () {
    // Post reactions
    Route::post('/posts/{post}/react', [PostReactionController::class, 'react']);
    Route::delete('/posts/{post}/react', [PostReactionController::class, 'unreact']);
    Route::get('/posts/{post}/reactions', [PostReactionController::class, 'getReactions']);
    
    // Post invitations
    Route::post('/posts/{post}/invite', [PostReactionController::class, 'invite']);
    Route::get('/users/me/invitations', [PostReactionController::class, 'getInvitations']);
    
    // Video API v1
    Route::prefix('v1/videos')->group(function () {
        // Upload flow
        Route::post('/upload-url', [VideoController::class, 'requestUploadUrl']);
        Route::post('/{video}/confirm-upload', [VideoController::class, 'confirmUpload']);
        
        // Video CRUD
        Route::get('/{video}', [VideoController::class, 'show']);
        Route::delete('/{video}', [VideoController::class, 'destroy']);
        
        // Streaming
        Route::get('/{video}/stream', [VideoController::class, 'stream']);
        Route::get('/{video}/status', [VideoController::class, 'status']);
        
        // Analytics (owner only)
        Route::get('/{video}/analytics', [VideoController::class, 'analytics']);
    });
});

// Public video routes (views can be anonymous)
Route::prefix('v1/videos')->group(function () {
    Route::post('/{video}/view', [VideoController::class, 'recordView'])
        ->middleware('throttle:60,1');
});

// S3 Webhook (no auth, verify signature in controller)
Route::post('/webhooks/s3/video-uploaded', [VideoController::class, 'handleS3Webhook'])
    ->name('webhooks.s3.video-uploaded');

