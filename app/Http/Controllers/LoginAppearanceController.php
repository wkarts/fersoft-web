<?php

namespace App\Http\Controllers;

use App\Models\LoginPresentation;
use App\Models\LoginPresentationAsset;
use App\Models\LoginPresentationContent;
use App\Services\LoginPresentationService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class LoginAppearanceController extends Controller
{
    public function __construct(private LoginPresentationService $presentation)
    {
        $this->middleware(function ($request, $next) {
            $session = session('user_logged');

            if (!$session) {
                return redirect('/login');
            }

            // Proteção no servidor, independentemente dos menus e botões.
            abort_unless((bool) ($session['super'] ?? false), 403);

            return $next($request);
        });
    }

    private function requireTables(): void
    {
        abort_unless(
            Schema::hasTable('login_presentations')
            && Schema::hasTable('login_presentation_contents')
            && Schema::hasTable('login_presentation_assets'),
            503,
            'Execute as migrations aditivas antes de administrar a tela de acesso.'
        );
    }

    public function index(Request $request)
    {
        $this->requireTables();

        $readyTemplates = $this->presentation->readyTemplates();
        $defaultTemplate = $this->presentation->defaultTemplate();
        $editorTemplate = $defaultTemplate;
        $editorModel = null;
        $editorOrigin = 'default';

        if ($request->filled('edit')) {
            $editorModel = LoginPresentation::query()->findOrFail(
                (int) $request->query('edit')
            );
            $editorTemplate = [
                'name' => $editorModel->name,
                'layout' => $editorModel->layout,
                'font' => $editorModel->font,
                'settings' => is_array($editorModel->settings) ? $editorModel->settings : [],
            ];
            $editorOrigin = 'model';
        } elseif ($request->filled('preset')) {
            $presetId = (string) $request->query('preset');
            abort_unless(array_key_exists($presetId, $readyTemplates), 404);
            $editorTemplate = $readyTemplates[$presetId];
            $editorOrigin = 'preset';
        }

        return view('config.login_appearance.index', [
            'readyTemplates' => $readyTemplates,
            'defaultTemplate' => $defaultTemplate,
            'editorTemplate' => $editorTemplate,
            'editorTexts' => $this->presentation->presentationTexts($editorTemplate['settings']),
            'editorModel' => $editorModel,
            'editorOrigin' => $editorOrigin,
            'title' => 'Aparência & Comunicação',
            'models' => LoginPresentation::query()->orderByDesc('is_active')->orderByDesc('id')->get(),
            'presets' => $this->presentation->presets(),
            'fonts' => $this->presentation->fonts(),
            'contents' => LoginPresentationContent::query()->orderByDesc('id')->limit(150)->get(),
            'assets' => LoginPresentationAsset::query()->orderBy('asset_key')->orderByDesc('id')->limit(100)->get(),
            'active' => $this->presentation->currentModel(),
            'draftCount' => LoginPresentation::query()->where('is_active', false)->count(),
            'publishedContentsCount' => LoginPresentationContent::query()->where('status', 'published')->count(),
        ]);
    }

    private function validatedModelData(Request $request, ?LoginPresentation $model = null): array
    {
        $data = $request->validate([
            ...\App\Services\LoginLogoSettings::rules(),
            'name' => ['required', 'string', 'max:100'],
            'layout' => ['required', Rule::in(array_keys($this->presentation->presets()))],
            'font' => ['required', Rule::in(array_keys($this->presentation->fonts()))],
            'headline' => ['nullable', 'string', 'max:180'],
            'tagline' => ['nullable', 'string', 'max:1500'],
            'hero_kicker' => ['sometimes', 'nullable', 'string', 'max:100'],
            'form_kicker' => ['sometimes', 'nullable', 'string', 'max:100'],
            'form_title' => ['sometimes', 'nullable', 'string', 'max:180'],
            'form_subtitle' => ['sometimes', 'nullable', 'string', 'max:1500'],
            'accent_color' => ['required', 'regex:/^#[0-9a-fA-F]{6}$/'],
            'background_color' => ['required', 'regex:/^#[0-9a-fA-F]{6}$/'],
            'rotation_seconds' => ['required', 'integer', 'between:4,30'],
        ]);

        // Clientes/formulários anteriores podem não enviar os campos novos.
        // Ausência preserva a personalização; envio vazio oculta o texto.
        $settings = is_array($model?->settings) ? $model->settings : [];
        if (array_key_exists('logo', $data)) {
            $currentLogo = \App\Services\LoginLogoSettings::normalize($settings['logo'] ?? []);
            $settings['logo'] = \App\Services\LoginLogoSettings::normalize(
                array_replace_recursive($currentLogo, $data['logo'])
            );
        }
        foreach (['hero_kicker', 'form_kicker', 'form_title', 'form_subtitle'] as $key) {
            if (array_key_exists($key, $data)) {
                $settings[$key] = (string) ($data[$key] ?? '');
            }
        }

        return [
            'name' => $data['name'],
            'layout' => $data['layout'],
            'font' => $data['font'],
            'settings' => [
                ...$settings,
                'headline' => $data['headline'] ?? '',
                'tagline' => $data['tagline'] ?? '',
                'accent_color' => $data['accent_color'],
                'background_color' => $data['background_color'],
                'rotation_seconds' => (int) $data['rotation_seconds'],
                'show_signup' => $request->boolean('show_signup'),
                'show_support' => $request->boolean('show_support'),
                'show_phrases' => $request->boolean('show_phrases'),
                'show_posts' => $request->boolean('show_posts'),
                'show_hero' => $request->boolean('show_hero'),
            ],
        ];
    }

    private function uniqueSlug(string $name): string
    {
        return Str::limit(Str::slug($name), 68, '')
            . '-' . Str::lower(Str::random(10));
    }

    public function storeModel(Request $request)
    {
        $this->requireTables();
        $data = $this->validatedModelData($request);

        $model = LoginPresentation::query()->create([
            ...$data,
            'slug' => $this->uniqueSlug($data['name']),
            'is_active' => false,
            'published_at' => null,
        ]);

        // Editar o padrão é criar uma versão automática. O original nunca é sobrescrito.
        if ($request->boolean('publish_now')) {
            return $this->publishModel($model);
        }

        return redirect('/config/aparencia?tab=editor&edit=' . $model->id)
            ->with('mensagem_sucesso', 'Rascunho salvo. O padrão original permanece disponível.');
    }

    public function updateModel(Request $request, LoginPresentation $model)
    {
        $this->requireTables();
        $data = $this->validatedModelData($request, $model);

        if ($model->is_active) {
            // Publicações não são editadas: salvamos uma nova versão independente.
            $version = LoginPresentation::query()->create([
                ...$data,
                'slug' => $this->uniqueSlug($data['name']),
                'is_active' => false,
                'published_at' => null,
            ]);

            if ($request->boolean('publish_now')) {
                return $this->publishModel($version);
            }

            return redirect('/config/aparencia?tab=editor&edit=' . $version->id)->with(
                'mensagem_sucesso',
                'Nova versão criada. A publicação anterior permanece intacta.'
            );
        }

        $model->update($data);

        if ($request->boolean('publish_now')) {
            return $this->publishModel($model);
        }

        return redirect('/config/aparencia?tab=editor&edit=' . $model->id)
            ->with('mensagem_sucesso', 'Rascunho atualizado.');
    }

    public function duplicateModel(LoginPresentation $model)
    {
        $this->requireTables();

        LoginPresentation::query()->create([
            'name' => $model->name . ' (cópia)',
            'slug' => $this->uniqueSlug($model->name),
            'layout' => $model->layout,
            'font' => $model->font,
            'settings' => $model->settings,
            'is_active' => false,
            'published_at' => null,
        ]);

        return redirect('/config/aparencia')->with('mensagem_sucesso', 'Modelo duplicado como rascunho.');
    }

    public function publishModel(LoginPresentation $model)
    {
        $this->requireTables();
        abort_unless(
            $this->presentation->validLayout($model->layout)
            && $this->presentation->validFont($model->font),
            422,
            'Modelo ou tipografia inválidos.'
        );

        DB::transaction(function () use ($model) {
            // Serialização para evitar duas publicações simultâneas.
            LoginPresentation::query()->lockForUpdate()->get(['id']);
            LoginPresentation::query()->where('is_active', true)->update(['is_active' => false]);
            $model->is_active = true;
            $model->published_at = now();
            $model->save();
        });

        $this->presentation->clearCache((int) $model->id);

        return redirect('/config/aparencia')->with('mensagem_sucesso', 'Novo modelo de acesso publicado.');
    }


    /**
     * Oito modelos disponíveis sem seed ou inserção automática.
     * A publicação cria um registro versionado com os parâmetros completos.
     */
    public function applyReadyTemplate(string $layout)
    {
        $this->requireTables();
        $template = $this->presentation->readyTemplates()[$layout] ?? null;
        abort_unless($template !== null, 404);

        $model = LoginPresentation::query()->create([
            'name' => $template['name'],
            'slug' => $this->uniqueSlug($template['name']),
            'layout' => $template['layout'],
            'font' => $template['font'],
            'settings' => $template['settings'],
            'is_active' => false,
            'published_at' => null,
        ]);

        return $this->publishModel($model);
    }

    /**
     * Restaurar o padrão não apaga nem altera qualquer modelo ou conteúdo.
     */
    public function restoreDefault()
    {
        $this->requireTables();

        DB::transaction(function () {
            LoginPresentation::query()->lockForUpdate()->get(['id']);
            LoginPresentation::query()->where('is_active', true)->update(['is_active' => false]);
        });

        $this->presentation->clearCache();

        return redirect('/config/aparencia?tab=modelos')
            ->with('mensagem_sucesso', 'Padrão original restaurado. Todos os modelos foram preservados.');
    }

    private function previewTemplateData(array $template)
    {
        $temporary = new LoginPresentation([
            'name' => $template['name'],
            'layout' => $template['layout'],
            'font' => $template['font'],
            'settings' => $template['settings'],
        ]);

        return view('login.access_4', [
            'presentation' => $this->presentation->configuration($temporary),
            'sessaoAtiva' => false,
            'previewMode' => true,
            'loginCookie' => '',
            'senhaCookie' => '',
            'lembrarCookie' => '',
        ]);
    }

    public function previewDefault()
    {
        $this->requireTables();
        return $this->previewTemplateData($this->presentation->defaultTemplate());
    }

    public function previewReadyTemplate(string $layout)
    {
        $this->requireTables();
        $template = $this->presentation->readyTemplates()[$layout] ?? null;
        abort_unless($template !== null, 404);

        return $this->previewTemplateData($template);
    }

    public function preview(LoginPresentation $model)
    {
        $this->requireTables();

        return view('login.access_4', [
            'presentation' => $this->presentation->configuration($model),
            'sessaoAtiva' => false,
            'previewMode' => true,
            'loginCookie' => '',
            'senhaCookie' => '',
            'lembrarCookie' => '',
        ]);
    }

    private function validateContent(Request $request): array
    {
        $data = $request->validate([
            'type' => ['required', Rule::in(['phrase', 'post', 'notice'])],
            'title' => ['nullable', 'string', 'max:200'],
            'body' => ['required', 'string', 'max:4000'],
            'link_url' => ['nullable', 'url', 'max:2048', 'starts_with:https://'],
            'status' => ['required', Rule::in(['draft', 'published', 'archived'])],
            'sort_order' => ['nullable', 'integer', 'between:-100000,100000'],
            'presentation_id' => ['nullable', 'integer', 'exists:login_presentations,id'],
            'starts_at' => ['nullable', 'date'],
            'ends_at' => ['nullable', 'date', 'after:starts_at'],
            'image' => ['nullable', 'image', 'mimes:png,jpg,jpeg,webp', 'max:5120'],
        ]);

        unset($data['image']);
        $data['sort_order'] = (int) ($data['sort_order'] ?? 0);
        $data['presentation_id'] = !empty($data['presentation_id'])
            ? (int) $data['presentation_id']
            : null;

        return $data;
    }

    public function storeContent(Request $request)
    {
        $this->requireTables();
        $data = $this->validateContent($request);

        if ($request->hasFile('image')) {
            $data['image_path'] = $this->storeImage($request->file('image'), 'posts');
        }

        $data['created_by'] = (int) (session('user_logged')['id'] ?? 0) ?: null;
        LoginPresentationContent::query()->create($data);
        $this->presentation->clearCache($data['presentation_id']);

        return redirect('/config/aparencia')->with('mensagem_sucesso', 'Conteúdo salvo.');
    }

    public function updateContent(Request $request, LoginPresentationContent $content)
    {
        $this->requireTables();
        $data = $this->validateContent($request);
        $oldModelId = $content->presentation_id;

        if ($request->hasFile('image')) {
            $data['image_path'] = $this->storeImage($request->file('image'), 'posts');
        }

        $content->update($data);
        $this->presentation->clearCache($oldModelId);
        $this->presentation->clearCache($data['presentation_id']);

        return redirect('/config/aparencia')->with('mensagem_sucesso', 'Conteúdo atualizado.');
    }

    public function archiveContent(LoginPresentationContent $content)
    {
        $this->requireTables();
        $content->status = 'archived';
        $content->save();
        $this->presentation->clearCache($content->presentation_id);

        return redirect('/config/aparencia')->with('mensagem_sucesso', 'Conteúdo arquivado.');
    }

    private function storeImage($file, string $folder): string
    {
        $hash = hash_file('sha256', $file->getRealPath());
        $imageInfo = @getimagesize($file->getRealPath());
        abort_unless(is_array($imageInfo), 422, 'O arquivo não é uma imagem válida.');

        $extension = match ($imageInfo['mime'] ?? '') {
            'image/png' => 'png',
            'image/jpeg' => 'jpg',
            'image/webp' => 'webp',
            default => null,
        };
        abort_unless($extension !== null, 422, 'Formato de imagem não permitido.');

        $path = Storage::disk('public')->putFileAs(
            'appearance/' . $folder,
            $file,
            $hash . '.' . $extension
        );

        abort_unless($path !== false, 503, 'Não foi possível armazenar a imagem.');
        return $path;
    }

    public function storeAsset(Request $request, string $key)
    {
        $this->requireTables();
        abort_unless(in_array($key, ['login_logo', 'menu_logo', 'favicon', 'hero'], true), 404);

        $request->validate([
            'image' => [
                'required', 'image',
                $key === 'favicon' ? 'mimes:png' : 'mimes:png,jpg,jpeg,webp',
                'max:5120',
            ],
        ]);

        $file = $request->file('image');
        $dimensions = @getimagesize($file->getRealPath());
        abort_unless(is_array($dimensions), 422, 'Imagem inválida.');

        if ($key === 'favicon') {
            abort_unless(
                $dimensions[0] === $dimensions[1] && $dimensions[0] >= 192,
                422,
                'O favicon deve ser quadrado, PNG, com pelo menos 192 x 192 pixels.'
            );
        }

        $path = $this->storeImage($file, $key);

        LoginPresentationAsset::query()->create([
            'asset_key' => $key,
            'path' => $path,
            'mime_type' => $dimensions['mime'],
            'sha256' => hash_file('sha256', $file->getRealPath()),
            'width' => (int) $dimensions[0],
            'height' => (int) $dimensions[1],
            'size_bytes' => (int) $file->getSize(),
            'is_active' => false,
            'created_by' => (int) (session('user_logged')['id'] ?? 0) ?: null,
        ]);

        return redirect('/config/aparencia')->with(
            'mensagem_sucesso',
            'Arquivo recebido como rascunho. Publique a versão escolhida.'
        );
    }

    public function publishAsset(LoginPresentationAsset $asset)
    {
        $this->requireTables();
        abort_unless(Storage::disk('public')->exists($asset->path), 422, 'Arquivo não encontrado.');

        DB::transaction(function () use ($asset) {
            LoginPresentationAsset::query()
                ->where('asset_key', $asset->asset_key)
                ->lockForUpdate()->get(['id']);

            LoginPresentationAsset::query()
                ->where('asset_key', $asset->asset_key)
                ->where('is_active', true)
                ->update(['is_active' => false]);

            $asset->is_active = true;
            $asset->save();
        });

        $this->presentation->clearCache();

        return redirect('/config/aparencia')->with('mensagem_sucesso', 'Arquivo visual publicado.');
    }
}