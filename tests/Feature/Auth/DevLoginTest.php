<?php

namespace Tests\Feature\Auth;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DevLoginTest extends TestCase
{
    use RefreshDatabase;

    public function test_is_disabled_by_default(): void
    {
        $this->get('/auth/dev-login')->assertNotFound();
        $this->assertGuest();
    }

    public function test_is_disabled_outside_local_even_when_flag_is_on(): void
    {
        config(['catatan.dev_login' => true]);

        // The test suite runs with APP_ENV=testing.
        $this->get('/auth/dev-login')->assertNotFound();
    }

    public function test_logs_in_when_enabled_locally(): void
    {
        config(['catatan.dev_login' => true]);
        $this->app['env'] = 'local';

        $this->get('/auth/dev-login')->assertRedirect('/');

        $this->assertAuthenticated();
    }
}
