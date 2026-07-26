<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Pesagem;
use App\Models\Fornecedor;
use App\Models\PesagemPagamento;
use Illuminate\Support\Facades\DB;

class PagamentoLoteController extends BaseController
{
    public function __construct()
    {
        // Variáveis obrigatórias do BaseController
        $this->redirectPage = '/pagamento-lote';
        $this->formTitle    = 'Lote de Pagamentos';
        $this->listTitle    = 'Lote de {form_title}'; 
        
        parent::__construct();
    }

    protected function rules(): array { return []; }
    protected function messages(): array { return []; }
public function index(Request $request)
    {
        $title = $this->formatString($this->listTitle, ['form_title' => $this->formTitle]);
        $empresaId = (int) $this->empresa_id;
        $dataInicial = $request->input('data_inicial', now()->format('Y-m-d'));
        $dataFinal = $request->input('data_final', now()->format('Y-m-d'));

        try {
            $inicio = \Carbon\Carbon::parse($dataInicial)->startOfDay();
            $fim = \Carbon\Carbon::parse($dataFinal)->endOfDay();
            if ($inicio->gt($fim)) [$inicio, $fim] = [$fim->copy()->startOfDay(), $inicio->copy()->endOfDay()];
        } catch (\Throwable $e) {
            return redirect()->back()->withInput()->with('mensagem_erro', 'Período informado é inválido.');
        }

        $query = Pesagem::withoutGlobalScopes()
            ->with(['fornecedor', 'tickets', 'veiculo'])
            ->where('empresa_id', $empresaId)
            ->whereRaw('LOWER(tipo) = ?', ['compra'])
            ->whereRaw("LOWER(REPLACE(status, 'í', 'i')) = ?", ['concluido'])
            ->whereNotExists(function ($subQuery): void {
                $subQuery->selectRaw('1')->from('pesagem_pagamentos')
                    ->whereColumn('pesagem_pagamentos.pesagem_id', 'pesagens.id');
            })
            ->where(function ($q) use ($inicio, $fim): void {
                $q->whereBetween('dt_registro', [$inicio, $fim])
                    ->orWhere(function ($q2) use ($inicio, $fim): void {
                        $q2->whereNull('dt_registro')->whereBetween('created_at', [$inicio, $fim]);
                    });
            });

        if ($this->filial_id !== null) {
            $query->where(function ($q): void {
                $q->where('filial_id', $this->filial_id)->orWhereNull('filial_id');
            });
        }

        $cnpjEmpresa = preg_replace('/\D+/', '', (string) DB::table('empresas')->where('id', $empresaId)->value('cnpj'));
        if ($cnpjEmpresa === '') {
            $cnpjEmpresa = preg_replace('/\D+/', '', (string) DB::table('config_notas')->where('empresa_id', $empresaId)->value('cnpj'));
        }

        $listaParaPagamento = $query->orderByDesc('id')->get()->map(function ($pesagem) use ($empresaId, $cnpjEmpresa) {
            return (object) $this->calcularPagamentoPesagem($pesagem, $empresaId, $cnpjEmpresa);
        })->all();

        return view('pagamento_lote.index', compact('listaParaPagamento', 'title', 'dataInicial', 'dataFinal'));
    }
public function gerarArquivoPix(Request $request)
    {
        $ids = array_values(array_unique(array_filter(array_map('intval', (array) $request->input('pesagens_ids', [])))));
        if ($ids === []) return redirect()->back()->with('mensagem_erro', 'Selecione pelo menos uma pesagem.');
        if (count($ids) > 500) return redirect()->back()->with('mensagem_erro', 'O lote excede o limite de 500 pagamentos.');

        $empresaId = (int) $this->empresa_id;
        $contaBancaria = \App\Models\ContaBancaria::query()
            ->where('empresa_id', $empresaId)->where('padrao', 1)->first();
        if (!$contaBancaria) return redirect()->back()->with('mensagem_erro', 'Configure uma conta bancária padrão para a empresa atual.');

        $empresa = \App\Models\Empresa::query()->findOrFail($empresaId);
        $cnpjEmpresa = str_pad(preg_replace('/\D+/', '', (string) $empresa->cnpj), 14, '0', STR_PAD_LEFT);
        $agenciaLimpa = preg_replace('/\D+/', '', (string) $contaBancaria->agencia);
        $contaLimpa = preg_replace('/\D+/', '', (string) $contaBancaria->conta);
        if ($agenciaLimpa === '' || strlen($contaLimpa) < 2) return redirect()->back()->with('mensagem_erro', 'Agência ou conta padrão está incompleta.');

        $agencia = str_pad(substr($agenciaLimpa, -5), 5, '0', STR_PAD_LEFT);
        $dac = substr($contaLimpa, -1);
        $conta = str_pad(substr($contaLimpa, 0, -1), 7, '0', STR_PAD_LEFT);
        $nomeEmpresa = $this->normalizarCnab((string) ($empresa->nome ?? $empresa->nome_fantasia ?? ''), 30);

        $pesagens = Pesagem::withoutGlobalScopes()->with(['fornecedor', 'tickets', 'veiculo'])
            ->where('empresa_id', $empresaId)->whereIn('id', $ids)
            ->whereNotExists(function ($q): void {
                $q->selectRaw('1')->from('pesagem_pagamentos')->whereColumn('pesagem_pagamentos.pesagem_id', 'pesagens.id');
            })->get()->keyBy('id');
        if ($pesagens->count() !== count($ids)) return redirect()->back()->with('mensagem_erro', 'Uma ou mais pesagens não pertencem à empresa, já foram pagas ou não existem.');

        $linhas = [];
        $header = '04100000' . str_repeat(' ', 9) . '2' . $cnpjEmpresa . str_repeat(' ', 20) . $agencia . ' ' . $conta . ' ' . $dac . $nomeEmpresa . $this->normalizarCnab('BANCO ITAU SA', 30) . str_repeat(' ', 10) . '1' . date('dmYHis') . str_repeat(' ', 9) . '00000081';
        $linhas[] = str_pad(substr($header, 0, 240), 240);
        $headerLote = '04100011C204501 2' . $cnpjEmpresa . str_repeat(' ', 20) . $agencia . ' ' . $conta . ' ' . $dac . $nomeEmpresa;
        $linhas[] = str_pad(substr($headerLote, 0, 240), 240);

        $sequencial = 1; $total = 0.0; $quantidadePagamentos = 0;
        foreach ($ids as $id) {
            $pesagem = $pesagens->get($id);
            $calculo = $this->calcularPagamentoPesagem($pesagem, $empresaId, preg_replace('/\D+/', '', (string)$empresa->cnpj));
            if (!$calculo['tabela_ok'] || $calculo['valor_calculado'] <= 0 || empty($calculo['chave_pix'])) {
                return redirect()->back()->with('mensagem_erro', "A pesagem #{$id} não possui preço, peso líquido ou chave PIX válidos.");
            }
            $valorEnviado = (float) $request->input("valores.{$id}", $calculo['valor_calculado']);
            if (abs($valorEnviado - $calculo['valor_calculado']) > 0.01) {
                return redirect()->back()->with('mensagem_erro', "O valor da pesagem #{$id} foi alterado. Atualize a tela e tente novamente.");
            }
            $valor = round($calculo['valor_calculado'], 2); $total += $valor; $quantidadePagamentos++;
            $valorFmt = str_pad(number_format($valor, 2, '', ''), 15, '0', STR_PAD_LEFT);
            $nomeFav = $this->normalizarCnab((string)$pesagem->fornecedor->razao_social, 30);
            $chavePix = $this->normalizarCnab((string)$pesagem->fornecedor->pix, 60);
            $seqA = str_pad($sequencial++, 5, '0', STR_PAD_LEFT);
            $segA = '04100013'.$seqA.'A000000000'.str_repeat(' ',3).'0000 '.'0000000 0'.$nomeFav.str_repeat(' ',20).date('dmY').'BRL'.str_repeat('0',15).$valorFmt.str_repeat(' ',20).str_repeat('0',8).str_repeat(' ',27).'0'.str_repeat('0',10);
            $linhas[] = str_pad(substr($segA,0,240),240);
            $seqB = str_pad($sequencial++, 5, '0', STR_PAD_LEFT);
            $segB = '04100013'.$seqB.'B'.str_repeat(' ',3).'0'.str_repeat(' ',113).$chavePix.str_repeat(' ',45);
            $linhas[] = str_pad(substr($segB,0,240),240);
        }
        if ($quantidadePagamentos === 0) return redirect()->back()->with('mensagem_erro', 'Nenhum pagamento válido foi encontrado.');
        $qtdLote = count($linhas) + 1;
        $trailerLote = '04100015'.str_repeat(' ',9).str_pad($qtdLote,6,'0',STR_PAD_LEFT).str_pad(number_format($total,2,'',''),18,'0',STR_PAD_LEFT).str_repeat('0',18);
        $linhas[] = str_pad(substr($trailerLote,0,240),240);
        $qtdArquivo = count($linhas) + 1;
        $trailerArquivo = '04199999'.str_repeat(' ',9).'000001'.str_pad($qtdArquivo,6,'0',STR_PAD_LEFT);
        $linhas[] = str_pad(substr($trailerArquivo,0,240),240);

        foreach ($linhas as $n => $linha) {
            if (strlen($linha) !== 240) throw new \RuntimeException('Registro CNAB inválido na linha '.($n+1).'.');
        }
        $conteudo = implode("\r\n", $linhas)."\r\n";
        return response($conteudo, 200, [
            'Content-Type' => 'text/plain; charset=ISO-8859-1',
            'Content-Disposition' => 'attachment; filename="REM_PIX_ITAU_'.date('dmY_His').'.rem"',
            'X-Content-Type-Options' => 'nosniff',
        ]);
    }

