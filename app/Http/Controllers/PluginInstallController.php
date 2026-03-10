<?php

namespace App\Http\Controllers;

use App\Plugins\PluginRegistry;
use App\Services\PluginStoreService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;

class PluginInstallController extends Controller
{
    public function __invoke(Request $request, string $slug): RedirectResponse
    {
        if (! preg_match('/^[a-z0-9\-]+$/i', $slug)) {
            return redirect()->route('plugins.index', ['tab' => 'store'])->with('error', 'Slug inválido.');
        }

        if (! class_exists('ZipArchive')) {
            return redirect()->route('plugins.index', ['tab' => 'store'])
                ->with('error', 'A extensão PHP Zip não está habilitada. Baixe o plugin e instale manualmente ou habilite a extensão no php.ini.')
                ->with('zip_unavailable', true);
        }

        $store = app(PluginStoreService::class);
        if (! $store->isConfigured()) {
            return redirect()->route('plugins.index', ['tab' => 'store'])->with('error', 'Loja de plugins não configurada.');
        }

        $tempFile = null;
        $useUploadedFile = $request->hasFile('plugin_zip');

        if ($useUploadedFile) {
            $upload = $request->file('plugin_zip');
            $ext = strtolower($upload->getClientOriginalExtension() ?? '');
            $mime = $upload->getMimeType();
            $zipMimes = ['application/zip', 'application/x-zip-compressed', 'application/octet-stream'];
            if ($ext !== 'zip' && ! in_array($mime, $zipMimes, true)) {
                return redirect()->route('plugins.index', ['tab' => 'store'])->with('error', 'Arquivo do plugin deve ser um ZIP.');
            }
            $tempFile = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'plugin_' . $slug . '_' . Str::random(8) . '.zip';
            move_uploaded_file($upload->getRealPath(), $tempFile);
        } else {
            $downloadUrl = $request->input('download_url');
            if ($downloadUrl && is_string($downloadUrl)) {
                $storeBase = $store->getBaseUrl();
                if ($storeBase === '' || (! str_starts_with($downloadUrl, $storeBase . '/') && ! str_starts_with($downloadUrl, $storeBase . '?'))) {
                    return redirect()->route('plugins.index', ['tab' => 'store'])->with('error', 'Link de download inválido.');
                }
                $download = ['download_url' => $downloadUrl, 'expires_at' => ''];
            } else {
                $purchaseToken = $request->input('purchase_token');
                $platformId = config('app.name') . '-' . md5(config('app.key'));
                $download = $store->requestDownloadUrl($slug, $purchaseToken ?: null, $platformId);
                if (! $download || empty($download['download_url'])) {
                    $reason = $store->getLastError();
                    $message = $reason
                        ? 'Não foi possível obter o link de download: ' . $reason
                        : 'Não foi possível obter o link de download. Verifique se o plugin é gratuito ou se o token de compra é válido.';
                    return redirect()->route('plugins.index', ['tab' => 'store'])->with('error', $message);
                }
            }

            $pluginsPath = base_path('plugins');
            $realPluginsPath = realpath($pluginsPath);
            if (! $realPluginsPath && is_dir($pluginsPath)) {
                $realPluginsPath = realpath(File::isDirectory($pluginsPath) ? $pluginsPath : (File::makeDirectory($pluginsPath, 0755, true) ? $pluginsPath : ''));
            }
            if (! $realPluginsPath || ! is_dir($realPluginsPath)) {
                return redirect()->route('plugins.index', ['tab' => 'store'])->with('error', 'Pasta de plugins indisponível.');
            }

            $tempFile = tempnam(sys_get_temp_dir(), 'plugin_' . $slug . '_');
            if ($tempFile === false) {
                return redirect()->route('plugins.index', ['tab' => 'store'])->with('error', 'Erro ao criar arquivo temporário.');
            }

            try {
                $response = Http::timeout(60)->withOptions(['sink' => $tempFile])->get($download['download_url']);
                if (! $response->successful()) {
                    @unlink($tempFile);
                    return redirect()->route('plugins.index', ['tab' => 'store'])->with('error', 'Falha ao baixar o plugin.');
                }
            } catch (\Throwable $e) {
                @unlink($tempFile);
                report($e);
                return redirect()->route('plugins.index', ['tab' => 'store'])->with('error', 'Erro ao baixar o plugin.');
            }
        }

        $pluginsPath = base_path('plugins');
        $realPluginsPath = realpath($pluginsPath);
        if (! $realPluginsPath) {
            if (! File::isDirectory($pluginsPath)) {
                File::makeDirectory($pluginsPath, 0755, true);
            }
            $realPluginsPath = realpath($pluginsPath);
        }
        if (! $realPluginsPath || ! is_dir($realPluginsPath)) {
            if ($tempFile && is_file($tempFile)) {
                @unlink($tempFile);
            }
            return redirect()->route('plugins.index', ['tab' => 'store'])->with('error', 'Pasta de plugins indisponível.');
        }

        $error = $this->extractZipToPlugins($tempFile, $slug, $pluginsPath, $realPluginsPath);
        if ($error) {
            return $error;
        }

        return $this->registerAndRunMigrations($slug)
            ?? redirect()->route('plugins.index', ['tab' => 'store'])->with('success', 'Plugin instalado com sucesso.');
    }

