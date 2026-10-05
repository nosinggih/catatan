<?php

namespace Tests\Feature\Api;

use App\Models\Activity;
use App\Models\Entry;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class EntryPhotoTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    private Entry $entry;

    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('local');
        $this->user = User::factory()->acceptedTerms()->create();
        $activity = Activity::factory()->create(['household_id' => $this->user->currentHousehold()->id]);
        $this->entry = Entry::factory()->create(['activity_id' => $activity->id, 'user_id' => $this->user->id]);
    }

    public function test_uploads_replaces_and_serves_a_photo(): void
    {
        $this->actingAs($this->user)
            ->postJson("/api/entries/{$this->entry->uuid}/photo", ['photo' => UploadedFile::fake()->image('a.jpg')])
            ->assertOk()
            ->assertJsonPath('data.photo_url', fn ($url) => str_starts_with($url, "/api/entries/{$this->entry->uuid}/photo"));

        $first = $this->entry->fresh()->photo_path;
        Storage::disk('local')->assertExists($first);

        $this->actingAs($this->user)
            ->postJson("/api/entries/{$this->entry->uuid}/photo", ['photo' => UploadedFile::fake()->image('b.jpg')])
            ->assertOk();

        Storage::disk('local')->assertMissing($first);
        $this->actingAs($this->user)->get("/api/entries/{$this->entry->uuid}/photo")->assertOk();
    }

    public function test_rejects_non_images_and_large_files(): void
    {
        $this->actingAs($this->user)
            ->postJson("/api/entries/{$this->entry->uuid}/photo", ['photo' => UploadedFile::fake()->create('a.pdf', 10, 'application/pdf')])
            ->assertUnprocessable();

        $this->actingAs($this->user)
            ->postJson("/api/entries/{$this->entry->uuid}/photo", ['photo' => UploadedFile::fake()->image('big.jpg')->size(6000)])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['photo' => 'Foto terlalu besar (maks. 5 MB).']);
    }

    public function test_deletes_a_photo(): void
    {
        $this->actingAs($this->user)
            ->postJson("/api/entries/{$this->entry->uuid}/photo", ['photo' => UploadedFile::fake()->image('a.jpg')]);
        $path = $this->entry->fresh()->photo_path;

        $this->actingAs($this->user)
            ->deleteJson("/api/entries/{$this->entry->uuid}/photo")
            ->assertOk()
            ->assertJsonPath('data.photo_url', null);

        Storage::disk('local')->assertMissing($path);
        $this->actingAs($this->user)->getJson("/api/entries/{$this->entry->uuid}/photo")->assertNotFound();
    }

    public function test_other_households_cannot_see_the_photo(): void
    {
        $this->actingAs($this->user)
            ->postJson("/api/entries/{$this->entry->uuid}/photo", ['photo' => UploadedFile::fake()->image('a.jpg')]);

        $stranger = User::factory()->acceptedTerms()->create();
        $this->actingAs($stranger)->getJson("/api/entries/{$this->entry->uuid}/photo")->assertNotFound();
        $this->actingAs($stranger)
            ->postJson("/api/entries/{$this->entry->uuid}/photo", ['photo' => UploadedFile::fake()->image('x.jpg')])
            ->assertNotFound();
    }
}
