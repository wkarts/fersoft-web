<?php

namespace App\Services;

use App\Models\LoginPresentation;
use App\Models\LoginPresentationAsset;
use App\Models\LoginPresentationContent;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;

class LoginPresentationService
{
    private const PRESETS = [
        'split' => 'Dividido',
        'centered' => 'Centralizado',
        'corporate' => 'Corporativo',
        'editorial' => 'Editorial',
        'minimal' => 'Minimalista',
        'institutional' => 'Institucional',
        'highlights' => 'Destaques',
        'wide' => 'Panorâmico',
    ];

    // Fontes tipográficas já disponíveis no repositório; nunca sobrescrever fontes de ícones.
    private const FONTS = [
        'poppins' => [
            'label' => 'Poppins',
            400 => 'login2/fonts/poppins/Poppins-Regular.ttf',
            600 => 'login2/fonts/poppins/Poppins-SemiBold.ttf',
            700 => 'login2/fonts/poppins/Poppins-Bold.ttf',
        ],
        'montserrat' => [
            'label' => 'Montserrat',
            400 => 'login2/fonts/montserrat/Montserrat-Regular.ttf',
            600 => 'login2/fonts/montserrat/Montserrat-SemiBold.ttf',
            700 => 'login2/fonts/montserrat/Montserrat-Bold.ttf',
        ],
    ];

    private const DEFAULT_ASSETS = [
        'login_logo' => 'imgs/slym2.png',
        'menu_logo' => 'imgs/slym.png',
        'favicon' => 'imgs/favicon.png',
        'hero' => 'imgs/login2.png',
    ];

    public function presets(): array
    {
        return self::PRESETS;
    }

    public function fonts(): array
    {
        return array_map(static fn (array $font) => $font['label'], self::FONTS);
    }

    public function fontFiles(string $font): array
    {
        // Somente IDs homologados pelo sistema; nunca URLs ou CSS arbitrários.
        return self::FONTS[$font]
            ?? throw new \InvalidArgumentException('A tipografia selecionada não está homologada.');
    }

    public function validLayout(string $layout): bool
    {
        return array_key_exists($layout, self::PRESETS);
    }

    public function validFont(string $font): bool
    {
        return array_key_exists($font, self::FONTS);
    }

    public function modelsReady(): bool
    {
        return Schema::hasTable('login_presentations');
    }

    public function currentModel(): ?LoginPresentation
    {
        if (!$this->modelsReady()) {
            return null;
        }

        return Cache::remember('login.presentation.active', 60, static function () {
            return LoginPresentation::query()
                ->where('is_active', true)
                ->whereNotNull('published_at')
                ->orderByDesc('published_at')
                ->first();
        });
    }

    public function configuration(?LoginPresentation $preview = null): array
    {
        $model = $preview ?? $this->currentModel();

        if ($model) {
            // Depois da publicação, o modelo publicado é a única fonte da aparência.
            if (!$this->validLayout((string) $model->layout) || !$this->validFont((string) $model->font)) {
                throw new \RuntimeException('O modelo publicado contém composição ou tipografia inválida.');
            }

            $layout = $model->layout;
            $font = $model->font;
            $settings = is_array($model->settings) ? $model->settings : [];
        } else {
            // Estado inicial apenas antes da primeira publicação.
            $layout = (string) config('login_presentation.initial_layout', 'split');
            $font = (string) config('login_presentation.initial_font', 'poppins');
            if (!$this->validLayout($layout) || !$this->validFont($font)) {
                throw new \RuntimeException('Modelo ou fonte inicial inválidos no ambiente.');
            }
            $settings = [
                'headline' => '',
                'tagline' => (string) config('login_presentation.initial_tagline', ''),
                'show_signup' => true,
                'show_support' => true,
                'show_phrases' => true,
                'show_posts' => true,
                'show_hero' => true,
                'accent_color' => '#1f9866',
                'background_color' => '#f5f7f7',
                'rotation_seconds' => 8,
            ];
        }

        return [
            'model_id' => $model?->id,
            'model_name' => $model?->name,
            'name' => (string) config('app.name'),
            'layout' => $layout,
            'font' => $font,
            'font_files' => $this->fontFiles($font),
            'headline' => (string) ($settings['headline'] ?? ''),
            'tagline' => (string) ($settings['tagline'] ?? ''),
            'accent' => $this->safeColor($settings['accent_color'] ?? null, '#1f9866'),
            'background' => $this->safeColor($settings['background_color'] ?? null, '#f5f7f7'),
            'show_signup' => (bool) ($settings['show_signup'] ?? false),
            'show_support' => (bool) ($settings['show_support'] ?? false),
            'show_phrases' => (bool) ($settings['show_phrases'] ?? false),
            'show_posts' => (bool) ($settings['show_posts'] ?? false),
            'show_hero' => (bool) ($settings['show_hero'] ?? false),
            'rotation_seconds' => max(4, min(30, (int) ($settings['rotation_seconds'] ?? 8))),
            'assets' => $this->assetUrls(),
            'contents' => $this->visibleContents($model?->id),
        ];
    }

