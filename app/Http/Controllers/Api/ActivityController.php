<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\ActivityResource;
use App\Models\Activity;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Http\Response;
use Illuminate\Validation\ValidationException;

class ActivityController extends Controller
{
    public function index(Request $request): AnonymousResourceCollection
    {
        $activities = $request->user()->currentHousehold()->activities()
            ->withStats($this->timezone($request))
            ->orderByDesc('entries_count')
            ->orderBy('name')
            ->get();

        return ActivityResource::collection($activities);
    }

    /**
     * Creates an activity, or returns the existing one with the same name
     * so that "Ganti sprei" and "ganti  sprei" never become two buttons.
     */
    public function store(Request $request): ActivityResource
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:80'],
            'icon' => ['nullable', 'string', 'max:16'],
        ]);

        $household = $request->user()->currentHousehold();
        $activity = $household->activities()
            ->where('normalized_name', Activity::normalize($data['name']))
            ->first();

        $created = ! $activity;
        $activity ??= $household->activities()->create($data);

        return (new ActivityResource($this->withStats($request, $activity)))
            ->additional(['created' => $created]);
    }

    public function show(Request $request, int $activity): ActivityResource
    {
        return new ActivityResource($this->withStats($request, $this->find($request, $activity)));
    }

    public function update(Request $request, int $activity): ActivityResource
    {
        $activity = $this->find($request, $activity);
        $data = $request->validate([
            'name' => ['sometimes', 'required', 'string', 'max:80'],
            'icon' => ['sometimes', 'nullable', 'string', 'max:16'],
        ]);

        if (isset($data['name'])) {
            $taken = $activity->household->activities()
                ->where('normalized_name', Activity::normalize($data['name']))
                ->whereKeyNot($activity->id)
                ->exists();

            if ($taken) {
                throw ValidationException::withMessages(['name' => 'Kegiatan dengan nama ini sudah ada.']);
            }
        }

        $activity->update($data);

        return new ActivityResource($this->withStats($request, $activity));
    }

    public function destroy(Request $request, int $activity): Response
    {
        $this->find($request, $activity)->delete();

        return response()->noContent();
    }

    private function find(Request $request, int $id): Activity
    {
        return $request->user()->currentHousehold()->activities()->findOrFail($id);
    }

    private function withStats(Request $request, Activity $activity): Activity
    {
        return Activity::whereKey($activity->id)->withStats($this->timezone($request))->firstOrFail();
    }
}