    private function calcularPagamentoPesagem($pesagem, int $empresaId, string $cnpjEmpresa): array
    {
        $fornecedor = $pesagem->fornecedor;
        $tipoFrete = 'ENTREGA';
        $docProprietario = preg_replace('/\D+/', '', (string) optional($pesagem->veiculo)->proprietario_documento);
        if ($docProprietario !== '' && $docProprietario === $cnpjEmpresa) $tipoFrete = 'COLETA';
        elseif ($pesagem->veiculo && (int)$pesagem->veiculo->empresa_id === $empresaId && $docProprietario === '') $tipoFrete = 'COLETA';

        $entradas = (float)$pesagem->tickets->whereIn('tipo',['entrada','avulsa'])->sum('peso');
        $saidas = (float)$pesagem->tickets->where('tipo','saida')->sum('peso');
        $bags = (float)$pesagem->tickets->sum('peso_bag');
        $peso = abs($entradas-$saidas); if ($peso <= 0) $peso=max($entradas,$saidas);
        $peso=max(0,$peso-$bags);
        $desconto=0.0;
        foreach ([['danificado','danificado_desconto'],['quebrado','quebrado_desconto'],['esverdeado','esverdeado_desconto'],['ardido','ardido_desconto'],['secagem','secagem_desconto']] as [$flag,$campo]) {
            if ((bool)($pesagem->{$flag} ?? false)) $desconto += (float)($pesagem->{$campo} ?? 0);
        }
        $desconto += (float)($pesagem->umidade_desconto ?? 0)+(float)($pesagem->impureza_desconto ?? 0);
        $desconto=max(0,min(100,$desconto)); $pesoFinal=round($peso*(1-$desconto/100),4);
        $ticket=$pesagem->tickets->firstWhere('tipo','entrada') ?? $pesagem->tickets->firstWhere('tipo','avulsa') ?? $pesagem->tickets->first();
        $valorKg=0.0; $precoOk=false;
        if ($ticket && $fornecedor && $fornecedor->tabela_preco_id) {
            $valorKg=(float)DB::table('tabela_preco_itens')->where('tabela_preco_id',$fornecedor->tabela_preco_id)->where('produto_id',$ticket->produto_id)->where('tipo_frete',$tipoFrete)->value('valor_kg');
        }
        if ($valorKg<=0 && $ticket) $valorKg=(float)DB::table('produtos')->where('empresa_id',$empresaId)->where('id',$ticket->produto_id)->value('valor_compra');
        $precoOk=$valorKg>0 && $pesoFinal>0;
        return [
            'id'=>$pesagem->id,'data'=>$pesagem->dt_registro ?: $pesagem->created_at,
            'fornecedor_nome'=>$fornecedor->razao_social ?? 'Sem Fornecedor','chave_pix'=>$fornecedor->pix ?? '',
            'peso_total'=>$pesoFinal,'frete_usado'=>$tipoFrete,'valor_kg'=>$valorKg,
            'valor_calculado'=>round($pesoFinal*$valorKg,2),'tabela_ok'=>$precoOk,
        ];
    }

    private function normalizarCnab(string $valor, int $tamanho): string
    {
        $normalizado = iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', $valor) ?: $valor;
        $normalizado = strtoupper(preg_replace('/[^A-Z0-9 .,@+\-]/i', '', $normalizado));
        return str_pad(substr($normalizado, 0, $tamanho), $tamanho, ' ', STR_PAD_RIGHT);
    }
  
}