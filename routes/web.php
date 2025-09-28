<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;

// Authentication Routes
Route::middleware(['guest'])->group(function () {
    Route::get('/admin/login', fn () => view('auth.login'))->name('login');

    // Handle login POST (add this route)
    Route::post('/admin/login', function (Request $request) {
        $credentials = $request->only('email', 'password');
        if (\Auth::attempt($credentials)) {
            $request->session()->regenerate();
            return redirect()->intended('/admin');
        }
        return back()->withErrors([
            'email' => 'The provided credentials do not match our records.',
        ]);
    })->name('login.store');
});

Route::post('/logout', [LoginController::class, 'destroy'])->name('logout');


// PWA support 
Route::get('/offline', fn () => view('offline'))->name('offline');
Route::get('/csrf-token', fn () => response()->json(['token' => csrf_token()]));

// Fallback for undefined routes (e.g. offline users or 404s)
Route::fallback(fn () => redirect()->route('login'));

