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

    public function index()
    {
        $this->requireTables();

        return view('config.login_appearance.index', [
            'title' => 'Aparência & Comunicação',
            'models' => LoginPresentation::query()->orderByDesc('is_active')->orderByDesc('id')->get(),
            'presets' => $this->presentation->presets(),
            'fonts' => $this->presentation->fonts(),
            'contents' => LoginPresentationContent::query()->orderByDesc('id')->limit(150)->get(),
            'assets' => LoginPresentationAsset::query()->orderBy('asset_key')->orderByDesc('id')->limit(100)->get(),
            'active' => $this->presentation->currentModel(),
        ]);
    }

    private function validatedModelData(Request $request): array
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:100'],
            'layout' => ['required', Rule::in(array_keys($this->presentation->presets()))],
            'font' => ['required', Rule::in(array_keys($this->presentation->fonts()))],
            'headline' => ['nullable', 'string', 'max:180'],
            'tagline' => ['nullable', 'string', 'max:1500'],
            'accent_color' => ['required', 'regex:/^#[0-9a-fA-F]{6}$/'],
            'background_color' => ['required', 'regex:/^#[0-9a-fA-F]{6}$/'],
            'rotation_seconds' => ['required', 'integer', 'between:4,30'],
        ]);

        return [
            'name' => $data['name'],
            'layout' => $data['layout'],
            'font' => $data['font'],
            'settings' => [
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

        LoginPresentation::query()->create([
            ...$data,
            'slug' => $this->uniqueSlug($data['name']),
            'is_active' => false,
            'published_at' => null,
        ]);

        return redirect('/config/aparencia')->with('mensagem_sucesso', 'Rascunho criado. Revise antes de publicar.');
    }

    public function updateModel(Request $request, LoginPresentation $model)
    {
        $this->requireTables();
        $data = $this->validatedModelData($request);

        if ($model->is_active) {
            // Nunca atualizar a versão publicada durante edição ou prévia.
            LoginPresentation::query()->create([
                ...$data,
                'slug' => $this->uniqueSlug($data['name']),
                'is_active' => false,
                'published_at' => null,
            ]);

            return redirect('/config/aparencia')->with(
                'mensagem_sucesso',
                'Alterações salvas em novo rascunho. O modelo publicado continua intacto.'
            );
        }

        $model->update($data);

        return redirect('/config/aparencia')->with('mensagem_sucesso', 'Rascunho atualizado.');
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
