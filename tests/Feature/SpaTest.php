<?php

namespace Tests\Feature;

use Tests\TestCase;

class SpaTest extends TestCase
{
    public function test_app_paths_serve_the_spa_shell(): void
    {
        foreach (['/', '/masuk', '/persetujuan'] as $path) {
            $this->get($path)
                ->assertOk()
                ->assertSee('<div id="app"></div>', escape: false)
                ->assertSee('/manifest.webmanifest', escape: false);
        }
    }

    public function test_unknown_api_paths_are_not_served_by_the_spa(): void
    {
        $this->getJson('/api/does-not-exist')->assertNotFound();
    }

    public function test_dev_login_flag_is_off_in_the_page(): void
    {
        $this->get('/')->assertSee('"devLogin":false', escape: false);
    }
}