    /**
     * POST /gerenciar-plugins/install-from-zip - Instala a partir apenas do ZIP; slug inferido pela pasta raiz.
     */
    public function installFromZip(Request $request): RedirectResponse
    {
        if (! class_exists('ZipArchive')) {
            return redirect()->route('plugins.index', ['tab' => 'store'])
                ->with('error', 'A extensão PHP Zip não está habilitada.')
                ->with('zip_unavailable', true);
        }

        if (! $request->hasFile('plugin_zip')) {
            return redirect()->route('plugins.index', ['tab' => 'store'])->with('error', 'Envie o arquivo ZIP do plugin.');
        }

        $upload = $request->file('plugin_zip');
        $ext = strtolower($upload->getClientOriginalExtension() ?? '');
        $mime = $upload->getMimeType();
        $zipMimes = ['application/zip', 'application/x-zip-compressed', 'application/octet-stream'];
        if ($ext !== 'zip' && ! in_array($mime, $zipMimes, true)) {
            return redirect()->route('plugins.index', ['tab' => 'store'])->with('error', 'Arquivo do plugin deve ser um ZIP.');
        }

        $tempFile = tempnam(sys_get_temp_dir(), 'plugin_zip_');
        if ($tempFile === false || ! move_uploaded_file($upload->getRealPath(), $tempFile)) {
            return redirect()->route('plugins.index', ['tab' => 'store'])->with('error', 'Erro ao salvar o arquivo.');
        }

        $zip = new \ZipArchive;
        if ($zip->open($tempFile) !== true) {
            @unlink($tempFile);
            return redirect()->route('plugins.index', ['tab' => 'store'])->with('error', 'Arquivo do plugin inválido.');
        }

        $entries = [];
        for ($i = 0; $i < $zip->numFiles; $i++) {
            $name = $zip->getNameIndex($i);
            if (str_contains($name, '..')) {
                $zip->close();
                @unlink($tempFile);
                return redirect()->route('plugins.index', ['tab' => 'store'])->with('error', 'Arquivo do plugin inválido.');
            }
            $entries[] = $name;
        }
        $zip->close();

        $baseInZip = null;
        foreach ($entries as $name) {
            $parts = explode('/', str_replace('\\', '/', trim($name, '/')));
            $first = $parts[0] ?? '';
            if ($first === '' || $first === '.' || $first === '..') {
                continue;
            }
            if ($baseInZip === null) {
                $baseInZip = $first;
            } elseif ($baseInZip !== $first) {
                $baseInZip = '';
                break;
            }
        }

        if (! $baseInZip || $baseInZip === '') {
            @unlink($tempFile);
            return redirect()->route('plugins.index', ['tab' => 'store'])->with('error', 'O ZIP deve conter uma única pasta raiz (ex: meu-plugin/plugin.json).');
        }

        $slug = strtolower(preg_replace('/[^a-z0-9\-]/', '', str_replace('_', '-', $baseInZip)));
        if ($slug === '') {
            @unlink($tempFile);
            return redirect()->route('plugins.index', ['tab' => 'store'])->with('error', 'Não foi possível identificar o nome do plugin a partir do ZIP.');
        }

        $pluginsPath = base_path('plugins');
        $realPluginsPath = realpath($pluginsPath);
        if (! $realPluginsPath) {
            if (! File::isDirectory($pluginsPath)) {
                File::makeDirectory($pluginsPath, 0755, true);
            }
            $realPluginsPath = realpath($pluginsPath);
        }
        if (! $realPluginsPath || ! is_dir($realPluginsPath)) {
            @unlink($tempFile);
            return redirect()->route('plugins.index', ['tab' => 'store'])->with('error', 'Pasta de plugins indisponível.');
        }

        $error = $this->extractZipToPlugins($tempFile, $slug, $pluginsPath, $realPluginsPath);
        if ($error) {
            return $error;
        }

        return $this->registerAndRunMigrations($slug)
            ?? redirect()->route('plugins.index', ['tab' => 'store'])->with('success', 'Plugin instalado com sucesso.');
    }

