<?php

namespace App\Http\Controllers;

use App\Models\CategoriaConta;
use App\Models\Cidade;
use App\Models\Cliente;
use App\Models\ContratoEngenharia;
use App\Models\ContaReceber;
use App\Models\FaturaEngenharia;
use App\Models\FaturaEngFuncionario;
use App\Models\FaturaEngItem;
use App\Models\Produto;
use App\Models\Servico;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Schema;

class ContratoEngMedicaoController extends BaseController
{
    protected $model = FaturaEngenharia::class;
    protected $redirectPage = '/contratos/medicoes';
    protected $formTitle = 'Medição / Faturamento de Contrato';

    protected function rules(): array
    {
        return [];
    }

    protected function messages(): array
    {
        return [];
    }

    public function index(Request $request, $contratoId = null)
    {
        $empresaId = $this->empresaId();

        $query = FaturaEngenharia::where('empresa_id', $empresaId);

        if ($contratoId) {
            $query->where('contrato_eng_id', $contratoId);
        }
        if ($request->filled('cliente_id')) {
            $query->where('cliente_id', $request->cliente_id);
        }
        // A view histórica envia contrato_id; o schema/model consolidado usa
        // contrato_eng_id. O controller faz a compatibilidade sem alterar a tela.
        $contratoFiltroId = $request->input('contrato_eng_id')
            ?: $request->input('contrato_id');

        if ($contratoFiltroId) {
            $query->where('contrato_eng_id', $contratoFiltroId);
        }
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }
        if ($request->filled('data_inicial')) {
            $query->whereDate('data_faturamento', '>=', $request->data_inicial);
        }
        if ($request->filled('data_final')) {
            $query->whereDate('data_faturamento', '<=', $request->data_final);
        }

