<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class HomeController extends Controller
{
    /**
     * Create a new controller instance.
     *
     * @return void
     */
    public function __construct()
    {
        // $this->middleware('auth');
    }

    /**
     * Show the application dashboard.
     *
     * @return \Illuminate\Contracts\Support\Renderable
     */
    public function index()
    {
        return view('home');
    }

    /**
     * Get a new CSRF token and ensure that the session is active for more than 2 hours
     */
    public function refreshToken(Request $request)
    {
        session()->regenerate();
        return response()->json(["token" => csrf_token()]);
    }
}
