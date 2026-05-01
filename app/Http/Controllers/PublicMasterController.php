<?php

namespace App\Http\Controllers;

use Illuminate\Http\RedirectResponse;

class PublicMasterController extends Controller
{
    public function show(string $slug): RedirectResponse
    {
        return redirect()->route('public.sto.show', ['slug' => $slug], 301);
    }
}
