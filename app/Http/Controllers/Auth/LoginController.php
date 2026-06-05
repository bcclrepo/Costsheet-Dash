<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Services\ActivityLogger;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class LoginController extends Controller
{
    public function showLogin()
    {
        if (Auth::check()) {
            return redirect()->route('dashboard');
        }
        return view('auth.login');
    }

    public function login(Request $request)
    {
        $request->validate([
            'email'   => 'required|email',
            'password'=> 'required',
            'captcha' => 'required',
        ]);

        // Captcha check (case-insensitive)
        if (strtoupper($request->captcha) !== strtoupper(session('captcha_code', ''))) {
            return back()->withErrors(['captcha' => 'Incorrect captcha. Please try again.'])->withInput($request->only('email'));
        }
        session()->forget('captcha_code');

        $credentials = $request->only('email', 'password');

        $remember = $request->boolean('remember');

        if (Auth::attempt($credentials, $remember)) {
            if (!Auth::user()->is_active) {
                Auth::logout();
                return back()->withErrors(['email' => 'Your account has been deactivated.']);
            }
            $request->session()->regenerate();
            ActivityLogger::log('LOGIN', 'User logged in');
            return redirect()->intended(route('dashboard'));
        }

        return back()->withErrors(['email' => 'Invalid email or password.'])->withInput($request->only('email'));
    }

    public function logout(Request $request)
    {
        ActivityLogger::log('LOGOUT', 'User logged out');
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();
        return redirect()->route('login');
    }
}
