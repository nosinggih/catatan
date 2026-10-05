<?php

namespace Tests\Feature\Api;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MeTest extends TestCase
{
    use RefreshDatabase;

    public function test_guests_get_401(): void
    {
        $this->getJson('/api/me')->assertUnauthorized();
    }

    public function test_returns_the_current_user(): void
    {
        $user = User::factory()->acceptedTerms()->create(['name' => 'Ibu Sari']);

        $this->actingAs($user)
            ->getJson('/api/me')
            ->assertOk()
            ->assertJsonPath('data.name', 'Ibu Sari')
            ->assertJsonPath('data.terms_accepted', true)
            ->assertJsonMissingPath('data.google_id');
    }

    public function test_terms_accepted_is_false_for_an_older_terms_version(): void
    {
        $user = User::factory()->create([
            'terms_accepted_at' => now(),
            'terms_version' => '2000-01-01',
        ]);

        $this->actingAs($user)->getJson('/api/me')->assertJsonPath('data.terms_accepted', false);
    }
}
