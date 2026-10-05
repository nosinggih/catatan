<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

class PushSubscriptionController extends Controller
{
    /**
     * Stores the browser's PushSubscription (the JSON from subscription.toJSON()).
     */
    public function store(Request $request): Response
    {
        $data = $request->validate([
            'endpoint' => ['required', 'url', 'max:1024'],
            'keys.p256dh' => ['required', 'string'],
            'keys.auth' => ['required', 'string'],
        ]);

        $request->user()->updatePushSubscription(
            $data['endpoint'],
            $data['keys']['p256dh'],
            $data['keys']['auth'],
            'aes128gcm',
        );

        return response()->noContent();
    }

    public function destroy(Request $request): Response
    {
        $data = $request->validate(['endpoint' => ['required', 'string']]);
        $request->user()->deletePushSubscription($data['endpoint']);

        return response()->noContent();
    }
}
