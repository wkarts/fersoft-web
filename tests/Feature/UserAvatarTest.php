<?php

namespace Tests\Feature;

use App\Http\Middleware\VerificaEmpresa;
use App\Models\Usuario;
use App\Services\UserAvatarService;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class UserAvatarTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        config([
            'database.default' => 'sqlite',
            'database.connections.sqlite.database' => ':memory:',
            'session.driver' => 'array',
        ]);
        DB::purge('sqlite');
        Schema::clearResolvedInstance('db.schema');
        Schema::create('usuarios', function (Blueprint $table) {
            $table->increments('id');
            $table->unsignedInteger('empresa_id');
            $table->string('nome');
            $table->string('img', 100)->default('');
            $table->timestamps();
        });
    }

    public function test_profile_routes_keep_company_authentication_without_admin_permission(): void
    {
        foreach (['usuarios.profile', 'usuarios.profile.photo'] as $name) {
            $route = Route::getRoutes()->getByName($name);
            $this->assertNotNull($route);
            $this->assertContains('verificaEmpresa', $route->gatherMiddleware());
            $this->assertNotContains('validaAcesso', $route->gatherMiddleware());
        }
    }

    public function test_only_logged_user_receives_uploaded_photo_and_session_is_refreshed(): void
    {
        $mine = Usuario::create(['empresa_id' => 8, 'nome' => 'Ana', 'img' => '']);
        $other = Usuario::create(['empresa_id' => 9, 'nome' => 'Bruno', 'img' => '']);

        // O middleware de empresa já é verificado na rota; o banco mínimo do
        // teste exercita a autorização explícita do endpoint pelo id da sessão.
        $response = $this->withoutMiddleware(VerificaEmpresa::class)
            ->withSession(['user_logged' => ['id' => $mine->id, 'empresa' => 8]])
            ->post(route('usuarios.profile.photo'), [
                'foto' => UploadedFile::fake()->image('retrato.png', 80, 80),
                'id' => $other->id,
                'empresa_id' => 9,
            ]);

        $response->assertRedirect(route('usuarios.profile'));
        $filename = (string) $mine->fresh()->img;
        try {
            $this->assertMatchesRegularExpression('/^[a-f0-9-]{36}\\.png$/', $filename);
            $this->assertFileExists(public_path('foto_usuario/' . $filename));
            $this->assertSame($filename, session('user_logged.img'));
            $this->assertSame('', $other->fresh()->img);
            $this->assertNotNull(app(UserAvatarService::class)->currentUserUrl());
        } finally {
            if ($filename !== '') {
                @unlink(public_path('foto_usuario/' . $filename));
            }
        }
    }

    public function test_rejects_non_image_and_does_not_use_untrusted_path(): void
    {
        $mine = Usuario::create(['empresa_id' => 8, 'nome' => 'Ana', 'img' => '']);

        $this->withoutMiddleware(VerificaEmpresa::class)
            ->withSession(['user_logged' => ['id' => $mine->id, 'empresa' => 8]])
            ->from(route('usuarios.profile'))
            ->post(route('usuarios.profile.photo'), [
                'foto' => UploadedFile::fake()->create('script.svg', 1, 'image/svg+xml'),
            ])
            ->assertSessionHasErrors('foto');

        $this->assertSame('', $mine->fresh()->img);
        $this->assertNull(app(UserAvatarService::class)->url('../.env'));
        $this->assertNull(app(UserAvatarService::class)->url('script.svg'));
    }

    public function test_session_company_must_match_the_user_before_upload(): void
    {
        $mine = Usuario::create(['empresa_id' => 8, 'nome' => 'Ana', 'img' => '']);

        $this->withoutMiddleware(VerificaEmpresa::class)
            ->withSession(['user_logged' => ['id' => $mine->id, 'empresa' => 9]])
            ->post(route('usuarios.profile.photo'), [
                'foto' => UploadedFile::fake()->image('retrato.png', 80, 80),
            ])
            ->assertNotFound();

        $this->assertSame('', $mine->fresh()->img);
    }

    public function test_newly_created_user_can_store_photo_in_existing_mobile_contract(): void
    {
        $created = Usuario::create(['empresa_id' => 8, 'nome' => 'Novo', 'img' => '']);
        $filename = app(UserAvatarService::class)
            ->store($created, UploadedFile::fake()->image('novo.jpg', 64, 64));

        try {
            $this->assertSame($filename, $created->fresh()->img);
            $this->assertFileExists(public_path('foto_usuario/' . $filename));
            $this->assertStringContainsString('/foto_usuario/', (string) app(UserAvatarService::class)->url($filename));
        } finally {
            @unlink(public_path('foto_usuario/' . $filename));
        }
    }

    public function test_old_session_hydrates_avatar_only_from_its_company(): void
    {
        $usuario = Usuario::create(['empresa_id' => 8, 'nome' => 'Ana', 'img' => 'user.png']);
        session()->put('user_logged', ['id' => $usuario->id, 'empresa' => 9, 'nome' => 'Ana']);

        $this->assertNull(app(UserAvatarService::class)->currentUserUrl());
        $this->assertSame('', session('user_logged.img'));

        session()->put('user_logged', ['id' => $usuario->id, 'empresa' => 8, 'nome' => 'Ana']);
        $this->assertStringContainsString('/foto_usuario/user.png', (string) app(UserAvatarService::class)->currentUserUrl());
        $this->assertSame('user.png', session('user_logged.img'));
        $this->assertSame('Ana', session('user_logged.nome'));
    }
}
