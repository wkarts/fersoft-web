<?php

namespace App\Services;

use Illuminate\Support\Facades\DB;
use Exception;

class MtrImportService
{
    /**
     * Prepara a estrutura do MTR a partir de uma NF-e de Venda
     */
    public function extrairDeVendaNfe(int $vendaId, string $orgao)
    {
        $venda = DB::table('vendas')
            ->join('empresas', 'vendas.empresa_id', '=', 'empresas.id')
            ->join('clientes', 'vendas.cliente_id', '=', 'clientes.id')
            ->leftJoin('transportadoras', 'vendas.transportadora_id', '=', 'transportadoras.id')
            ->where('vendas.id', $vendaId)
            ->select(
                'vendas.*',
                'empresas.cnpj as gerador_cnpj', 'empresas.nome as gerador_nome',
                'clientes.cpf_cnpj as destinador_cnpj', 'clientes.razao_social as destinador_nome',
                'transportadoras.cnpj_cpf as transp_cnpj', 'transportadoras.razao_social as transp_nome'
            )->first();

        if (!$venda) {
            throw new Exception("Venda ID #{$vendaId} não encontrada.");
        }

        // Busca os itens da nota
        $itensVenda = DB::table('item_vendas')
            ->where('venda_id', $vendaId)
            ->get();

        $itensMtr = [];
        foreach ($itensVenda as $item) {
            $depara = DB::table('mtr_depara_residuos')
                ->where('empresa_id', $venda->empresa_id)
                ->where('orgao', $orgao)
                ->where('produto_id', $item->produto_id)
                ->first();

            if ($depara) {
                $itensMtr[] = [
                    'produto_id'         => $item->produto_id,
                    'cod_ibama'          => $depara->cod_ibama,
                    'descricao_residuo'  => $depara->descricao_residuo,
                    'quantidade'         => $item->quantidade * $depara->fator_conversao,
                    'unidade_medida'     => $depara->unidade_medida,
                    'classe_residuo'     => $depara->classe_residuo,
                    'acondicionamento_id'=> $depara->acondicionamento_id,
                    'tratamento_id'      => $depara->tratamento_id,
                ];
            }
        }

        return [
            'tipo_origem'         => 'nfe',
            'venda_id'            => $venda->id,
            'chave_nfe'           => $venda->chave,
            'gerador_cnpj'        => $venda->gerador_cnpj,
            'gerador_nome'        => $venda->gerador_nome,
            'destinador_cnpj'     => $venda->destinador_cnpj,
            'destinador_nome'     => $venda->destinador_nome,
            'transportador_cnpj'  => $venda->transp_cnpj ?? $venda->gerador_cnpj,
            'transportador_nome'  => $venda->transp_nome ?? $venda->gerador_nome,
            'observacao'          => "MTR gerado referente à NF-e chave: {$venda->chave}",
            'itens'               => $itensMtr
        ];
    }

    /**
     * Prepara a estrutura do MTR a partir de um Ticket de Pesagem
     */
    public function extrairDeTicketPesagem(int $ticketId, string $orgao)
    {
        $ticket = DB::table('tickets_pesagem')
            ->join('pesagens', 'tickets_pesagem.pesagem_id', '=', 'pesagens.id')
            ->join('empresas', 'tickets_pesagem.empresa_id', '=', 'empresas.id')
            ->leftJoin('clientes', 'pesagens.cliente_id', '=', 'clientes.id')
            ->leftJoin('fornecedors', 'pesagens.fornecedor_id', '=', 'fornecedors.id')
            ->where('tickets_pesagem.id', $ticketId)
            ->select(
                'tickets_pesagem.*',
                'pesagens.placa_veiculo', 'pesagens.motorista_nome',
                'pesagens.peso_final', 'pesagens.cliente_id', 'pesagens.fornecedor_id',
                'empresas.cnpj as gerador_cnpj', 'empresas.nome as gerador_nome',
                'clientes.cpf_cnpj as cliente_cnpj', 'clientes.razao_social as cliente_nome',
                'fornecedors.cpf_cnpj as forn_cnpj', 'fornecedors.razao_social as forn_nome'
            )->first();

        if (!$ticket) {
            throw new Exception("Ticket de Pesagem #{$ticketId} não encontrado.");
        }

        // Busca a regra De-Para do produto do ticket
        $depara = DB::table('mtr_depara_residuos')
            ->where('empresa_id', $ticket->empresa_id)
            ->where('orgao', $orgao)
            ->where('produto_id', $ticket->produto_id)
            ->first();

        $itensMtr = [];
        if ($depara) {
            $pesoEmKg = $ticket->peso_final > 0 ? $ticket->peso_final : $ticket->peso;
            $itensMtr[] = [
                'produto_id'         => $ticket->produto_id,
                'cod_ibama'          => $depara->cod_ibama,
                'descricao_residuo'  => $depara->descricao_residuo,
                'quantidade'         => $pesoEmKg * $depara->fator_conversao,
                'unidade_medida'     => $depara->unidade_medida,
                'classe_residuo'     => $depara->classe_residuo,
                'acondicionamento_id'=> $depara->acondicionamento_id,
                'tratamento_id'      => $depara->tratamento_id,
            ];
        }

        $destinadorCnpj = $ticket->cliente_cnpj ?? $ticket->forn_cnpj ?? '';
        $destinadorNome = $ticket->cliente_nome ?? $ticket->forn_nome ?? '';

        return [
            'tipo_origem'         => 'ticket_pesagem',
            'ticket_pesagem_id'   => $ticket->id,
            'gerador_cnpj'        => $ticket->gerador_cnpj,
            'gerador_nome'        => $ticket->gerador_nome,
            'destinador_cnpj'     => $destinadorCnpj,
            'destinador_nome'     => $destinadorNome,
            'transportador_cnpj'  => $ticket->gerador_cnpj,
            'transportador_nome'  => $ticket->gerador_nome,
            'veiculo_placa'       => $ticket->placa_veiculo,
            'motorista_nome'      => $ticket->motorista_nome,
            'observacao'          => "MTR gerado a partir do Ticket de Pesagem #{$ticket->id}",
            'itens'               => $itensMtr
        ];
    }
}