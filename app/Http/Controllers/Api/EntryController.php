<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\EntryResource;
use App\Models\Entry;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Http\Response;
use Illuminate\Support\Carbon;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class EntryController extends Controller
{
    public function index(Request $request, int $activity): AnonymousResourceCollection
    {
        $entries = $request->user()->currentHousehold()->activities()->findOrFail($activity)
            ->entries()
            ->with('user')
            ->latest('occurred_at')
            ->latest('id')
            ->paginate(50);

        return EntryResource::collection($entries);
    }

    /**
     * Records an entry. The client generates the UUID, so sending the same
     * entry twice (e.g. a retry after a dropped connection) is harmless.
     */
    public function store(Request $request): JsonResponse
    {
        $household = $request->user()->currentHousehold();
        $data = $request->validate([
            'uuid' => ['required', 'uuid'],
            'activity_id' => ['required', 'integer', Rule::exists('activities', 'id')
                ->where('household_id', $household->id)
                ->whereNull('deleted_at')],
            ...$this->detailRules(),
        ]);

        $existing = Entry::withTrashed()->where('uuid', $data['uuid'])->first();
        if ($existing) {
            if ($existing->activity()->withTrashed()->value('household_id') !== $household->id) {
                throw ValidationException::withMessages(['uuid' => 'UUID sudah dipakai.']);
            }

            return (new EntryResource($existing->load('user')))->response()->setStatusCode(200);
        }

        $entry = new Entry($this->toUtc($data));
        $entry->occurred_at ??= now();
        $entry->activity_id = $data['activity_id'];
        $entry->user_id = $request->user()->id;
        $entry->save();

        return (new EntryResource($entry->load('user')))->response()->setStatusCode(201);
    }

    public function update(Request $request, string $uuid): EntryResource
    {
        $entry = $this->find($request, $uuid);
        $rules = $this->detailRules();
        $rules['occurred_at'][0] = 'sometimes';
        $rules['occurred_at'][] = 'required';

        $entry->update($this->toUtc($request->validate($rules)));

        return new EntryResource($entry->load('user'));
    }

    public function destroy(Request $request, string $uuid): Response
    {
        $this->find($request, $uuid)->delete();

        return response()->noContent();
    }

    /**
     * @return array<string, list<mixed>>
     */
    private function detailRules(): array
    {
        return [
            // Allow a little clock skew between phone and server.
            'occurred_at' => ['nullable', 'date', 'before_or_equal:'.now()->addMinutes(5)->toIso8601String()],
            'note' => ['nullable', 'string', 'max:500'],
            'cost' => ['nullable', 'integer', 'min:0', 'max:1000000000'],
            'location_name' => ['nullable', 'string', 'max:120'],
        ];
    }

    /**
     * Times arrive with the phone's offset ("...+07:00"); store them in UTC.
     *
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    private function toUtc(array $data): array
    {
        if (isset($data['occurred_at'])) {
            $data['occurred_at'] = Carbon::parse($data['occurred_at'])->utc();
        }

        return $data;
    }

    private function find(Request $request, string $uuid): Entry
    {
        $householdId = $request->user()->currentHousehold()->id;

        return Entry::where('uuid', $uuid)
            ->whereHas('activity', fn ($q) => $q->where('household_id', $householdId))
            ->firstOrFail();
    }
}
