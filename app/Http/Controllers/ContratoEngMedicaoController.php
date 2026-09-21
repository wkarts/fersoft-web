<?php

namespace App\Http\Controllers;

use App\Models\CategoriaConta;
use App\Models\Cidade;
use App\Models\Cliente;
use App\Models\ContratoEngenharia;
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

class ContratoEngMedicaoController extends Controller
{
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
        if ($request->filled('contrato_eng_id')) {
            $query->where('contrato_eng_id', $request->contrato_eng_id);
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
                'municipio_prestacao_id' => $request->input('cidade_prestacao_id'),
                'codigo_obra' => $request->input('codigo_obra'),
                'valor_total' => $valorTotal,
                'valor_retencao' => $valorRetencao,
                'valor_liquido' => $valorTotal - $valorRetencao,
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
            $valorRetencao = $this->money($request->input('valor_retencao', 0));

            $fatura->update([
                'contrato_eng_id' => $novoContratoId,
                'cliente_id' => $request->input('cliente_id'),
                'categoria_conta_id' => $request->input('categoria_conta_id'),
                'servico_id' => $request->input('servico_id'),
                'municipio_prestacao_id' => $request->input('cidade_prestacao_id'),
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

    public function enviarWhatsapp(Request $request, $id)
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
            'funcionarios' => DB::table('funcionarios')->where('empresa_id', $empresaId)->orderBy('nome')->get(),
            'tiposPagamento' => ['Dinheiro', 'Boleto', 'Cartão de Crédito', 'Cartão de Débito', 'Pix', 'Transferência'],
        ];
    }

    private function replaceItems(int $faturaId, array $items): void
    {
        FaturaEngItem::where('fatura_eng_id', $faturaId)->delete();

        foreach ($items as $item) {
            $tipo = ($item['tipo_item'] ?? 'Servico') === 'Locacao' ? 'Locacao' : 'Servico';
            $qtd = $this->decimal($item['quantidade'] ?? 1);
            $valor = $this->money($item['valor_unitario'] ?? $item['valor'] ?? 0);
            $total = $this->money($item['sub_total'] ?? $item['subtotal'] ?? ($qtd * $valor));

            if (
                empty($item['servico_id'])
                && empty($item['produto_id'])
                && empty($item['descricao'])
            ) {
                continue;
            }

            FaturaEngItem::create([
                'fatura_eng_id' => $faturaId,
                'tipo_item' => $tipo,
                'servico_id' => $tipo === 'Servico' ? ($item['servico_id'] ?? null) : null,
                'produto_id' => $tipo === 'Locacao' ? ($item['produto_id'] ?? null) : null,
                'descricao' => $item['descricao'] ?? null,
                'quantidade' => $qtd,
                'valor_unitario' => $valor,
                'sub_total' => $total,
                'valor_total' => $total,
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
            $query->update([
                'categoria_id' => $fatura->categoria_conta_id,
                'observacao' => $request->input('observacao'),
                'updated_at' => now(),
            ]);
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

            DB::table('conta_recebers')->where('id', $row->id)->update([
                'valor_integral' => $value,
                'categoria_id' => $fatura->categoria_conta_id,
                'observacao' => $request->input('observacao'),
                'updated_at' => now(),
            ]);
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
            'title' => 'Medição #' . $fatura->id,
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
}