    /**
     * Register plugin in DB and run its migrations. Returns RedirectResponse on error, null on success.
     * $slug is the folder name we used (e.g. from URL); plugin.json may have a different slug, so we find by path if needed.
     */
    private function registerAndRunMigrations(string $slug): ?RedirectResponse
    {
        $all = PluginRegistry::installed();
        $pluginsPath = base_path('plugins');
        $targetDir = $pluginsPath.DIRECTORY_SEPARATOR.$slug;
        $targetReal = is_dir($targetDir) ? realpath($targetDir) : null;

        $plugin = collect($all)->first(fn ($p) => $p['slug'] === $slug);
        if (! $plugin && $targetReal) {
            $plugin = collect($all)->first(function ($p) use ($targetReal) {
                $pReal = is_dir($p['path']) ? realpath($p['path']) : null;
                return $pReal && $pReal === $targetReal;
            });
        }
        if (! $plugin) {
            return redirect()->route('plugins.index', ['tab' => 'store'])->with('error', 'Plugin não encontrado após extração.');
        }

        $pluginSlug = $plugin['slug'];
        PluginRegistry::register($pluginSlug);
        $migrationsPath = $plugin['migrations'] ?? null;
        if (is_string($migrationsPath) && $migrationsPath !== '') {
            $fullPath = $plugin['path'].DIRECTORY_SEPARATOR.str_replace(['/', '\\'], DIRECTORY_SEPARATOR, $migrationsPath);
            if (is_dir($fullPath)) {
                $base = rtrim(str_replace(['/', '\\'], DIRECTORY_SEPARATOR, base_path()), DIRECTORY_SEPARATOR).DIRECTORY_SEPARATOR;
                $full = str_replace(['/', '\\'], DIRECTORY_SEPARATOR, $fullPath);
                $relativePath = str_replace('\\', '/', Str::after($full, $base));
                try {
                    Artisan::call('migrate', ['--path' => $relativePath, '--force' => true]);
                } catch (\Throwable $e) {
                    report($e);
                    return redirect()->route('plugins.index', ['tab' => 'store'])->with('error', 'Plugin instalado, mas as migrations falharam: '.$e->getMessage());
                }
            }
        }
        return null;
    }

    /**
     * @return RedirectResponse|null null em sucesso
     */
    private function extractZipToPlugins(string $tempFile, string $slug, string $pluginsPath, ?string $realPluginsPath): ?RedirectResponse
    {
        $zip = new \ZipArchive;
        if ($zip->open($tempFile) !== true) {
            @unlink($tempFile);
            return redirect()->route('plugins.index', ['tab' => 'store'])->with('error', 'Arquivo do plugin inválido.');
        }

        $entries = [];
        for ($i = 0; $i < $zip->numFiles; $i++) {
            $name = $zip->getNameIndex($i);
            if (str_contains($name, '..')) {
                $zip->close();
                @unlink($tempFile);
                return redirect()->route('plugins.index', ['tab' => 'store'])->with('error', 'Arquivo do plugin inválido.');
            }
            $entries[] = $name;
        }

        $extractTo = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'plugin_extract_' . $slug . '_' . Str::random(8);
        if (is_dir($extractTo)) {
            File::deleteDirectory($extractTo);
        }
        if (! File::makeDirectory($extractTo, 0755, true)) {
            $zip->close();
            @unlink($tempFile);
            return redirect()->route('plugins.index', ['tab' => 'store'])->with('error', 'Erro ao preparar extração.');
        }

        $zip->extractTo($extractTo);
        $zip->close();
        @unlink($tempFile);

        $baseInZip = null;
        foreach ($entries as $name) {
            $parts = explode('/', str_replace('\\', '/', trim($name, '/')));
            $first = $parts[0] ?? '';
            if ($first === '' || $first === '.' || $first === '..') {
                continue;
            }
            if ($baseInZip === null) {
                $baseInZip = $first;
            } elseif ($baseInZip !== $first) {
                $baseInZip = '';
                break;
            }
        }
        $sourceDir = ($baseInZip && is_dir($extractTo . DIRECTORY_SEPARATOR . $baseInZip))
            ? $extractTo . DIRECTORY_SEPARATOR . $baseInZip
            : $extractTo;

        $targetDir = $pluginsPath . DIRECTORY_SEPARATOR . $slug;
        if (is_dir($targetDir)) {
            File::deleteDirectory($targetDir);
        }
        $pluginsDir = dirname($targetDir);
        if (! is_dir($pluginsDir)) {
            File::makeDirectory($pluginsDir, 0755, true);
        }
        rename($sourceDir, $targetDir);
        File::deleteDirectory($extractTo);

        $targetReal = realpath($targetDir);
        if (! $targetReal || ! Str::startsWith($targetReal, $realPluginsPath)) {
            if (is_dir($targetDir)) {
                File::deleteDirectory($targetDir);
            }
            return redirect()->route('plugins.index', ['tab' => 'store'])->with('error', 'Erro de segurança ao instalar o plugin.');
        }

        return null;
    }
}