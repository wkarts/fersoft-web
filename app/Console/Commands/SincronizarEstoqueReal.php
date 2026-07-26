<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use App\Models\Pesagem; // Ajuste para o namespace correto do seu model

class SincronizarEstoqueReal extends Command
{
    // O nome que você vai digitar no terminal
    protected $signature = 'estoque:sincronizar-real';

    protected $description = 'Preenche a tabela de estoque_fisico_movimentos com as pesagens finalizadas.';

    public function handle()
    {
        $this->info('Iniciando a sincronização das pesagens...');

        // Supondo que você tem um campo de status na tabela de pesagens
        // Ajuste 'status' e 'Concluído' para os nomes exatos que você usa
        $pesagens = Pesagem::where('status', 'Concluído')->get();

        if ($pesagens->isEmpty()) {
            $this->warn('Nenhuma pesagem finalizada encontrada.');
            return;
        }

        $inseridos = 0;

        // Inicia uma transação (se der erro no meio, ele desfaz tudo)
        DB::beginTransaction();

        try {
            foreach ($pesagens as $pesagem) {
                // Verifica se já não foi inserido antes (evita duplicidade se você rodar o comando 2 vezes)
                $existe = DB::table('estoque_fisico_movimentos')->where('pesagem_id', $pesagem->id)->exists();

                if (!$existe) {
                    DB::table('estoque_fisico_movimentos')->insert([
                        'produto_id' => $pesagem->produto_id, // Certifique-se que o campo é esse
                        'pesagem_id' => $pesagem->id,
                        // Aqui você define se a pesagem foi de compra (Entrada) ou venda (Saída)
                        // Ajuste a lógica abaixo conforme o seu sistema identifica o tipo de pesagem
                        'tipo' => ($pesagem->tipo == 'Compra') ? 'entrada' : 'saida', 
                        'quantidade' => $pesagem->peso_liquido, // O peso final depois dos descontos
                        'valor_unitario' => $pesagem->preco_kg,
                        'valor_total' => $pesagem->valor_total,
                        // Pega a data da pesagem. Se for DateTime, pegue só a parte da Data (Y-m-d)
                        'data_movimento' => date('Y-m-d', strtotime($pesagem->data_pesagem)), 
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]);
                    $inseridos++;
                }
            }
            DB::commit();
            $this->info("Sucesso! {$inseridos} movimentações de estoque real foram geradas.");

        } catch (\Exception $e) {
            DB::rollBack();
            $this->error('Erro durante a sincronização: ' . $e->getMessage());
        }
    }
}