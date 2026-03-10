<?php

namespace App\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Process;

class UpdateController extends Controller
{
    private const GITHUB_API = 'https://api.github.com/repos/getfy-opensource/getfy/releases/latest';

    /**
     * Check for updates (GitHub Releases API).
     */
    public function check(): JsonResponse
    {
        $current = config('getfy.version');
        $response = [
            'current' => $current,
            'latest' => null,
            'available' => false,
            'error' => null,
            'changelog_remote' => null,
        ];

        try {
            $res = Http::timeout(10)
                ->withHeaders(['Accept' => 'application/vnd.github+json'])
                ->get(self::GITHUB_API);

            if ($res->status() === 404) {
                $response['error'] = 'Nenhuma release encontrada no repositório.';

                return response()->json($response);
            }

            if (! $res->successful()) {
                $response['error'] = 'Não foi possível verificar atualizações. Tente novamente mais tarde.';

                return response()->json($response);
            }

            $data = $res->json();
            $tagName = $data['tag_name'] ?? '';
            $latest = ltrim((string) $tagName, 'v');
            $response['latest'] = $latest;
            $response['changelog_remote'] = $data['body'] ?? null;

            if ($latest !== '' && version_compare($latest, $current, '>')) {
                $response['available'] = true;
            }
        } catch (\Throwable $e) {
            $response['error'] = 'Erro ao verificar: ' . $e->getMessage();
        }

        return response()->json($response);
    }

    /**
     * Run update: git pull, composer, npm build, migrate.
     */
    public function run(Request $request): JsonResponse|RedirectResponse
    {
        if (! config('getfy.updates_enabled', true)) {
            if ($request->wantsJson()) {
                return response()->json(['success' => false, 'message' => 'Atualizações pela interface estão desativadas.'], 403);
            }

            return redirect()->route('settings.index', ['tab' => 'update'])
                ->with('error', 'Atualizações pela interface estão desativadas.');
        }

        $basePath = base_path();
        $branch = config('getfy.update_branch', 'main');
        $expectedRepo = config('getfy.update_repository_url', 'https://github.com/getfy-opensource/getfy.git');
        $timeout = 300;

        // Check if .git exists
        if (! is_dir($basePath . DIRECTORY_SEPARATOR . '.git')) {
            $msg = 'Este diretório não é um repositório Git. Atualização automática indisponível.';
            if ($request->wantsJson()) {
                return response()->json(['success' => false, 'message' => $msg], 400);
            }

            return redirect()->route('settings.index', ['tab' => 'update'])->with('error', $msg);
        }

        $steps = [];
        $runStep = function (string $command, string $label) use ($basePath, $timeout, &$steps): bool {
            $result = Process::path($basePath)->timeout($timeout)->run($command);
            $steps[] = ['label' => $label, 'ok' => $result->successful(), 'output' => $result->output(), 'error' => $result->errorOutput()];
            if (! $result->successful()) {
                return false;
            }
            return true;
        };

        // 1. Git fetch + pull
        if (! $runStep("git fetch origin && git pull origin {$branch}", 'Git pull')) {
            $last = end($steps);
            $msg = 'Falha ao atualizar código: ' . ($last['error'] ?: $last['output'] ?: 'erro desconhecido');
            if ($request->wantsJson()) {
                return response()->json(['success' => false, 'message' => $msg, 'steps' => $steps], 422);
            }

            return redirect()->route('settings.index', ['tab' => 'update'])->with('error', $msg);
        }

        // 2. Composer install
        if (! $runStep('composer install --no-interaction --no-dev', 'Composer install')) {
            $last = end($steps);
            $msg = 'Falha no Composer: ' . ($last['error'] ?: $last['output'] ?: 'erro desconhecido');
            if ($request->wantsJson()) {
                return response()->json(['success' => false, 'message' => $msg, 'steps' => $steps], 422);
            }

            return redirect()->route('settings.index', ['tab' => 'update'])->with('error', $msg);
        }

        // 3. NPM ci + build
        if (! $runStep('npm ci && npm run build', 'NPM build')) {
            $last = end($steps);
            $msg = 'Falha no build do frontend: ' . ($last['error'] ?: $last['output'] ?: 'erro desconhecido');
            if ($request->wantsJson()) {
                return response()->json(['success' => false, 'message' => $msg, 'steps' => $steps], 422);
            }

            return redirect()->route('settings.index', ['tab' => 'update'])->with('error', $msg);
        }

        // 4. Migrate
        try {
            \Illuminate\Support\Facades\Artisan::call('migrate', ['--force' => true]);
        } catch (\Throwable $e) {
            $msg = 'Falha nas migrations: ' . $e->getMessage();
            if ($request->wantsJson()) {
                return response()->json(['success' => false, 'message' => $msg, 'steps' => $steps], 422);
            }

            return redirect()->route('settings.index', ['tab' => 'update'])->with('error', $msg);
        }

        // 5. Config cache
        try {
            \Illuminate\Support\Facades\Artisan::call('config:cache');
        } catch (\Throwable $e) {
            // Non-fatal
        }

        if ($request->wantsJson()) {
            return response()->json(['success' => true, 'message' => 'Atualização concluída com sucesso.', 'redirect' => route('settings.index', ['tab' => 'update']), 'steps' => $steps]);
        }

        return redirect()->route('settings.index', ['tab' => 'update'])->with('success', 'Atualização concluída com sucesso.');
    }
}
