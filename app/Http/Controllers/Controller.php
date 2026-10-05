<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

abstract class Controller
{
    /**
     * The viewer's timezone from the `tz` query parameter, used for
     * calendar-based stats such as "this month".
     */
    protected function timezone(Request $request): string
    {
        $tz = $request->query('tz');

        return is_string($tz) && in_array($tz, timezone_identifiers_list(), true) ? $tz : 'Asia/Jakarta';
    }
}