    public function safeColor($value, string $default): string
    {
        return is_string($value) && preg_match('/^#[0-9a-fA-F]{6}$/D', $value)
            ? $value
            : $default;
    }

    public function assetUrls(): array
    {
        $urls = array_map(static fn (string $path) => asset($path), self::DEFAULT_ASSETS);

        if (!Schema::hasTable('login_presentation_assets')) {
            return $urls;
        }

        $active = Cache::remember('login.presentation.assets', 60, static function () {
            return LoginPresentationAsset::query()
                ->where('is_active', true)
                ->orderByDesc('id')
                ->get()
                ->unique('asset_key');
        });

        foreach ($active as $asset) {
            if (!array_key_exists($asset->asset_key, self::DEFAULT_ASSETS)) {
                continue;
            }

            // Resolver uma única imagem no servidor, sem requisitar primeiro a antiga.
            // Quando o arquivo publicado foi perdido, manter a identidade anterior
            // até o superusuário republicar uma versão válida.
            if (!Storage::disk('public')->exists($asset->path)) {
                continue;
            }

            $urls[$asset->asset_key] = Storage::disk('public')->url($asset->path)
                . '?v=' . substr((string) $asset->sha256, 0, 12);
        }

        return $urls;
    }

    public function faviconPath(): string
    {
        if (!Schema::hasTable('login_presentation_assets')) {
            return public_path(self::DEFAULT_ASSETS['favicon']);
        }

        $active = LoginPresentationAsset::query()
            ->where('asset_key', 'favicon')
            ->where('is_active', true)
            ->orderByDesc('id')
            ->first();

        if ($active && Storage::disk('public')->exists($active->path)) {
            return Storage::disk('public')->path($active->path);
        }

        return public_path(self::DEFAULT_ASSETS['favicon']);
    }

    public function visibleContents(?int $modelId = null): array
    {
        if (!Schema::hasTable('login_presentation_contents')) {
            return ['phrases' => [], 'posts' => [], 'notices' => []];
        }

        $cacheKey = 'login.presentation.contents.' . ($modelId ?: 'global');

        return Cache::remember($cacheKey, 15, static function () use ($modelId) {
            $query = LoginPresentationContent::query()
                ->where('status', 'published')
                ->where(static fn ($q) => $q->whereNull('starts_at')->orWhere('starts_at', '<=', now()))
                ->where(static fn ($q) => $q->whereNull('ends_at')->orWhere('ends_at', '>', now()))
                ->where(static function ($q) use ($modelId) {
                    $q->whereNull('presentation_id');
                    if ($modelId !== null) {
                        $q->orWhere('presentation_id', $modelId);
                    }
                })
                ->orderBy('sort_order')
                ->orderByDesc('id')
                ->limit(45)
                ->get();

            $mapped = ['phrases' => [], 'posts' => [], 'notices' => []];

            foreach ($query as $item) {
                $type = match ($item->type) {
                    'phrase' => 'phrases',
                    'post' => 'posts',
                    'notice' => 'notices',
                    default => null,
                };

                if ($type === null || count($mapped[$type]) >= 15) {
                    continue;
                }

                $mapped[$type][] = [
                    'title' => (string) ($item->title ?? ''),
                    'body' => (string) $item->body,
                    'image' => $item->image_path
                        ? Storage::disk('public')->url($item->image_path)
                        : null,
                    'url' => $item->link_url,
                ];
            }

            return $mapped;
        });
    }

    public function clearCache(?int $modelId = null): void
    {
        Cache::forget('login.presentation.active');
        Cache::forget('login.presentation.assets');
        Cache::forget('login.presentation.contents.global');

        if ($modelId !== null) {
            Cache::forget('login.presentation.contents.' . $modelId);
        }
    }
}
