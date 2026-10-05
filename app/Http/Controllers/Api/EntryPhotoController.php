<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\EntryResource;
use App\Models\Entry;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Photos are compressed on the phone before upload and stored on the
 * private disk, readable only by members of the entry's household.
 */
class EntryPhotoController extends Controller
{
    public function store(Request $request, string $uuid): EntryResource
    {
        $entry = $this->find($request, $uuid);
        $request->validate(
            ['photo' => ['required', 'image', 'mimes:jpeg,png,webp', 'max:5120']],
            ['photo.max' => 'Foto terlalu besar (maks. 5 MB).'],
        );

        $old = $entry->photo_path;
        $path = $request->file('photo')->store('photos/'.$entry->activity->household_id, 'local');
        $entry->forceFill(['photo_path' => $path])->save();

        if ($old) {
            Storage::disk('local')->delete($old);
        }

        return new EntryResource($entry->load('user'));
    }

    public function show(Request $request, string $uuid): StreamedResponse
    {
        $entry = $this->find($request, $uuid);
        abort_unless($entry->photo_path, 404);

        return Storage::disk('local')->response($entry->photo_path, headers: [
            'Cache-Control' => 'private, max-age=31536000, immutable',
        ]);
    }

    public function destroy(Request $request, string $uuid): EntryResource
    {
        $entry = $this->find($request, $uuid);

        if ($entry->photo_path) {
            Storage::disk('local')->delete($entry->photo_path);
            $entry->forceFill(['photo_path' => null])->save();
        }

        return new EntryResource($entry->load('user'));
    }

    private function find(Request $request, string $uuid): Entry
    {
        $householdId = $request->user()->currentHousehold()->id;

        return Entry::where('uuid', $uuid)
            ->whereHas('activity', fn ($q) => $q->where('household_id', $householdId))
            ->firstOrFail();
    }
}
