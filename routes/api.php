<?php

use App\Http\Controllers\Api\PostReactionController;
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
});
