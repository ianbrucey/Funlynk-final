<?php

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('username availability endpoint returns available for new username', function () {
    $response = $this->postJson('/api/check-username', [
        'username' => 'newusername',
    ]);

    $response->assertStatus(200)
        ->assertJson([
            'available' => true,
            'username' => 'newusername',
        ]);
});

test('username availability endpoint returns unavailable for taken username', function () {
    User::factory()->create(['username' => 'takenusername']);

    $response = $this->postJson('/api/check-username', [
        'username' => 'takenusername',
    ]);

    $response->assertStatus(200)
        ->assertJson([
            'available' => false,
            'username' => 'takenusername',
        ]);
});

test('username availability endpoint excludes current user', function () {
    $user = User::factory()->create(['username' => 'myusername']);

    $response = $this->postJson('/api/check-username', [
        'username' => 'myusername',
        'exclude_user_id' => $user->id,
    ]);

    $response->assertStatus(200)
        ->assertJson([
            'available' => true,
            'username' => 'myusername',
        ]);
});

test('username availability endpoint requires minimum 3 characters', function () {
    $response = $this->postJson('/api/check-username', [
        'username' => 'ab',
    ]);

    $response->assertStatus(422)
        ->assertJsonValidationErrors(['username']);
});

test('username availability endpoint normalizes username', function () {
    User::factory()->create(['username' => 'test-user']);

    $response = $this->postJson('/api/check-username', [
        'username' => 'Test User',
    ]);

    $response->assertStatus(200)
        ->assertJson([
            'available' => false,
            'username' => 'test-user',
        ]);
});
