<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;

class AuthController extends Controller
{
    public function login(Request $request)
    {
        $credentials = $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required', 'string'],
        ]);

        if (! Auth::attempt($credentials, $request->boolean('remember'))) {
            throw ValidationException::withMessages([
                'email' => 'The email address or password is incorrect.',
            ]);
        }

        $request->session()->regenerate();

        return redirect()->to($this->destination($request));
    }

    public function register(Request $request)
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:80'],
            'email' => ['required', 'email', 'max:255', 'unique:users,email'],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
            'respectful' => ['accepted'],
        ]);

        $user = User::create(collect($data)->only(['name', 'email', 'password'])->all());
        $user->refresh();
        Auth::login($user);
        $request->session()->regenerate();

        return redirect()->to($this->destination($request));
    }

    public function logout(Request $request)
    {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('home');
    }

    private function destination(Request $request): string
    {
        $target = $request->input('redirect') ?: $request->session()->pull('url.intended');

        // Only return to this application, including its XAMPP base path.
        if (is_string($target) && str_starts_with($target, url('/').'/')
            && ! preg_match('/[\x00-\x20\\\\]/', $target)) {
            return $target;
        }

        return route($request->user()->homeRoute());
    }
}
