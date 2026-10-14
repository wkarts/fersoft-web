<?php

namespace Tests\Feature;

use App\Helpers\EncryptionHelper;
use App\Models\MtrConfig;
use App\Services\SinirIemaService;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class MtrCredencialCriptografiaTest extends TestCase
{
    public function test_credencial_nova_e_criptografada_e_lida_descriptografada(): void
    {
        $model = new MtrConfig();
        $model->senha = 'senha-portal-mtr-123';

        $ciphertext = $model->getAttributes()['senha'];
        $this->assertNotSame('senha-portal-mtr-123', $ciphertext);
        $this->assertSame('senha-portal-mtr-123', Crypt::decryptString($ciphertext));

        $reloaded = new MtrConfig();
        $reloaded->setRawAttributes(['senha' => $ciphertext], true);

        $this->assertSame('senha-portal-mtr-123', $reloaded->senha);
        $this->assertArrayNotHasKey('senha', $reloaded->toArray());
    }

    public function test_token_jwt_e_credencial_legitima_apos_descriptografar(): void
    {
        // eyJhbGci... é o cabeçalho de um JWT, não o ciphertext do Laravel.
        $jwt = 'eyJhbGciOiJIUzUxMiJ9.' . 'eyJzdWIiOiJ0ZXN0ZSJ9.' . str_repeat('a', 64);
        $model = new MtrConfig();
        $model->senha = $jwt;

        $stored = (string) $model->getAttributes()['senha'];
        $this->assertNotSame($jwt, $stored);

        $reloaded = new MtrConfig();
        $reloaded->setRawAttributes(['senha' => $stored], true);
        $this->assertSame($jwt, $reloaded->senha);
    }

    public function test_credencial_antiga_em_texto_puro_continua_legivel(): void
    {
        $model = new MtrConfig();
        $model->setRawAttributes(['senha' => 'senha-legada'], true);

        $this->assertSame('senha-legada', $model->senha);
    }

    public function test_credencial_com_criptografia_legada_continua_legivel(): void
    {
        EncryptionHelper::initialize();
        $ciphertext = EncryptionHelper::encrypt('senha-legada-criptografada');

        $model = new MtrConfig();
        $model->setRawAttributes(['senha' => $ciphertext], true);

        $this->assertSame('senha-legada-criptografada', $model->senha);
    }

    public function test_dupla_criptografia_laravel_pode_ser_recuperada(): void
    {
        $ciphertext = Crypt::encryptString(Crypt::encryptString('token-em-duas-camadas'));

        $model = new MtrConfig();
        $model->setRawAttributes(['senha' => $ciphertext], true);

        $this->assertSame('token-em-duas-camadas', $model->senha);
    }

    public function test_criptografia_truncada_nao_pode_ser_aceita_como_token(): void
    {
        $ciphertext = Crypt::encryptString(str_repeat('x', 320));
        $this->assertGreaterThan(500, strlen($ciphertext));

        $model = new MtrConfig();
        $model->setRawAttributes(['senha' => substr($ciphertext, 0, 500)], true);

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Não foi possível descriptografar');
        $model->senha;
    }

    public function test_service_nao_envia_ciphertext_para_sinir(): void
    {
        Http::fake();

        try {
            (new SinirIemaService())->getToken('12345678000190', Crypt::encryptString('segredo'));
            $this->fail('O service aceitou uma credencial ainda criptografada.');
        } catch (\Exception $e) {
            $this->assertStringContainsString('ainda criptografada', $e->getMessage());
        }

        Http::assertNothingSent();
    }

    public function test_auditoria_da_credencial_nao_grava_segredos(): void
    {
        $model = new class extends MtrConfig {
            public function auditNow(): array
            {
                return $this->auditSnapshot();
            }

            public function auditBefore(): array
            {
                return $this->auditSnapshotOriginal();
            }

            public function auditChanges(): array
            {
                return $this->auditChangedSnapshot();
            }
        };

        $encryptedOld = Crypt::encryptString('senha-antiga');
        $model->setRawAttributes(['id' => 7, 'empresa_id' => 1, 'senha' => $encryptedOld], true);
        $model->senha = 'senha-nova';

        [$before, $after] = $model->auditChanges();

        $this->assertSame('[CREDENCIAL_PROTEGIDA]', $before['senha']);
        $this->assertSame('[CREDENCIAL_PROTEGIDA]', $after['senha']);
        $this->assertSame('[CREDENCIAL_PROTEGIDA]', $model->auditNow()['senha']);
        $this->assertSame('[CREDENCIAL_PROTEGIDA]', $model->auditBefore()['senha']);
        $this->assertSame(7, $after['id']);
        $this->assertStringNotContainsString('senha-antiga', json_encode($before));
        $this->assertStringNotContainsString('senha-nova', json_encode($after));
    }

    public function test_rota_e_interface_nao_injetam_token_no_html_inicial(): void
    {
        $root = dirname(__DIR__, 2);
        $route = file_get_contents($root . '/routes/web.php');
        $view = file_get_contents($root . '/resources/views/mtr/unidades/edit.blade.php');
        $migration = file_get_contents(
            $root . '/database/migrations/2026_10_13_231000_expand_mtr_configs_senha_column.php'
        );

        $this->assertStringContainsString("Route::post('/revelar-credencial/{id}'", $route);
        $this->assertStringContainsString("->middleware('throttle:5,1')", $route);
        // A edição deve manter somente o campo e o olho originais.
        $this->assertSame(1, substr_count($view, 'name="senha" id="senha"'));
        $this->assertSame(1, substr_count($view, 'id="btnToggleSenha"'));
        $this->assertStringNotContainsString('id="btnRevelarSenha"', $view);
        $this->assertStringNotContainsString('id="senhaSalva"', $view);
        $this->assertStringNotContainsString('id="btnOcultarSenha"', $view);
        $this->assertStringNotContainsString('value="{{ $unidade->senha }}"', $view);

        // A consulta usa o mesmo campo e só ocorre na primeira visualização;
        // visualizar o valor anterior não deve gerar nova criptografia.
        $this->assertStringContainsString("if (campoSenha.val() !== '')", $view);
        $this->assertStringContainsString('campoSenha.val(credencialCarregada)', $view);
        $this->assertStringContainsString("alterarVisualizacaoSenha(false)", $view);
        $this->assertStringContainsString("campoSenha.prop('disabled', true)", $view);
        $controller = file_get_contents($root . '/app/Http/Controllers/MtrUnidadeController.php');
        $this->assertStringContainsString('hash_equals((string) $unidade->senha, (string) $dados[\'senha\'])', $controller);
        $this->assertStringContainsString("->text('senha')->nullable()", $migration);
        $this->assertStringContainsString('public function down(): void', $migration);
    }
}
