<?php

namespace Tests\Feature\Api;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PushSubscriptionTest extends TestCase
{
    use RefreshDatabase;

    public function test_stores_and_removes_a_subscription(): void
    {
        $user = User::factory()->acceptedTerms()->create();
        $payload = [
            'endpoint' => 'https://fcm.googleapis.com/fcm/send/abc',
            'keys' => ['p256dh' => 'BPk', 'auth' => 'xyz'],
        ];

        $this->actingAs($user)->postJson('/api/push-subscriptions', $payload)->assertNoContent();
        $this->actingAs($user)->postJson('/api/push-subscriptions', $payload)->assertNoContent();
        $this->assertSame(1, $user->pushSubscriptions()->count());

        $this->actingAs($user)
            ->deleteJson('/api/push-subscriptions', ['endpoint' => $payload['endpoint']])
            ->assertNoContent();
        $this->assertSame(0, $user->pushSubscriptions()->count());
    }

    public function test_validates_the_subscription(): void
    {
        $user = User::factory()->acceptedTerms()->create();

        $this->actingAs($user)
            ->postJson('/api/push-subscriptions', ['endpoint' => 'not a url'])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['endpoint', 'keys.p256dh', 'keys.auth']);
    }
}
