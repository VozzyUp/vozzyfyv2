<?php

namespace App\Http\Controllers;

use App\Models\PanelPushSubscription;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class PanelPwaController extends Controller
{
    public function manifest(Request $request): JsonResponse
    {
        $baseUrl = rtrim($request->getSchemeAndHttpHost(), '/');
        $appName = config('getfy.app_name', 'Getfy');
        $themeColor = config('getfy.theme_primary', '#0ea5e9');

        $icons = [];
        $icon192 = asset('icons/icon-192x192.png');
        $icon512 = asset('icons/icon-512x512.png');
        if (file_exists(public_path('icons/icon-192x192.png'))) {
            $icons[] = ['src' => $icon192, 'sizes' => '192x192', 'type' => 'image/png', 'purpose' => 'any maskable'];
        }
        if (file_exists(public_path('icons/icon-512x512.png'))) {
            $icons[] = ['src' => $icon512, 'sizes' => '512x512', 'type' => 'image/png', 'purpose' => 'any maskable'];
        }
        if (empty($icons)) {
            $icons[] = [
                'src' => config('getfy.app_logo_icon', 'https://cdn.getfy.cloud/collapsed-logo.png'),
                'sizes' => '192x192',
                'type' => 'image/png',
                'purpose' => 'any maskable',
            ];
        }

        $manifest = [
            'name' => $appName,
            'short_name' => $appName,
            'start_url' => $baseUrl . '/',
            'scope' => $baseUrl . '/',
            'display' => 'standalone',
            'background_color' => '#18181b',
            'theme_color' => $themeColor,
            'icons' => $icons,
        ];

        return response()->json($manifest)->header('Content-Type', 'application/manifest+json');
    }

    public function pushSubscribe(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'endpoint' => ['required', 'string', 'max:500'],
            'keys' => ['required', 'array'],
            'keys.auth' => ['required', 'string'],
            'keys.p256dh' => ['required', 'string'],
        ]);

        $user = $request->user();
        if (! $user->canAccessPanel()) {
            return response()->json(['message' => 'Acesso negado.'], 403);
        }

        PanelPushSubscription::updateOrCreate(
            [
                'user_id' => $user->id,
                'tenant_id' => $user->tenant_id,
            ],
            [
                'endpoint' => $validated['endpoint'],
                'keys' => $validated['keys'],
                'user_agent' => $request->userAgent(),
            ]
        );

        return response()->json(['success' => true]);
    }
}
