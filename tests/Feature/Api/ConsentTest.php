<?php

namespace Tests\Feature\Api;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ConsentTest extends TestCase
{
    use RefreshDatabase;

    public function test_guests_cannot_accept(): void
    {
        $this->postJson('/api/consent', ['accept' => true])->assertUnauthorized();
    }

    public function test_requires_the_checkbox(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->postJson('/api/consent', ['accept' => false])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['accept' => 'Centang persetujuan untuk melanjutkan.']);

        $this->assertNull($user->fresh()->terms_accepted_at);
    }

    public function test_records_acceptance_of_the_current_version(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->postJson('/api/consent', ['accept' => true])
            ->assertOk()
            ->assertJsonPath('data.terms_accepted', true);

        $user->refresh();
        $this->assertNotNull($user->terms_accepted_at);
        $this->assertSame(config('catatan.terms_version'), $user->terms_version);
    }
}
