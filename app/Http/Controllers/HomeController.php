<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class HomeController extends Controller
{
    public function landing()
    {
        return view('landing.index');
    }

    public function dashboard(Request $request): RedirectResponse
    {
        /** @var User $user */
        $user = $request->user();

        return redirect()->route($user->homeRoute());
    }
}
