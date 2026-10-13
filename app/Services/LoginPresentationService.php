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


    /**
     * A definição original nunca é salva nem alterada no banco.
     * Editá-la na administração gera uma nova versão persistida.
     */
    public function defaultTemplate(): array
    {
        return [
            'name' => 'Padrão original',
            'description' => 'A composição original da access_4, sempre disponível para restauração.',
            'layout' => 'split',
            'font' => 'poppins',
            'settings' => [
                'headline' => '',
                'tagline' => (string) config('login_presentation.initial_tagline', ''),
                'accent_color' => '#1f9866',
                'background_color' => '#f5f7f7',
                'rotation_seconds' => 8,
                'show_signup' => true,
                'show_support' => true,
                'show_phrases' => true,
                'show_posts' => true,
                'show_hero' => true,
            ],
        ];
    }

    /**
     * Biblioteca permanente: modelos completos, prontos para prévia, aplicação
     * ou personalização, mesmo quando não há registros no banco de dados.
     * Nenhuma consulta ou inserção automática acontece ao abrir a administração.
     */
    public function readyTemplates(): array
    {
        $defaults = $this->defaultTemplate()['settings'];

        $items = [
            'split' => [
                'name' => 'Clássico dividido',
                'description' => 'Apresentação institucional e formulário lado a lado.',
                'font' => 'poppins',
                'settings' => [
                    'headline' => 'Seu trabalho em um só lugar.',
                    'tagline' => 'Acesse seus recursos com praticidade e segurança.',
                    'accent_color' => '#16885d',
                    'background_color' => '#f3f8f5',
                ],
            ],
            'centered' => [
                'name' => 'Centralizado',
                'description' => 'Experiência direta, com foco absoluto nas credenciais.',
                'font' => 'montserrat',
                'settings' => [
                    'headline' => 'Tudo pronto para começar.',
                    'tagline' => 'Entre na sua conta para continuar.',
                    'accent_color' => '#2563a6',
                    'background_color' => '#f0f5fb',
                ],
            ],
            'corporate' => [
                'name' => 'Corporativo',
                'description' => 'Apresentação sóbria para operações empresariais.',
                'font' => 'montserrat',
                'settings' => [
                    'headline' => 'Sua operação, conectada.',
                    'tagline' => 'Gestão integrada com um acesso simples e seguro.',
                    'accent_color' => '#188d76',
                    'background_color' => '#18362f',
                ],
            ],
            'editorial' => [
                'name' => 'Editorial',
                'description' => 'Tipografia em destaque e composição com personalidade.',
                'font' => 'poppins',
                'settings' => [
                    'headline' => 'Boas ideias começam aqui.',
                    'tagline' => 'Entre para acompanhar e organizar suas atividades.',
                    'accent_color' => '#946a3d',
                    'background_color' => '#f5f1eb',
                ],
            ],
            'minimal' => [
                'name' => 'Minimalista',
                'description' => 'Poucos elementos e atenção total ao formulário.',
                'font' => 'poppins',
                'settings' => [
                    'headline' => '',
                    'tagline' => '',
                    'accent_color' => '#202e38',
                    'background_color' => '#f7f9f8',
                    'show_posts' => false,
                    'show_phrases' => false,
                    'show_hero' => false,
                ],
            ],
            'institutional' => [
                'name' => 'Institucional',
                'description' => 'Equilíbrio entre identidade, informações e credenciais.',
                'font' => 'montserrat',
                'settings' => [
                    'headline' => 'Bem-vindo ao seu ambiente.',
                    'tagline' => 'Uma experiência organizada para o seu dia a dia.',
                    'accent_color' => '#336e79',
                    'background_color' => '#edf5f5',
                ],
            ],
            'highlights' => [
                'name' => 'Destaques',
                'description' => 'Valoriza notícias, avisos e comunicados publicados.',
                'font' => 'poppins',
                'settings' => [
                    'headline' => 'Fique por dentro.',
                    'tagline' => 'Acompanhe atualizações e acesse o seu ambiente.',
                    'accent_color' => '#2a8650',
                    'background_color' => '#edf8f0',
                ],
            ],
            'wide' => [
                'name' => 'Panorâmico',
                'description' => 'Amplo espaço institucional com acesso em destaque.',
                'font' => 'montserrat',
                'settings' => [
                    'headline' => 'Mais espaço para suas ideias.',
                    'tagline' => 'Tudo o que você precisa para seguir em frente.',
                    'accent_color' => '#316d94',
                    'background_color' => '#eaf3f8',
                ],
            ],
        ];

        $templates = [];
        foreach ($items as $layout => $item) {
            $templates[$layout] = [
                'name' => $item['name'],
                'description' => $item['description'],
                'layout' => $layout,
                'font' => $item['font'],
                'settings' => array_replace($defaults, $item['settings']),
            ];
        }

        return $templates;
    }

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
            // O padrão de fábrica permanece imutável e pode ser restaurado.
            $default = $this->defaultTemplate();
            $layout = $default['layout'];
            $font = $default['font'];
            $settings = $default['settings'];
        }

        $assets = $this->assetUrls();

        return [
            'model_id' => $model?->id,
            'model_name' => $model?->name,
            'name' => (string) config('app.name'),
            'layout' => $layout,
            'font' => $font,
            'font_files' => $this->fontFiles($font),
            'headline' => (string) ($settings['headline'] ?? ''),
            'tagline' => (string) ($settings['tagline'] ?? ''),
            'texts' => $this->presentationTexts($settings),
            'accent' => $this->safeColor($settings['accent_color'] ?? null, '#1f9866'),
            'background' => $this->safeColor($settings['background_color'] ?? null, '#f5f7f7'),
            'show_signup' => (bool) ($settings['show_signup'] ?? false),
            'show_support' => (bool) ($settings['show_support'] ?? false),
            'show_phrases' => (bool) ($settings['show_phrases'] ?? false),
            'show_posts' => (bool) ($settings['show_posts'] ?? false),
            'show_hero' => (bool) ($settings['show_hero'] ?? false),
            'rotation_seconds' => max(4, min(30, (int) ($settings['rotation_seconds'] ?? 8))),
            // A imagem histórica de apresentação repete a marca da logo.
            // Evita a duplicação até que uma imagem específica seja publicada.
            'hero_is_custom' => $assets['hero'] !== asset(self::DEFAULT_ASSETS['hero']),
            'assets' => $assets,
            'contents' => $this->visibleContents($model?->id),
        ];
    }

    /**
     * Resolve os seis textos visíveis sem modificar modelos salvos ou o padrão.
     * Modelos anteriores mantêm exatamente o fallback que existia nas Blades.
     * Ao editar pelo novo formulário, uma string vazia oculta somente esse texto.
     */
    public function presentationTexts(array $settings): array
    {
        $headline = is_string($settings['headline'] ?? null) ? $settings['headline'] : '';
        $tagline = is_string($settings['tagline'] ?? null) ? $settings['tagline'] : '';
        $hasHeroText = $headline !== '' || $tagline !== '';
        $legacyFallback = !array_key_exists('hero_kicker', $settings) && !$hasHeroText;

        $texts = [
            'hero_kicker' => $hasHeroText ? 'Seu espaço de trabalho' : 'Seu ambiente',
            'headline' => $legacyFallback ? 'Tudo pronto para continuar.' : $headline,
            'tagline' => $legacyFallback
                ? 'Acesse seu ambiente de trabalho com segurança e praticidade.'
                : $tagline,
            'form_kicker' => 'Área de acesso',
            'form_title' => 'Acesse sua conta',
            'form_subtitle' => 'Informe suas credenciais para continuar.',
        ];

        foreach (['hero_kicker', 'form_kicker', 'form_title', 'form_subtitle'] as $key) {
            if (array_key_exists($key, $settings)
                && (is_string($settings[$key]) || $settings[$key] === null)) {
                // Laravel converte entradas vazias em null durante a validação.
                $texts[$key] = (string) ($settings[$key] ?? '');
            }
        }

        return $texts;
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
