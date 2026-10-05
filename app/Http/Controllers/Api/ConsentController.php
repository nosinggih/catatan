<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\UserResource;
use Illuminate\Http\Request;

class ConsentController extends Controller
{
    public function __invoke(Request $request): UserResource
    {
        $request->validate(
            ['accept' => ['accepted']],
            ['accept.accepted' => 'Centang persetujuan untuk melanjutkan.'],
        );

        $request->user()->acceptTerms();

        return new UserResource($request->user());
    }
}
