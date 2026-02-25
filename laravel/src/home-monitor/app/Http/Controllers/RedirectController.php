<?php

namespace App\Http\Controllers;

use Illuminate\Http\Response;

class RedirectController extends Controller
{
    public function index()
    {
        return redirect('/api/documentation');
    }

    public function unknownApiRoute()
    {
        return response()->json(
            ['errors' => ['route' => ['Not Found']]],
            Response::HTTP_NOT_FOUND
        );
    }

    public function fallback()
    {
        return redirect('/');
    }
}
