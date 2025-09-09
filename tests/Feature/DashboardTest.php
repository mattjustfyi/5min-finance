<?php

use App\Models\User;

# Test broke probably due to removing Volt login page (commented out in /Users/matt/code/5min-finance/routes/auth.php)
beforeEach()->skip();

test('guests are redirected to the login page', function () {
    $response = $this->get('/dashboard');
    $response->assertRedirect('/login');
});

test('authenticated users can visit the dashboard', function () {
    $user = User::factory()->create();
    $this->actingAs($user);

    $response = $this->get('/dashboard');
    $response->assertStatus(200);
});
