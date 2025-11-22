<?php

namespace App\Http\Controllers;

class RedirectController extends Controller
{
    public function index()
    {
        return redirect('/api/documentation');
    }

    public function fallback()
    {
        return redirect('/');
    }
}
