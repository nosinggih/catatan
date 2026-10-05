<?php

namespace Tests\Feature\Auth;

use Illuminate\Http\Request;
use Laravel\Sanctum\Http\Middleware\EnsureFrontendRequestsAreStateful;
use Tests\TestCase;

class StatefulHostTest extends TestCase
{
    public function test_the_spa_is_stateful_on_any_port_it_is_served_from(): void
    {
        config(['app.url' => 'http://localhost']);

        $request = Request::create('http://127.0.0.1:8001/api/me', server: [
            'HTTP_REFERER' => 'http://127.0.0.1:8001/masuk',
        ]);

        $this->assertTrue(EnsureFrontendRequestsAreStateful::fromFrontend($request));
    }

    public function test_other_sites_are_not_stateful(): void
    {
        $request = Request::create('http://127.0.0.1:8001/api/me', server: [
            'HTTP_REFERER' => 'https://evil.example/',
        ]);

        $this->assertFalse(EnsureFrontendRequestsAreStateful::fromFrontend($request));
    }
}
