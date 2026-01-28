<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\Usuario;
use App\Models\Empresa;
use App\Helpers\Menu;

class UsuarioSeed extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        Empresa::create([
            'nome' => env('EMPRESA_NOME', 'EMPRESA PADRAO'),
            'rua' => env('EMPRESA_RUA', 'ENDERECO PADRAO'),
            'numero' => env('EMPRESA_NUMERO', 'SN'),
            'bairro' => env('EMPRESA_BAIRRO', 'PADRAO'),
            'cidade' => env('EMPRESA_CIDADE', 'PADRAO'),
            'status' => 1,
            'email' => env('EMPRESA_EMAIL', 'PADRAO@PADRAO.com'),
            'telefone' => env('EMPRESA_TELEFONE', '00000000000'),
            'cnpj' => env('EMPRESA_CNPJ', ''),
            'permissao' => env('EMPRESA_PERMISSAO', ''),
        ]);

        $todasPermissoes = $this->validaPermissao();

        Usuario::create([
            'nome' => env('USUARIO_NOME', 'admin'),
            'login' => env('USUARIO_LOGIN', 'admin'),
            'senha' => env('USUARIO_SENHA_HASH', '202cb962ac59075b964b07152d234b70'), // MD5
            'adm' => 1,
            'ativo' => 1,
            'permissao' => json_encode($todasPermissoes),
            'empresa_id' => 1,
            'img' => '',
            'tema' => 1,
            'email' => env('USUARIO_EMAIL', ''),
            'permite_desconto' => 1,
            'somente_fiscal' => 0,
            'locais' => ''
        ]);

        $this->clearFolders();
    }

    private function clearFolders() {
        \Artisan::call('cache:clear');
        \Artisan::call('view:clear');
        \Artisan::call('route:clear');
        exec('echo "" > ' . storage_path('logs/laravel.log'));

        $folders = [
            'certificados',
            'barcode',
            // 'imgs_planos',
            'imgs_clientes',
            'imagens_loja_delivery',
            'imagens_categorias',
            'logos',
            'xml_nfe',
            'xml_nfce',
            'xml_cte',
            'xml_mdfe',
            'orcamento',
        ];

        foreach ($folders as $f) {
            $files = glob(public_path($f . '/*'));
            foreach ($files as $file) {
                if (is_file($file)) {
                    unlink($file);
                }
            }
        }
    }

    private function validaPermissao() {
        $menu = new Menu();
        $temp = [];
        $menu = $menu->getMenu();
        foreach ($menu as $m) {
            foreach ($m['subs'] as $s) {
                array_push($temp, $s['rota']);
            }
        }
        return $temp;
    }
}