        return view('contratos.medicoes.index', [
            'medicoes' => $query->with(['contrato', 'cliente'])->orderByDesc('id')->paginate(15),
            'clientes' => Cliente::where('empresa_id', $empresaId)->orderBy('razao_social')->get(),
            'contratos' => ContratoEngenharia::where('empresa_id', $empresaId)->orderByDesc('id')->get(),
            'contrato_id' => $contratoId,
            'contratoSelecionado' => $contratoId
                ? ContratoEngenharia::where('empresa_id', $empresaId)->find($contratoId)
                : null,
            'title' => 'Locação e Serviços - Medições e Faturamento',
        ]);
    }

    public function create($contratoId = null)
    {
        $empresaId = $this->empresaId();

        $contratos = ContratoEngenharia::where('empresa_id', $empresaId)
            ->where('status', 'Ativo')
            ->with(['cliente', 'itens.servico', 'itens.produto'])
            ->get();

        foreach ($contratos as $contrato) {
            $contrato->funcionarios = DB::table('contrato_eng_funcionarios as cf')
                ->join('funcionarios as f', 'f.id', '=', 'cf.funcionario_id')
                ->leftJoin('funcoes as fn', 'fn.id', '=', 'f.funcao_id')
                ->where('cf.contrato_eng_id', $contrato->id)
                ->where('cf.status', 'Ativo')
                ->where('f.empresa_id', $empresaId)
                ->select('f.id as funcionario_id', 'f.nome', DB::raw("COALESCE(fn.nome, '') as funcao"))
                ->get();
        }

        return view('contratos.medicoes.create', $this->formData($empresaId) + [
                'contratos' => $contratos,
                'contratoSelecionado' => $contratoId
                    ? ContratoEngenharia::where('empresa_id', $empresaId)->find($contratoId)
                    : null,
                'title' => 'Novo Lançamento - Locação e Serviços',
            ]);
    }

    public function store(Request $request, $contratoId = null)
    {
        DB::beginTransaction();

        try {
            $empresaId = $this->empresaId();
            $usuarioId = $this->usuarioId();
            $contratoEngId = $request->input('contrato_eng_id') ?: $contratoId;

            $contrato = $contratoEngId
                ? ContratoEngenharia::where('empresa_id', $empresaId)->findOrFail($contratoEngId)
                : null;

            $clienteId = $request->input('cliente_id') ?: optional($contrato)->cliente_id;
            if (!$clienteId) {
                throw new \RuntimeException('Informe o cliente da medição.');
            }

            $valorTotal = $this->money($request->input('valor_total', 0));
            $valorRetencao = $this->money($request->input('valor_retencao', 0));
            $valorAbatimento = $this->money($request->input('valor_abatimento_compra', 0)); // 🚀 Adicionado

            $fatura = FaturaEngenharia::create([
                'empresa_id' => $empresaId,
                'filial_id' => $request->filled('filial_id') ? $request->filial_id : (session('user_logged')['filial'] ?? null),
                'usuario_id' => $usuarioId,
                'contrato_eng_id' => $contrato?->id,
                'cliente_id' => $clienteId,
                'vendedor_id' => $request->input('vendedor_id'),
                'condicao_pagamento_id' => $request->input('condicao_pagamento_id'),
                'categoria_conta_id' => $request->input('categoria_conta_id'),
                'servico_id' => $request->input('servico_id'),
                'municipio_prestacao_id' => $this->municipioPrestacaoId($request),
                'codigo_obra' => $request->input('codigo_obra'),
                'valor_total' => $valorTotal, // O valor_total que vem do formulário já é o líquido (total - abatimento)
                'valor_retencao' => $valorRetencao,
                'valor_abatimento_compra' => $valorAbatimento, // 🚀 Salva o abatimento
                'valor_liquido' => $valorTotal - $valorRetencao, // Aqui o líquido retira apenas a retenção técnica
                'data_faturamento' => $request->input('nf_data_emissao') ?: date('Y-m-d'),
                'observacao' => $request->input('observacao'),
                'status' => 'Pendente',
            ]);

            $this->replaceItems($fatura->id, (array) ($request->input('itens') ?: $request->input('servicos') ?: []));
            $this->replaceEmployees($fatura->id, (array) $request->input('funcionarios', []));
            $this->createReceivables($fatura, (array) $request->input('parcelas', []), $request);

            if ($contrato) {
                $contrato->increment('valor_faturado', $valorTotal);
            }

            DB::commit();

            return redirect()->route('contratos.medicoes.index')
                ->with('mensagem_sucesso', 'Lançamento e financeiro atualizados com sucesso!')
                ->with('imprimir_id', $fatura->id);
        } catch (\Throwable $e) {
            DB::rollBack();
            Log::error('Erro ao gerar medição de contrato.', [
                'empresa_id' => $this->empresaId(),
                'error' => $e->getMessage(),
            ]);

            return redirect()->back()->withInput()->with('mensagem_erro', 'Erro ao salvar: ' . $e->getMessage());
        }
    }

    public function edit($id)
    {
        $empresaId = $this->empresaId();

        $fatura = FaturaEngenharia::where('empresa_id', $empresaId)
            ->with(['itens.servico', 'itens.produto', 'funcionarios.funcionario'])
            ->findOrFail($id);

        if ($fatura->status === 'Finalizado') {
            return redirect('/contratos/medicoes')
                ->with('mensagem_erro', 'Este lançamento está finalizado e não pode ser editado.');
        }

        return view('contratos.medicoes.edit', $this->formData($empresaId) + [
                'fatura' => $fatura,
                'contratos' => ContratoEngenharia::where('empresa_id', $empresaId)->orderByDesc('id')->get(),
                'title' => 'Editar Medição / Faturamento #' . $fatura->id,
            ]);
    }

    public function update(Request $request, $id)
    {
        DB::beginTransaction();

        try {
            $empresaId = $this->empresaId();
            $fatura = FaturaEngenharia::where('empresa_id', $empresaId)->findOrFail($id);
            $oldTotal = (float) $fatura->valor_total;
            $oldContratoId = $fatura->contrato_eng_id;

            $novoContratoId = $request->input('contrato_eng_id') ?: null;
            if ($novoContratoId) {
                ContratoEngenharia::where('empresa_id', $empresaId)->findOrFail($novoContratoId);
            }

            $valorTotal = $this->money($request->input('valor_total', 0));
            $valorRetencao = $request->filled('valor_retencao')
                ? $this->money($request->input('valor_retencao'))
                : (float) $fatura->valor_retencao;

            $fatura->update([
                'contrato_eng_id' => $novoContratoId,
                'cliente_id' => $request->input('cliente_id'),
                'categoria_conta_id' => $request->input('categoria_conta_id'),
                'servico_id' => $request->input('servico_id'),
                'municipio_prestacao_id' => $this->municipioPrestacaoId($request),
                'codigo_obra' => $request->input('codigo_obra'),
                'valor_total' => $valorTotal,
                'valor_retencao' => $valorRetencao,
                'valor_liquido' => $valorTotal - $valorRetencao,
                'data_faturamento' => $request->input('nf_data_emissao'),
                'observacao' => $request->input('observacao'),
                'status' => $request->input('status') ?: $fatura->status,
            ]);

            $this->replaceItems($fatura->id, (array) ($request->input('itens') ?: $request->input('servicos') ?: []));
            $this->replaceEmployees($fatura->id, (array) $request->input('funcionarios', []), false);
            $this->syncOpenReceivables($fatura, $request);

            if ($oldContratoId && (int) $oldContratoId === (int) $novoContratoId) {
                ContratoEngenharia::where('empresa_id', $empresaId)
                    ->where('id', $oldContratoId)
                    ->update([
                        'valor_faturado' => DB::raw('GREATEST(0, valor_faturado + ' . ($valorTotal - $oldTotal) . ')'),
                        'updated_at' => now(),
                    ]);
            } else {
                if ($oldContratoId) {
                    ContratoEngenharia::where('empresa_id', $empresaId)
                        ->where('id', $oldContratoId)
                        ->update([
                            'valor_faturado' => DB::raw('GREATEST(0, valor_faturado - ' . $oldTotal . ')'),
                            'updated_at' => now(),
                        ]);
                }
                if ($novoContratoId) {
                    ContratoEngenharia::where('empresa_id', $empresaId)
                        ->where('id', $novoContratoId)
                        ->increment('valor_faturado', $valorTotal);
                }
            }

            DB::commit();

            return redirect('/contratos/medicoes')
                ->with('mensagem_sucesso', 'Lançamento e financeiro atualizados com sucesso!');
        } catch (\Throwable $e) {
            DB::rollBack();
            return redirect()->back()->withInput()->with('mensagem_erro', 'Erro ao atualizar: ' . $e->getMessage());
        }
    }

    public function imprimir($id)
    {
        return view('contratos.medicoes.print', $this->printData($id));
    }

    public function gerarPdf($id)
    {
        $data = $this->printData($id);
        $html = view('contratos.medicoes.print', $data)->render();
        $pdf = $this->renderPdf($html);

        return response($pdf, 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'inline; filename="Medicao_Faturamento_' . $id . '.pdf"',
        ]);
    }

    public function destroy($id)
    {
        DB::beginTransaction();

        try {
            $empresaId = $this->empresaId();
            $fatura = FaturaEngenharia::where('empresa_id', $empresaId)->findOrFail($id);

            $hasPaid = DB::table('conta_recebers')
                ->where('empresa_id', $empresaId)
                ->where('referencia', 'like', '%(Medição #' . $id . ')%')
                ->where('status', 1)
                ->exists();

            if ($hasPaid) {
                throw new \RuntimeException('Existem parcelas recebidas. A medição não pode ser excluída.');
            }

            DB::table('conta_recebers')
                ->where('empresa_id', $empresaId)
                ->where('referencia', 'like', '%(Medição #' . $id . ')%')
                ->delete();

            FaturaEngItem::where('fatura_eng_id', $id)->delete();
            FaturaEngFuncionario::where('fatura_eng_id', $id)->delete();

            if ($fatura->contrato_eng_id) {
                ContratoEngenharia::where('empresa_id', $empresaId)
                    ->where('id', $fatura->contrato_eng_id)
                    ->update([
                        'valor_faturado' => DB::raw('GREATEST(0, valor_faturado - ' . (float) $fatura->valor_total . ')'),
                        'updated_at' => now(),
                    ]);
            }

            $fatura->delete();
            DB::commit();

            return redirect()->back()->with('mensagem_sucesso', 'Lançamento e títulos financeiros excluídos com sucesso!');
        } catch (\Throwable $e) {
            DB::rollBack();
            return redirect()->back()->with('mensagem_erro', 'Erro ao excluir: ' . $e->getMessage());
        }
    }

    public function mudarStatus(Request $request, $id)
    {
        $fatura = FaturaEngenharia::where('empresa_id', $this->empresaId())->findOrFail($id);
        $fatura->update(['status' => $request->input('status')]);

        return redirect()->back()->with('mensagem_sucesso', 'Status atualizado com sucesso!');
    }

    public function cancelar($id)
    {
        try {
            $empresaId = $this->empresaId();
            $fatura = FaturaEngenharia::where('empresa_id', $empresaId)->findOrFail($id);

            $hasPaid = DB::table('conta_recebers')
                ->where('empresa_id', $empresaId)
                ->where('referencia', 'like', '%(Medição #' . $id . ')%')
                ->where('status', 1)
                ->exists();

            if ($hasPaid) {
                throw new \RuntimeException(
                    'Existem parcelas recebidas. O faturamento não pode ser cancelado.'
                );
            }

            $fatura->status = 'Cancelado';
            $fatura->save();

            return redirect()->back()
                ->with('mensagem_sucesso', 'Medição / faturamento cancelado com sucesso!');
        } catch (\Throwable $e) {
            Log::error('Falha ao cancelar medição de contrato.', [
                'medicao_id' => $id,
                'empresa_id' => $this->empresaId(),
                'error' => $e->getMessage(),
            ]);

            return redirect()->back()->with('mensagem_erro', $e->getMessage());
        }
    }

    public function enviarWhatsAppMedicao(Request $request, $id)
    {
        try {
            $data = $this->printData($id);
            $fatura = $data['fatura'];

            $numeroOriginal = $fatura->cliente->celular
                ?? $fatura->cliente->whatsapp
                ?? $fatura->cliente->telefone
                ?? '';

            $numero = preg_replace('/\D+/', '', $numeroOriginal);
            if (strlen($numero) < 10) {
                throw new \RuntimeException('O cliente não possui número de WhatsApp válido.');
            }
            if (!str_starts_with($numero, '55')) {
                $numero = '55' . $numero;
            }

            $html = view('contratos.medicoes.print', $data)->render();
            $pdf = $this->renderPdf($html);

            $dir = public_path('pdf/faturas');
            if (!is_dir($dir)) {
                mkdir($dir, 0755, true);
            }

            $path = $dir . '/Medicao_Faturamento_' . $id . '.pdf';
            if (safe_file_put_contents($path, $pdf) === false) {
                throw new \RuntimeException('Não foi possível gravar o PDF temporário da medição.');
            }

            $cliente = $fatura->cliente->razao_social ?? 'Cliente';
            $contrato = $fatura->contrato?->numero_contrato ?? $fatura->contrato_eng_id ?? 'Avulso';
            $mensagem = "📄 *MEDIÇÃO / FATURAMENTO #{$fatura->id}*\n\n"
                . "Olá, *{$cliente}*!\n"
                . "Contrato: {$contrato}\n"
                . "Valor: R$ " . number_format((float) $fatura->valor_total, 2, ',', '.') . "\n"
                . "Data: " . optional($fatura->data_faturamento)->format('d/m/Y') . "\n\n"
                . "Segue o comprovante em PDF.";

            $result = app(\App\Utils\WhatsAppUtil::class)
                ->sendMessage($numero, $mensagem, $this->empresaId(), $path);

            $decoded = json_decode($result, true);
            if (is_array($decoded) && ($decoded['success'] ?? true) === false) {
                throw new \RuntimeException($decoded['message'] ?? 'Falha ao enviar WhatsApp.');
            }

            return redirect()->back()->with('mensagem_sucesso', 'Fatura enviada por WhatsApp com sucesso!');
        } catch (\Throwable $e) {
            Log::error('Falha no WhatsApp da medição.', ['medicao_id' => $id, 'error' => $e->getMessage()]);
            return redirect()->back()->with('mensagem_erro', $e->getMessage());
        }
    }

    public function enviarEmail(Request $request, $id)
    {
        try {
            $data = $this->printData($id);
            $fatura = $data['fatura'];

            if (!$fatura->cliente || empty($fatura->cliente->email)) {
                throw new \RuntimeException('O cliente não possui e-mail cadastrado.');
            }

            $config = $this->emailConfig($this->empresaId());
            if ($config) {
                config([
                    'mail.mailers.smtp.host' => $config->host,
                    'mail.mailers.smtp.port' => $config->porta ?? $config->port ?? 587,
                    'mail.mailers.smtp.encryption' => strtolower($config->criptografia ?? $config->encryption ?? 'tls'),
                    'mail.mailers.smtp.username' => $config->email ?? $config->usuario,
                    'mail.mailers.smtp.password' => $config->senha ?? $config->password,
                    'mail.from.address' => $config->email ?? config('mail.from.address'),
                    'mail.from.name' => $config->nome ?? config('app.name'),
                ]);
            }

            $pdf = $this->renderPdf(view('contratos.medicoes.print', $data)->render());
            $clienteNome = $fatura->cliente->razao_social ?? 'Cliente';
            $empresaNome = $data['empresa']->nome ?? config('app.name');

            Mail::send([], [], function ($message) use ($fatura, $pdf, $clienteNome, $empresaNome) {
                $body = '<p>Olá, <strong>' . e($clienteNome) . '</strong>!</p>'
                    . '<p>Segue o comprovante da Medição / Faturamento #' . $fatura->id . '.</p>'
                    . '<p>Valor total: <strong>R$ ' . number_format((float) $fatura->valor_total, 2, ',', '.') . '</strong></p>'
                    . '<p>Atenciosamente,<br><strong>' . e($empresaNome) . '</strong></p>';

                $message->to($fatura->cliente->email, $clienteNome)
                    ->subject('Medição / Faturamento #' . $fatura->id . ' - ' . $empresaNome)
                    ->html($body)
                    ->attachData($pdf, 'Medicao_Faturamento_' . $fatura->id . '.pdf', ['mime' => 'application/pdf']);
            });

            return redirect()->back()->with('mensagem_sucesso', 'E-mail enviado com sucesso!');
        } catch (\Throwable $e) {
            Log::error('Falha no e-mail da medição.', ['medicao_id' => $id, 'error' => $e->getMessage()]);
            return redirect()->back()->with('mensagem_erro', $e->getMessage());
        }
    }

    private function formData(int $empresaId): array
    {
        return [
            'clientes' => Cliente::where('empresa_id', $empresaId)->orderBy('razao_social')->get(),
            'servicos' => Servico::where('empresa_id', $empresaId)->orderBy('nome')->get(),
            'produtos' => Produto::where('empresa_id', $empresaId)->orderBy('nome')->get(),
            'categorias' => CategoriaConta::where('empresa_id', $empresaId)->where('tipo', 'receber')->orderBy('nome')->get(),
            'cidades' => Cidade::orderBy('nome')->get(),
            'funcionarios' => DB::table('funcionarios as f')
                ->leftJoin('funcoes as fn', 'fn.id', '=', 'f.funcao_id')
                ->where('f.empresa_id', $empresaId)
                ->select(
                    'f.id',
                    'f.nome',
                    DB::raw("COALESCE(fn.nome, '') as funcao_nome")
                )
                ->orderBy('f.nome')
                ->get(),
            'tiposPagamento' => ContaReceber::tiposPagamento(),
        ];
    }

    private function replaceItems(int $faturaId, array $items): void
    {
        FaturaEngItem::where('fatura_eng_id', $faturaId)->delete();

        foreach ($items as $item) {
            $tipo = $item['tipo_item'] ?? 'Servico';
            $qtd = $this->decimal($item['quantidade'] ?? 1);
            $valor = $this->money($item['valor_unitario'] ?? $item['valor'] ?? 0);
            $total = $this->money($item['valor_total'] ?? ($qtd * $valor));

            if (empty($item['servico_id']) && empty($item['produto_id'])) {
                continue;
            }

            FaturaEngItem::create([
                'fatura_eng_id'  => $faturaId,
                'tipo_item'      => $tipo,
                'servico_id'     => !empty($item['servico_id']) ? $item['servico_id'] : null,
                'produto_id'     => !empty($item['produto_id']) ? $item['produto_id'] : null,
                'descricao'      => !empty($item['descricao']) ? $item['descricao'] : null, // 🚀 SALVA A DATA AQUI
                'quantidade'     => $qtd,
                'valor_unitario' => $valor,
                'valor_total'    => $total,
            ]);
        }
    }

    private function replaceEmployees(int $faturaId, array $employees, bool $replaceWhenEmpty = true): void
    {
        if (!$replaceWhenEmpty && $employees === []) {
            return;
        }

        FaturaEngFuncionario::where('fatura_eng_id', $faturaId)->delete();

        foreach ($employees as $employee) {
            if (empty($employee['funcionario_id'])) {
                continue;
            }

            $diarias = $this->decimal($employee['diarias'] ?? 1);
            $valor = $this->money($employee['valor_diaria'] ?? 0);

            FaturaEngFuncionario::create([
                'fatura_eng_id' => $faturaId,
                'funcionario_id' => $employee['funcionario_id'],
                'funcao' => $employee['funcao'] ?? null,
                'diarias' => $diarias,
                'valor_diaria' => $valor,
                'valor_total' => $diarias * $valor,
            ]);
        }
    }

    private function createReceivables(FaturaEngenharia $fatura, array $parcelas, Request $request): void
    {
        if ($parcelas === []) {
            $parcelas = [[
                'valor' => $fatura->valor_total,
                'vencimento' => $fatura->data_faturamento?->format('Y-m-d') ?: date('Y-m-d'),
            ]];
        }

        $totalParcelas = count($parcelas);

        foreach ($parcelas as $key => $parcela) {
            $valor = $this->money($parcela['valor'] ?? 0);
            if ($valor <= 0) {
                continue;
            }

            $contrato = $fatura->contrato;
            $referencia = $contrato
                ? 'Contrato Nº ' . ($contrato->numero_contrato ?: $contrato->id)
                . ' (Medição #' . $fatura->id . ') - Parcela ' . ($key + 1) . '/' . $totalParcelas
                : 'Serviço Avulso (Faturamento #' . $fatura->id . ') - Parcela ' . ($key + 1) . '/' . $totalParcelas;

            DB::table('conta_recebers')->insert([
                'empresa_id' => $fatura->empresa_id,
                'filial_id' => $fatura->filial_id,
                'cliente_id' => $fatura->cliente_id,
                'usuario_id' => $fatura->usuario_id,
                'categoria_id' => $fatura->categoria_conta_id,
                'tipo_pagamento' => $request->input('tipo_pagamento'),
                'valor_integral' => $valor,
                'data_vencimento' => $parcela['vencimento'] ?? date('Y-m-d'),
                'nf_data_emissao' => $fatura->data_faturamento,
                'status' => 0,
                'referencia' => $referencia,
                'observacao' => $request->input('observacao'),
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
    }

    private function syncOpenReceivables(FaturaEngenharia $fatura, Request $request): void
    {
        $query = DB::table('conta_recebers')
            ->where('empresa_id', $fatura->empresa_id)
            ->where('referencia', 'like', '%(Medição #' . $fatura->id . ')%');

        if ((clone $query)->where('status', 1)->exists()) {
            $update = [
                'categoria_id' => $fatura->categoria_conta_id,
                'observacao' => $request->input('observacao'),
                'updated_at' => now(),
            ];

            if ($request->filled('tipo_pagamento')) {
                $update['tipo_pagamento'] = $request->input('tipo_pagamento');
            }

            $query->update($update);
            return;
        }

        $rows = $query->orderBy('id')->get();
        if ($rows->isEmpty()) {
            return;
        }

        $base = round((float) $fatura->valor_total / $rows->count(), 2);
        $remaining = (float) $fatura->valor_total;

        foreach ($rows as $index => $row) {
            $value = $index === $rows->count() - 1 ? $remaining : $base;
            $remaining -= $value;

            $update = [
                'valor_integral' => $value,
                'categoria_id' => $fatura->categoria_conta_id,
                'observacao' => $request->input('observacao'),
                'updated_at' => now(),
            ];

            if ($request->filled('tipo_pagamento')) {
                $update['tipo_pagamento'] = $request->input('tipo_pagamento');
            }

            DB::table('conta_recebers')
                ->where('id', $row->id)
                ->update($update);
        }
    }

    private function printData(int $id): array
    {
        $empresaId = $this->empresaId();
        $fatura = FaturaEngenharia::where('empresa_id', $empresaId)
            ->with([
                'contrato.itens.servico',
                'contrato.itens.produto',
                'cliente',
                'funcionarios.funcionario',
                'categoriaConta',
                'itens.servico',
                'itens.produto',
                'cidadePrestacao',
            ])
            ->findOrFail($id);

        return [
            'fatura' => $fatura,
            'empresa' => DB::table('empresas')->where('id', $empresaId)->first(),
            'configNota' => DB::table('config_notas')->where('empresa_id', $empresaId)->first(),
            'parcelas' => DB::table('conta_recebers')
                ->where('empresa_id', $empresaId)
                ->where('referencia', 'like', '%(Medição #' . $id . ')%')
                ->get(),
            'title' => 'Impressão de Medição / Faturamento #' . $fatura->id,
        ];
    }

    private function renderPdf(string $html): string
    {
        $options = new \Dompdf\Options();
        $options->set('isRemoteEnabled', true);
        $options->set('defaultFont', 'sans-serif');

        $dompdf = new \Dompdf\Dompdf($options);
        $dompdf->loadHtml($html);
        $dompdf->setPaper('A4', 'portrait');
        $dompdf->render();

        return $dompdf->output();
    }

    private function emailConfig(int $empresaId): ?object
    {
        foreach (['config_email', 'config_emails', 'email_configs'] as $table) {
            if (Schema::hasTable($table)) {
                $config = DB::table($table)->where('empresa_id', $empresaId)->first();
                if ($config) {
                    return $config;
                }
            }
        }

        return null;
    }

    private function empresaId(): int
    {
        return (int) (session('user_logged')['empresa'] ?? 0);
    }

    private function usuarioId(): ?int
    {
        $id = session('user_logged')['id'] ?? null;
        return $id ? (int) $id : null;
    }

    private function municipioPrestacaoId(Request $request): ?int
    {
        $id = $request->input('municipio_prestacao_id')
            ?: $request->input('cidade_prestacao_id');

        return $id ? (int) $id : null;
    }

    private function money($value): float
    {
        if (is_numeric($value)) {
            return (float) $value;
        }

        return (float) str_replace(',', '.', str_replace('.', '', (string) $value));
    }

    private function decimal($value): float
    {
        return $this->money($value);
    }

    // Operações de NFS-e adicionadas sem alterar os métodos históricos de
    // medições, financeiros, WhatsApp, e-mail, impressão e BaseController.

    public function emitirNfse(Request $request, $id)
    {
        $empresa_id = session('user_logged')['empresa'] ?? null;
        $fatura = FaturaEngenharia::with(['cliente', 'itens.servico', 'itens.produto'])
            ->where('empresa_id', $empresa_id)
            ->findOrFail($id);

        $config = \App\Models\ConfigNota::where('empresa_id', $empresa_id)->first();
        $certRes = \App\Models\Certificado::where('empresa_id', $empresa_id)->first();
        $tributacao = \App\Models\Tributacao::where('empresa_id', $empresa_id)->first();

        if (!$config || !$certRes) {
            return redirect()->back()->with('mensagem_erro', 'Configurações ou certificado digital não encontrados.');
        }

        try {
            $certContent = file_exists(storage_path('app/public/certificados/' . $certRes->arquivo))
                ? file_get_contents(storage_path('app/public/certificados/' . $certRes->arquivo))
                : $certRes->arquivo;
            $certificate = \NFePHP\Common\Certificate::readPfx($certContent, $certRes->senha);

            // Identificação do município emitente
            $codigoIbgeEmitente = null;
            if (!empty($config->cidade_id)) {
                $cidadeObj = DB::table('cidades')->where('id', $config->cidade_id)->first();
                if ($cidadeObj) {
                    $codigoIbgeEmitente = $cidadeObj->codigo ?? $cidadeObj->codigo_ibge ?? null;
                }
            }
            if (!$codigoIbgeEmitente) {
                $codigoIbgeEmitente = $config->codMun ?? $config->codigo_ibge_municipio ?? '2919207';
            }
            $codigoIbgeEmitente = str_pad(preg_replace('/\D/', '', $codigoIbgeEmitente), 7, '0', STR_PAD_LEFT);

            // Seleção de Provedor
            $configArray = $config->toArray();
            switch ((string) $codigoIbgeEmitente) {
                case '2910057': // Dias d'Ávila - BA
                    $provedor = 'diasdavila';
                    if (empty($configArray['usuario_saatri']) || empty($configArray['senha_saatri'])) {
                        throw new \RuntimeException('Configure as credenciais do provedor municipal (SAATRI) antes de transmitir a NFS-e.');
                    }
                    break;
                case '2927408': // Salvador - BA
                    $provedor = 'salvador';
                    break;
                default:
                    $provedor = 'nacional'; // Lauro de Freitas e Outros (Padrão Nacional)
                    break;
            }

            $emissor = \App\Services\Nfse\NfseServiceFactory::criar($provedor, $configArray, $certificate);

            // =========================================================================
            // 1. AGRUPAR ITENS POR SERVIÇO FISCAL (Geração de Múltiplas Notas)
            // =========================================================================
            $gruposServicos = [];
            foreach ($fatura->itens as $it) {

                // 🚀 BLOQUEIO DA SUCATA: Se não tem serviço fiscal, é abatimento!
                // A sucata não pode ser listada na discriminação de serviços da NFS-e.
                if ($it->tipo_item === 'Residuo' && empty($it->servico_id)) {
                    continue;
                }

                $sId = $it->servico_id ?? $fatura->servico_id;

                if (!$sId && $it->tipo_item != 'Locacao') {
                    throw new \RuntimeException('Existem itens de serviço na medição sem Serviço Fiscal vinculado. Vincule um serviço para emitir a nota.');
                }

                // Se for locação solta (sem serviço fixo), anexa ao primeiro serviço disponível para dedução
                if (!$sId && $it->tipo_item == 'Locacao') {
                    $sId = $fatura->servico_id ?? array_key_first($gruposServicos);
                }

                if (!isset($gruposServicos[$sId])) {
                    $gruposServicos[$sId] = [
                        'servico'       => $it->servico ?? \App\Models\Servico::find($sId),
                        'itens'         => [],
                        'valor_bruto'   => 0,
                        'valor_locacao' => 0,
                    ];
                }

                $gruposServicos[$sId]['itens'][] = $it;
                $gruposServicos[$sId]['valor_bruto'] += (float) $it->valor_total;

                if ($it->tipo_item === 'Locacao') {
                    $gruposServicos[$sId]['valor_locacao'] += (float) $it->valor_total;
                }
            }

            $cliente = $fatura->cliente;
            $docTomador = preg_replace('/\D/', '', $cliente->cpf_cnpj ?? '');
            $tipoDocTomador = strlen($docTomador) > 11 ? 'CNPJ' : 'CPF';
            $cMunTomador = preg_replace('/\D/', '', $cliente->cidade->codigo ?? $codigoIbgeEmitente);

            $abatimentoTotalFatura = (float) ($fatura->valor_abatimento_compra ?? 0.00);
            $valorFaturaTotal = (float) $fatura->valor_total;

            $notasGeradas = 0;
            $errosGerados = [];

            // =========================================================================
            // 2. ITERA SOBRE CADA SERVIÇO E EMITE UMA NOTA SEPARADA
            // =========================================================================
            foreach ($gruposServicos as $sId => $grupo) {
                $servicoMaster = $grupo['servico'];
                $valorServicoBruto = $grupo['valor_bruto'];
                $valorLocacaoDeducao = $grupo['valor_locacao'];

                // Rateio proporcional do abatimento (Sucata/Desconto Incondicional) para esta nota
                $valorAbatimentoSucata = 0;
                if ($valorFaturaTotal > 0 && $abatimentoTotalFatura > 0) {
                    $valorAbatimentoSucata = round($abatimentoTotalFatura * ($valorServicoBruto / $valorFaturaTotal), 2);
                }

                // Regime Tributário
                $regimeErp = (int) ($tributacao->regime ?? $config->opcao_simples_nacional ?? 0);
                $opSimpNac = ($regimeErp === 0) ? 2 : (($regimeErp === 2) ? 3 : 1);

                $cTribNac  = preg_replace('/\D/', '', $servicoMaster->codigo_tributacao_nacional ?? $servicoMaster->codigo_servico ?? '140501');
                $cNBS      = preg_replace('/\D/', '', $servicoMaster->codigo_nbs ?? '120029000');
                $aliquotaIss = (float) ($servicoMaster->aliquota_iss ?? 3.00);
                $issRetido   = (int) ($servicoMaster->iss_retido ?? 2);
                $tpRetISSQN  = ($issRetido === 1) ? 2 : 1;
                $aliquotaInss = (float) ($servicoMaster->aliquota_inss ?? 0);

                // Discriminação dos itens filtrados apenas para ESTA nota
                $discriminacaoItens = "MEDIÇÃO DE CONTRATO #" . ($fatura->contrato_eng_id ?? $fatura->id) . "\n";
                foreach ($grupo['itens'] as $it) {
                    $nomeItem = !empty($it->descricao) ? $it->descricao : ($it->servico->nome ?? $it->produto->nome ?? 'Item da Medição');
                    $discriminacaoItens .= "- {$nomeItem} | Qtd: " . number_format($it->quantidade, 2, ',', '.') . " | Vl. Unit: R$ " . number_format($it->valor_unitario, 2, ',', '.') . " | Subtotal: R$ " . number_format((float) $it->valor_total, 2, ',', '.') . "\n";
                }

                if ($valorAbatimentoSucata > 0) {
                    $discriminacaoItens .= "\n(-) DESCONTO COMERCIAL POR COMPRA DE SUCATA/MATERIAIS: R$ " . number_format($valorAbatimentoSucata, 2, ',', '.') . "\n";
                }
                if ($valorLocacaoDeducao > 0) {
                    $discriminacaoItens .= "\n(*) O valor referente à Locação de Equipamentos/Caçambas (R$ " . number_format($valorLocacaoDeducao, 2, ',', '.') . ") foi deduzido da base de cálculo do ISSQN conforme Súmula Vinculante 31 do STF.\n";
                }
                if (!empty($fatura->observacao)) {
                    $discriminacaoItens .= "\nObs: " . $fatura->observacao;
                }

                $baseCalculoReal = max(0, $valorServicoBruto - $valorAbatimentoSucata - $valorLocacaoDeducao);

                // Reforma Tributária e Cálculos
                $rtService = new \App\Services\ReformaTributariaService();
                $itemCalculo = [
                    'valor_total' => $baseCalculoReal,
                    'cst_ibs_cbs' => $servicoMaster->cst_ibscbs ?? $tributacao->cst_ibs_cbs ?? '000',
                    'class_trib_ibs_cbs' => $servicoMaster->codigo_class_trib ?? $tributacao->class_trib_ibs_cbs ?? '000001',
                    'aliq_cbs' => (float)($tributacao->aliq_cbs ?? 0.9000),
                    'aliq_ibs_uf' => (float)($tributacao->aliq_ibs_uf ?? 0.1000),
                    'aliq_ibs_mun' => (float)($tributacao->aliq_ibs_mun ?? 0.0000),
                ];
                $rtService->calcularItem($itemCalculo, $empresa_id);

                $baseCalculoIbsCbs = $itemCalculo['bc_ibs_cbs'] ?? $baseCalculoReal;
                $valIbsEstadual    = $itemCalculo['valor_ibs_uf'] ?? round($baseCalculoIbsCbs * 0.001, 2);
                $valCbs            = $itemCalculo['valor_cbs'] ?? round($baseCalculoIbsCbs * 0.009, 2);
                $vRetINSS          = $aliquotaInss > 0 ? round($baseCalculoReal * ($aliquotaInss / 100), 2) : 0.00;

                // Garante um número sequencial único para o RPS
                $numeroRps = (int) ($config->ultimo_numero_nfse ? $config->ultimo_numero_nfse + 1 : $fatura->id . rand(10,99));

                $dpsData = [
                    'infDPS' => [
                        'dhEmi'    => date('Y-m-d\TH:i:sP'),
                        'dCompet'  => date('Y-m-d', strtotime($fatura->data_faturamento ?? now())),
                        'tpEmit'   => 1,
                        'cLocEmi'  => $codigoIbgeEmitente,
                        'serie'    => '1',
                        'nDPS'     => $numeroRps,
                        'prest' => [
                            'CNPJ'    => preg_replace('/\D/', '', $config->cnpj),
                            'IM'      => preg_replace('/\D/', '', $config->inscricao_municipal ?? ''),
                            'regTrib' => ['opSimpNac' => $opSimpNac, 'regApTribSN' => 1, 'regEspTrib' => 0]
                        ],
                        'toma' => [
                            $tipoDocTomador => $docTomador,
                            'xNome' => $cliente->razao_social ?? 'CONSUMIDOR',
                            'ender' => [
                                'cMun'    => $cMunTomador,
                                'CEP'     => preg_replace('/\D/', '', $cliente->cep ?? '40000000'),
                                'xLgr'    => $cliente->rua ?? 'Logradouro',
                                'nro'     => $cliente->numero ?? 'S/N',
                                'xBairro' => $cliente->bairro ?? 'Centro'
                            ]
                        ],
                        'serv' => [
                            'locPrest' => ['cLocPrest' => $codigoIbgeEmitente],
                            'cServ' => [
                                'cTribNac'  => $cTribNac,
                                'xDescServ' => trim($discriminacaoItens),
                                'cNBS'      => $cNBS,
                            ]
                        ],
                        'valores' => [
                            'vServPrest' => ['vServ' => number_format($valorServicoBruto, 2, '.', '')],
                            'trib' => [
                                'tribMun' => [
                                    'tribISSQN'  => 1,
                                    'tpRetISSQN' => $tpRetISSQN,
                                    'pAliq'      => number_format($aliquotaIss, 4, '.', '')
                                ],
                                'vDescIncond' => number_format($valorAbatimentoSucata, 2, '.', ''),
                                'vDed'        => number_format($valorLocacaoDeducao, 2, '.', ''),
                                'tribFed'     => [
                                    'vRetCP'    => number_format($vRetINSS, 2, '.', ''),
                                    'vRetIRRF'  => '0.00',
                                    'vRetCSLL'  => '0.00',
                                    'piscofins' => ['CST' => '07', 'vPIS' => '0.00', 'vCOFINS' => '0.00']
                                ]
                            ]
                        ],
                        'ibscbs' => [
                            'CST'         => $itemCalculo['cst_ibs_cbs'] ?? '000',
                            'cClassTrib'  => $itemCalculo['class_trib_ibs_cbs'] ?? '000001',
                            'cIndOp'      => ($cMunTomador === $codigoIbgeEmitente) ? '050101' : '050201',
                            'baseCalculo' => number_format($baseCalculoIbsCbs, 2, '.', ''),
                            'percIbsMun'  => '0.00', 'valIbsMun' => '0.00',
                            'percIbsEst'  => '0.10', 'valIbsEst' => number_format($valIbsEstadual, 2, '.', ''),
                            'percCbs'     => '0.90', 'valCbs'    => number_format($valCbs, 2, '.', ''),
                        ]
                    ]
                ];

                // 3. ENVIA PARA A PREFEITURA E CAPTURA O RETORNO
                $resultado = $emissor->emitir($dpsData);
                $xmlRetorno = $resultado['xml_retorno'] ?? '';

                // Incrementa o RPS globalmente para a próxima nota do laço não colidir!
                $config->ultimo_numero_nfse = $numeroRps;
                $config->save();

                // Tratamento de Rejeição vs Sucesso
                $xmlDecodificado = html_entity_decode($xmlRetorno);
                if (stripos($xmlDecodificado, '<Sucesso>false</Sucesso>') !== false || stripos($xmlDecodificado, '<Erro>') !== false || stripos($xmlDecodificado, '<faultstring>') !== false) {
                    preg_match('/<Descricao>(.*?)<\/Descricao>/', $xmlDecodificado, $mDesc);
                    $errosGerados[] = "Serviço '{$servicoMaster->nome}': " . ($mDesc[1] ?? 'Rejeição no WebService.');
                    continue; // Pula pra próxima nota em caso de erro!
                }

                $numeroNfse = $resultado['numero_nfse'] ?? $numeroRps;
                $codVerificacao = $resultado['codigo_verificacao'] ?? $resultado['chave_nfse'] ?? $resultado['chave'] ?? ('DPS-' . $numeroRps);

                $linkPdf = "https://diasdavila.saatri.com.br/ConsultaNota/Visualizar?codigo=" . $codVerificacao; // Ajuste se usar outro portal

                // =========================================================================
                // 4. INSERE NA NOVA TABELA AUXILIAR "fatura_eng_notas" (Multiplas NFS-e)
                // =========================================================================
                DB::table('fatura_eng_notas')->insert([
                    'fatura_eng_id' => $fatura->id,
                    'empresa_id'    => $empresa_id,
                    'numero_nfse'   => $numeroNfse,
                    'chave_nfse'    => $codVerificacao,
                    'xml_nfse'      => $xmlRetorno,
                    'url_pdf'       => $linkPdf,
                    'status'        => (stripos($xmlDecodificado, '<Codigo>0</Codigo>') !== false) ? 'processando' : 'aprovado',
                    'created_at'    => now(),
                    'updated_at'    => now()
                ]);

                $notasGeradas++;
            }

            // =========================================================================
            // 5. ATUALIZA A MEDIÇÃO E NOTIFICA O OPERADOR
            // =========================================================================
            if ($notasGeradas > 0) {
                // Ao menos uma nota foi emitida: Atualiza a Fatura Principal no ERP
                $fatura->update(['estado_nfse' => 'aprovado']);

                if (count($errosGerados) > 0) {
                    $msgErro = implode(" | ", $errosGerados);
                    return redirect()->back()->with('mensagem_sucesso', "Atenção: {$notasGeradas} nota(s) gerada(s) com sucesso. Contudo, algumas falharam: {$msgErro}");
                }

                return redirect()->back()->with('mensagem_sucesso', "{$notasGeradas} nota(s) fiscal(is) gerada(s) com sucesso para os diferentes serviços!");
            } else {
                // Todas falharam
                $msgErro = implode(" | ", $errosGerados);
                return redirect()->back()->with('mensagem_erro', "Nenhuma nota pôde ser gerada. Erros da Prefeitura: {$msgErro}");
            }

        } catch (\Throwable $th) {
            \Illuminate\Support\Facades\Log::error("Erro NFS-e Medição (Multi) #{$id}: " . $th->getMessage());

            // Para não quebrar a coluna histórica (se existir)
            if (\Illuminate\Support\Facades\Schema::hasColumn('faturas_engenharia', 'estado_nfse')) {
                $fatura->update(['estado_nfse' => 'rejeitado']);
            }

            return redirect()->back()->with('mensagem_erro', 'Erro críico na emissão: ' . $th->getMessage());
        }
    }

    public function consultarNfse(Request $request, $id)
    {
        $empresa_id = session('user_logged')['empresa'] ?? null;
        $fatura = FaturaEngenharia::where('empresa_id', $empresa_id)->findOrFail($id);

        $config = \App\Models\ConfigNota::where('empresa_id', $empresa_id)->first();
        $certRes = \App\Models\Certificado::where('empresa_id', $empresa_id)->first();

        try {
            $certContent = file_exists(storage_path('app/public/certificados/' . $certRes->arquivo))
                ? file_get_contents(storage_path('app/public/certificados/' . $certRes->arquivo))
                : $certRes->arquivo;
            $certificate = \NFePHP\Common\Certificate::readPfx($certContent, $certRes->senha);

            $codigoIbge = $config->codMun ?? '2910057';
            $provedor = ($codigoIbge == '2910057') ? 'diasdavila' : 'nacional';

            $emissor = \App\Services\Nfse\NfseServiceFactory::criar($provedor, $config->toArray(), $certificate);

            $retorno = $emissor->consultar($fatura->id);

            $statusRetorno = strtoupper($retorno['status'] ?? '');
            $isCancelada   = $retorno['cancelada'] ?? false;
            $numeroOficial = $retorno['numero_nfse'] ?? null;
            $codigoVerif   = $retorno['codigo_verificacao'] ?? null;

            if ($isCancelada || $statusRetorno === 'CANCELADO') {
                $fatura->update(['estado_nfse' => 'cancelado']);
                return redirect()->back()->with('error', 'A NFS-e encontra-se cancelada.');
            }

            if ($statusRetorno === 'AUTORIZADO' || !empty($numeroOficial)) {
                $fatura->update([
                    'estado_nfse'        => 'aprovado',
                    'numero_nfse'        => $numeroOficial,
                    'codigo_verificacao' => $codigoVerif ?? $fatura->codigo_verificacao
                ]);

                return redirect()->back()->with('sucesso', "NFS-e aprovada! Número oficial: {$numeroOficial}");
            }

            return redirect()->back()->with('warning', 'A nota ainda consta como processando.');

        } catch (\Throwable $th) {
            // AQUI ESTÁ A CORREÇÃO PRINCIPAL DO ERRO NA TELA BRANCA
            return redirect()->back()->with('error', 'Erro ao consultar: ' . $th->getMessage());
        }
    }

    public function baixarXml($id)
    {
        $empresa_id = session('user_logged')['empresa'] ?? null;
        $fatura = FaturaEngenharia::where('empresa_id', $empresa_id)->findOrFail($id);

        $path = public_path('nfse_doc/Medicao_NFSe_' . $fatura->id . '.xml');

        if (file_exists($path)) {
            return response()->download($path);
        } else {
            return redirect()->back()->with('mensagem_erro', 'Arquivo XML não encontrado!');
        }
    }

    public function imprimirDanfeNfse($id)
    {
        $empresa_id = session('user_logged')['empresa'] ?? null;
        $fatura = FaturaEngenharia::with(['cliente', 'itens.servico'])->where('empresa_id', $empresa_id)->findOrFail($id);

        $config = \App\Models\ConfigNota::where('empresa_id', $empresa_id)->first();

        $emitente = (object) [
            'razao_social'        => $config->razao_social ?? $config->nome ?? 'CICLOS SUSTENTABILIDADE AMBIENTAL LTDA',
            'cnpj'                => $config->cnpj ?? '57425351000161',
            'inscricao_municipal' => $config->inscricao_municipal ?? $config->im ?? '16228',
            'optante_simples'     => $config->optante_simples ?? '0',
            'endereco'            => $config->logradouro ?? $config->endereco ?? 'RUA DOIS DE JULHO, S N',
            'numero'              => $config->numero ?? '',
            'bairro'              => $config->bairro ?? 'POLO DE SERVICOS',
            'cidade'              => $config->cidade_nome ?? 'Dias d\'Ávila',
            'uf'                  => $config->uf ?? 'BA',
            'cep'                 => $config->cep ?? '',
            'telefone'            => $config->telefone ?? '',
            'email'               => $config->email ?? ''
        ];

        // Se a base de dados ainda tiver as colunas a null, tentamos extrair do XML ou atribuímos o retorno
        if (empty($fatura->chave_nfse)) {
            $fatura->chave_nfse = 'DPS-' . $fatura->id . '-' . ($fatura->numero_nfse ?? '1');
        }
        if (empty($fatura->protocolo_nfse)) {
            $fatura->protocolo_nfse = $fatura->codigo_verificacao ?? 'AUTORIZADO';
        }

        $title = 'Impressão NFS-e - Medição #' . $fatura->id;

        // IMPORTANTE: Deve ser 'contratos.medicoes.danfse' e NÃO 'nfse.imprimir'
        return view('contratos.medicoes.danfse', compact('fatura', 'config', 'emitente', 'title'));
    }

    public function cancelarNfse(Request $request, $id)
    {
        $empresa_id = session('user_logged')['empresa'] ?? null;
        $fatura = FaturaEngenharia::where('empresa_id', $empresa_id)->findOrFail($id);

        if (empty($fatura->numero_nfse)) {
            return redirect()->back()->with('error', 'Esta medição não possui uma nota autorizada para cancelar.');
        }

        $config = \App\Models\ConfigNota::where('empresa_id', $empresa_id)->first();
        $certRes = \App\Models\Certificado::where('empresa_id', $empresa_id)->first();

        // Motivo do cancelamento vindo do formulário (se aplicável, ou fixo)
        $motivo = $request->input('motivo_cancelamento', 'Erro na emissao');

        try {
            $certContent = file_exists(storage_path('app/public/certificados/' . $certRes->arquivo))
                ? file_get_contents(storage_path('app/public/certificados/' . $certRes->arquivo))
                : $certRes->arquivo;
            $certificate = \NFePHP\Common\Certificate::readPfx($certContent, $certRes->senha);

            $codigoIbge = $config->codMun ?? '2910057';
            $provedor = ($codigoIbge == '2910057') ? 'diasdavila' : 'nacional';

            $emissor = \App\Services\Nfse\NfseServiceFactory::criar($provedor, $config->toArray(), $certificate);

            // Chama o método de cancelar no seu provedor (adapte se o método se chamar de outra forma)
            $retorno = $emissor->cancelar([
                'numero_nfse' => $fatura->numero_nfse,
                'codigo_verificacao' => $fatura->codigo_verificacao,
                'motivo' => $motivo
            ]);

            // Se o retorno indicar sucesso ou erro mas que já estava cancelada
            if ((isset($retorno['sucesso']) && $retorno['sucesso']) || (isset($retorno['status']) && $retorno['status'] == 'CANCELADO')) {
                // APENAS ATUALIZA O STATUS, NUNCA FAZ DELETE
                $fatura->update([
                    'estado_nfse' => 'cancelado',
                    'status' => 'Cancelado' // se usar este campo
                ]);

                return redirect()->back()->with('sucesso', 'Nota Fiscal cancelada com sucesso na prefeitura!');
            }

            return redirect()->back()->with('error', 'Erro ao cancelar: ' . ($retorno['mensagem'] ?? 'Erro desconhecido.'));

        } catch (\Throwable $th) {
            return redirect()->back()->with('error', 'Falha na comunicação de cancelamento: ' . $th->getMessage());
        }
    }

    public function carregarDadosApuraColeta(Request $request)
    {
        $empresaId  = $this->empresaId();
        $contratoId = $request->input('contrato_id');
        $dtInicio   = $request->input('data_inicial');
        $dtFim      = $request->input('data_final');

        $contrato = ContratoEngenharia::with('itens')->where('empresa_id', $empresaId)->findOrFail($contratoId);
        $clienteId = $contrato->cliente_id;
        $itensContrato = $contrato->itens->keyBy('produto_id');

        // ====================================================================
        // 1. Encontrar o Fornecedor equivalente (À PROVA DE PONTOS/TRAÇOS)
        // ====================================================================
        $cliente = Cliente::find($clienteId);
        $fornecedorEquivalenteId = null;

        if ($cliente && !empty($cliente->cpf_cnpj)) {
            $cpfCnpjLimpo = preg_replace('/[^0-9]/', '', $cliente->cpf_cnpj);
            $fornecedor = \App\Models\Fornecedor::where('empresa_id', $empresaId)
                ->whereRaw("REPLACE(REPLACE(REPLACE(cpf_cnpj, '.', ''), '/', ''), '-', '') = ?", [$cpfCnpjLimpo])
                ->first();

            if ($fornecedor) {
                $fornecedorEquivalenteId = $fornecedor->id;
            }
        }

        // ====================================================================
        // 2. BUSCA 1: AGENDA (Apenas para Serviços, Descarte e Fretes)
        // ====================================================================
        $destinos = DB::table('movimentacao_destinos as md')
            ->join('movimentacoes_veiculos as mv', 'mv.id', '=', 'md.movimentacao_veiculo_id')
            ->where('mv.empresa_id', $empresaId)
            ->where(function($query) use ($clienteId, $fornecedorEquivalenteId) {
                $query->where('md.cliente_id', $clienteId);
                if ($fornecedorEquivalenteId) {
                    $query->orWhere('md.fornecedor_id', $fornecedorEquivalenteId);
                }
            })
            ->whereBetween(DB::raw('DATE(COALESCE(md.data_hora_saida, md.created_at))'), [$dtInicio, $dtFim])
            ->select('md.id as destino_id', 'mv.id as viagem_id', 'md.is_aterro', 'md.data_hora_saida', 'md.created_at', 'mv.data_hora_saida as mv_data_saida', 'mv.data_hora_saida_real')
            ->orderBy(DB::raw('COALESCE(md.data_hora_saida, md.created_at)'), 'asc')
            ->get();

        $destinoIds = $destinos->pluck('destino_id')->toArray();

        $viagensComPesagem = DB::table('pesagens')
            ->where('empresa_id', $empresaId)
            ->whereNotNull('movimentacao_id')
            ->whereRaw("LOWER(status) LIKE '%conclu%'")
            ->pluck('movimentacao_id')
            ->toArray();

        $coletasViagem = collect();
        if (count($destinoIds) > 0) {
            $queryColetas = DB::table('movimentacao_destino_itens as mi')
                ->join('produtos as p', 'p.id', '=', 'mi.produto_id')
                ->join('movimentacao_destinos as md', 'md.id', '=', 'mi.movimentacao_destino_id')
                ->join('movimentacoes_veiculos as mv', 'mv.id', '=', 'md.movimentacao_veiculo_id')
                ->whereIn('mi.movimentacao_destino_id', $destinoIds)
                ->where('mi.quantidade', '>', 0);

            if (count($viagensComPesagem) > 0) {
                $queryColetas->whereNotIn('mv.id', $viagensComPesagem);
            }

            $coletasViagem = $queryColetas->select(
                'mi.produto_id',
                'p.nome as produto_nome',
                'mi.unidade',
                DB::raw('DATE(COALESCE(mv.data_hora_saida_real, mv.data_hora_saida, md.created_at)) as data_coleta'),
                DB::raw('SUM(mi.quantidade) as quantidade_total')
            )
                ->groupBy('mi.produto_id', 'p.nome', 'mi.unidade', DB::raw('DATE(COALESCE(mv.data_hora_saida_real, mv.data_hora_saida, md.created_at))'))
                ->get();
        }

        // ====================================================================
        // 3. BUSCA 2: BALANÇA (Sempre puxa a Sucata/Abatimento daqui)
        // ====================================================================
        $pesagensDiretas = DB::table('tickets_pesagem as tp')
            ->join('pesagens as p', 'p.id', '=', 'tp.pesagem_id')
            ->join('produtos as prod', 'prod.id', '=', 'tp.produto_id')
            ->where('p.empresa_id', $empresaId)
            ->where('p.tipo', 'compra') // 🚀 REGRA DE OURO: Apenas Compras de Sucata abatem!
            ->where(function($q) use ($clienteId, $fornecedorEquivalenteId) {
                $q->where('p.cliente_id', $clienteId)
                    ->orWhere('p.fornecedor_id', $clienteId);
                if ($fornecedorEquivalenteId) {
                    $q->orWhere('p.fornecedor_id', $fornecedorEquivalenteId)
                        ->orWhere('p.cliente_id', $fornecedorEquivalenteId);
                }
            })
            ->whereRaw("LOWER(p.status) LIKE '%conclu%'")
            ->whereBetween('p.dt_registro', [$dtInicio, $dtFim])
            ->select(
                'tp.produto_id',
                'prod.nome as produto_nome',
                DB::raw("'KG' as unidade"),
                'p.dt_registro as data_coleta',
                // 🚀 MATEMÁTICA CORRETA DA BALANÇA: Faz Entrada - Saída para achar o Saldo Líquido
                DB::raw("SUM(CASE WHEN LOWER(tp.tipo) = 'entrada' THEN tp.peso WHEN LOWER(tp.tipo) = 'saida' THEN -tp.peso ELSE tp.peso END) as quantidade_total")
            )
            ->groupBy('tp.produto_id', 'prod.nome', 'p.dt_registro')
            ->get();

        // ====================================================================
        // 4. PROCESSAMENTO E SEPARAÇÃO INTELIGENTE
        // ====================================================================
        $itensResiduos = [];
        $itensAbatimento = [];
        $totalResiduos = 0;
        $totalAbatimento = 0;

        foreach ($coletasViagem as $col) {
            $regraContrato = $itensContrato->get($col->produto_id);
            if (!$regraContrato) continue;

            if ($regraContrato->tipo_item === 'Residuo' && empty($regraContrato->servico_id)) {
                continue;
            }

            $qtdOriginal = (float) $col->quantidade_total;
            if ($qtdOriginal <= 0) continue;

            $unidadeContrato = strtoupper(trim($regraContrato->unidade ?? 'TON'));
            $unidadeFrota    = strtoupper(trim($col->unidade ?? 'KG'));
            $precoUnitario   = (float) ($regraContrato->valor_unitario ?? 0);

            if ($unidadeContrato === 'TON' && in_array($unidadeFrota, ['KG', 'KGS', 'QUILO', 'QUILOGRAMA'])) {
                $qtdCalculo = $qtdOriginal / 1000;
                $unidadeExibicao = 'TON';
            } else {
                $qtdCalculo = $qtdOriginal;
                $unidadeExibicao = $unidadeContrato;
            }

            $subtotal = round($qtdCalculo * $precoUnitario, 2);
            $dataColetaFormatada = date('d/m/Y', strtotime($col->data_coleta));

            $totalResiduos += $subtotal;
            $itensResiduos[] = [
                'tipo_item'      => 'Residuo',
                'produto_id'     => $col->produto_id,
                'servico_id'     => $regraContrato->servico_id,
                'descricao'      => "{$dataColetaFormatada} - Coleta/Descarte: {$col->produto_nome}",
                'quantidade'     => round($qtdCalculo, 3),
                'unidade'        => $unidadeExibicao,
                'valor_unitario' => $precoUnitario,
                'valor_total'    => $subtotal,
            ];
        }

        foreach ($pesagensDiretas as $col) {
            $regraContrato = $itensContrato->get($col->produto_id);
            if (!$regraContrato) continue;

            if (!empty($regraContrato->servico_id)) {
                continue;
            }

            $qtdOriginal = (float) $col->quantidade_total;
            if ($qtdOriginal <= 0) continue; // 🚀 Previne saldos negativos ou zerados na balança

            $unidadeContrato = strtoupper(trim($regraContrato->unidade ?? 'TON'));
            $precoUnitario   = (float) ($regraContrato->valor_unitario ?? 0);

            if ($unidadeContrato === 'TON') {
                $qtdCalculo = $qtdOriginal / 1000;
                $unidadeExibicao = 'TON';
            } else {
                $qtdCalculo = $qtdOriginal;
                $unidadeExibicao = $unidadeContrato;
            }

            $subtotal = round($qtdCalculo * $precoUnitario, 2);
            $dataColetaFormatada = date('d/m/Y', strtotime($col->data_coleta));

            $totalAbatimento += $subtotal;
            $itensAbatimento[] = [
                'tipo_item'      => 'Residuo',
                'produto_id'     => $col->produto_id,
                'servico_id'     => null,
                'descricao'      => "{$dataColetaFormatada} - Compra de Sucata: {$col->produto_nome}",
                'quantidade'     => round($qtdCalculo, 3),
                'unidade'        => $unidadeExibicao,
                'valor_unitario' => $precoUnitario,
                'valor_total'    => $subtotal,
            ];
        }

        // ====================================================================
        // 5. FRETES E LOCAÇÕES
        // ====================================================================
        $itensFrete = [];
        $totalFrete = 0;

        foreach ($destinos as $dest) {
            $dataViagemFormatada = date('d/m/Y', strtotime($dest->data_hora_saida ?? $dest->mv_data_saida ?? $dest->created_at));
            $itemFreteContrato = $contrato->itens->firstWhere('tipo_item', 'Frete');

            if ($itemFreteContrato) {
                $valorFreteUnit = (float) ($itemFreteContrato->valor_unitario ?? 0);
                $totalFrete += $valorFreteUnit;

                $itensFrete[] = [
                    'tipo_item'      => 'Frete',
                    'servico_id'     => $itemFreteContrato->servico_id ?? null,
                    'produto_id'     => null,
                    'descricao'      => "{$dataViagemFormatada} - Frete de Coleta (Viagem #{$dest->viagem_id})",
                    'quantidade'     => 1,
                    'unidade'        => 'VIAGEM',
                    'valor_unitario' => $valorFreteUnit,
                    'valor_total'    => $valorFreteUnit,
                ];
            }
        }

        $itensLocacao = [];
        $totalLocacao = 0;
        foreach ($contrato->itens->where('tipo_item', 'Locacao') as $loc) {
            $qtdLoc = (float) $loc->quantidade_prevista;
            if ($qtdLoc > 0) {
                $prod = DB::table('produtos')->where('id', $loc->produto_id)->first();
                $sub = round($qtdLoc * (float) $loc->valor_unitario, 2);
                $totalLocacao += $sub;

                $itensLocacao[] = [
                    'tipo_item'      => 'Locacao',
                    'servico_id'     => $loc->servico_id ?? null,
                    'produto_id'     => $loc->produto_id,
                    'descricao'      => "Locação Período: " . ($prod->nome ?? 'Equipamento / Caçamba'),
                    'quantidade'     => $qtdLoc,
                    'unidade'        => $loc->unidade ?: 'MES',
                    'valor_unitario' => (float) $loc->valor_unitario,
                    'valor_total'    => $sub,
                ];
            }
        }

        $totalBruto = $totalResiduos + $totalFrete + $totalLocacao;

        return response()->json([
            'sucesso'          => true,
            'residuos'         => $itensResiduos,
            'frete'            => $itensFrete,
            'locacoes'         => $itensLocacao,
            'abatimentos'      => $itensAbatimento,
            'total_residuos'   => $totalResiduos,
            'total_frete'      => $totalFrete,
            'total_locacao'    => $totalLocacao,
            'total_abatimento' => $totalAbatimento,
            'total_bruto'      => $totalBruto,
            'total_liquido'    => max(0, $totalBruto - $totalAbatimento),
        ]);
    }
}
