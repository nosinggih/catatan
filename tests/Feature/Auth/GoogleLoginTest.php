<?php

namespace Tests\Feature\Auth;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Socialite\Facades\Socialite;
use Laravel\Socialite\Two\User as GoogleUser;
use Tests\TestCase;

class GoogleLoginTest extends TestCase
{
    use RefreshDatabase;

    private function fakeGoogleUser(string $id = 'g-123', string $email = 'ibu@example.com'): void
    {
        $googleUser = (new GoogleUser)->map([
            'id' => $id,
            'name' => 'Ibu Sari',
            'email' => $email,
            'avatar' => 'https://example.com/a.png',
        ]);

        Socialite::shouldReceive('driver->user')->andReturn($googleUser);
    }

    public function test_redirects_to_google(): void
    {
        config(['services.google.client_id' => 'id', 'services.google.client_secret' => 'secret']);

        $response = $this->get('/auth/google/redirect');

        $response->assertRedirect();
        $this->assertStringStartsWith('https://accounts.google.com/', $response->headers->get('Location'));
    }

    public function test_callback_creates_and_logs_in_a_new_user(): void
    {
        $this->fakeGoogleUser();

        $this->get('/auth/google/callback')->assertRedirect('/');

        $user = User::firstWhere('email', 'ibu@example.com');
        $this->assertNotNull($user);
        $this->assertSame('g-123', $user->google_id);
        $this->assertSame('Ibu Sari', $user->name);
        $this->assertFalse($user->hasAcceptedCurrentTerms());
        $this->assertAuthenticatedAs($user);
    }

    public function test_callback_links_an_existing_user_by_email(): void
    {
        $existing = User::factory()->create(['email' => 'ibu@example.com']);
        $this->fakeGoogleUser();

        $this->get('/auth/google/callback')->assertRedirect('/');

        $this->assertSame(1, User::count());
        $this->assertSame('g-123', $existing->fresh()->google_id);
        $this->assertAuthenticatedAs($existing);
    }

    public function test_failed_callback_returns_to_login_page(): void
    {
        Socialite::shouldReceive('driver->user')->andThrow(new \RuntimeException('invalid state'));

        $this->get('/auth/google/callback')->assertRedirect('/masuk?gagal=1');
        $this->assertGuest();
    }
}
