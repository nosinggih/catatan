<?php

namespace Tests\Feature\Auth;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LogoutTest extends TestCase
{
    use RefreshDatabase;

    public function test_logs_the_user_out(): void
    {
        $this->actingAs(User::factory()->create());

        $this->postJson('/auth/logout')->assertNoContent();

        $this->assertGuest('web');
    }
}
