<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Services\MemberAreaResolver;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;
use Inertia\Response;

class LoginController extends Controller
{
    /**
     * Exibe o login da plataforma ou, se o host for de área de membros (subdomínio/domínio próprio),
     * delega para o login da área de membros do produto.
     */
    public function showLoginForm(Request $request): Response|RedirectResponse
    {
        $resolved = app(MemberAreaResolver::class)->resolve($request);
        if ($resolved && in_array($resolved['access_type'], ['subdomain', 'custom'], true)) {
            $request->attributes->set('member_area_product', $resolved['product']);
            $request->attributes->set('member_area_access_type', $resolved['access_type']);
            $request->attributes->set('member_area_slug', $resolved['slug']);
            return app()->call([\App\Http\Controllers\MemberAreaLoginController::class, 'showLoginForm'], [
                'request' => $request,
                'slug' => $resolved['slug'],
            ]);
        }
        return Inertia::render('Auth/Login');
    }

    public function login(Request $request): RedirectResponse
    {
        $resolved = app(MemberAreaResolver::class)->resolve($request);
        if ($resolved && in_array($resolved['access_type'], ['subdomain', 'custom'], true)) {
            $request->attributes->set('member_area_product', $resolved['product']);
            $request->attributes->set('member_area_access_type', $resolved['access_type']);
            $request->attributes->set('member_area_slug', $resolved['slug']);
            return app()->call([\App\Http\Controllers\MemberAreaLoginController::class, 'login'], [
                'request' => $request,
                'slug' => $resolved['slug'],
            ]);
        }

        $credentials = $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required'],
        ]);

        if (Auth::attempt($credentials, (bool) $request->boolean('remember'))) {
            $request->session()->regenerate();
            $user = Auth::user();
            if ($user->canAccessPanel()) {
                return redirect()->intended('/dashboard');
            }
            return redirect()->intended('/area-membros');
        }

        return back()->withErrors([
            'email' => 'Credenciais inválidas.',
        ])->onlyInput('email');
    }

    public function logout(Request $request)
    {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();
        return redirect('/');
    }
}
