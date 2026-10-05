<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class DevLoginController extends Controller
{
    public function __invoke(Request $request): RedirectResponse
    {
        abort_unless(self::enabled(), 404);

        $user = User::firstOrCreate(
            ['email' => 'dev@catatan.test'],
            ['name' => 'Pengguna Dev'],
        );

        Auth::login($user, remember: true);
        $request->session()->regenerate();

        return redirect('/');
    }

    public static function enabled(): bool
    {
        return config('catatan.dev_login') && app()->environment('local');
    }
}
