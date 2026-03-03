<?php

namespace Database\Seeders;

use App\Models\PerfilAcesso;
use Illuminate\Database\Seeder;

class PontoPermissoesSeeder extends Seeder
{
    public function run(): void
    {
        $rotasPonto = [
            '/ponto',
            '/ponto/relogios',
            '/ponto/importacao-afd',
            '/ponto/marcacoes',
            '/ponto/jornadas',
            '/ponto/ajustes',
            '/ponto/fechamentos',
            '/ponto/relatorios',
        ];

        PerfilAcesso::query()->chunk(100, function ($perfis) use ($rotasPonto) {
            foreach ($perfis as $perfil) {
                $permissoes = json_decode($perfil->permissao, true);
                if (!is_array($permissoes)) {
                    $permissoes = [];
                }

                $merge = array_values(array_unique(array_merge($permissoes, $rotasPonto)));
                if ($merge !== $permissoes) {
                    $perfil->permissao = json_encode($merge);
                    $perfil->save();
                }
            }
        });
    }
}
