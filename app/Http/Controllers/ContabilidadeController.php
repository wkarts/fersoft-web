<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\PlanoContasContabil;
use App\Models\Usuario; // <-- Adicionamos o modelo do utilizador!
use Illuminate\Support\Facades\DB;
use App\Services\ContabilidadeService;

class ContabilidadeController extends BaseController
{
    public function __construct()
    {
        $this->redirectPage = '/contabilidade/plano-contas';
        $this->formTitle = 'Integração Contábil';

        parent::__construct();
    }

    protected function rules(): array
    {
        return [];
    }

    protected function messages(): array
    {
        return [];
    }

    /**
     * Função auxiliar para descobrir a empresa de forma 100% segura
     */
    private function getEmpresaId()
    {
        // 1. Tenta ir buscar ao BaseController (se vier no formulário)
        if (!empty($this->empresa_id)) {
            return $this->empresa_id;
        }

        // 2. Se não vier, utiliza o ID do utilizador logado para procurar a empresa dele
        if (!empty($this->usuario_id)) {
            $usuario = Usuario::find($this->usuario_id);
            return $usuario ? $usuario->empresa_id : null;
        }

        return null;
    }

    public function index(Request $request)
    {
        $empresaId = $this->getEmpresaId();

        if (!$empresaId) {
            return back()->with('erro', 'Não foi possível identificar a empresa. Verifique a sessão do utilizador.');
        }

        $contas = PlanoContasContabil::where('empresa_id', $empresaId)
            ->when($request->get('pesquisa'), function($query, $pesquisa) {
                return $query->where(function($q) use ($pesquisa) {
                    $q->where('nome', 'LIKE', "%{$pesquisa}%")
                      ->orWhere('codigo_acesso', 'LIKE', "%{$pesquisa}%")
                      ->orWhere('classificador', 'LIKE', "%{$pesquisa}%");
                });
            })
            ->orderBy('classificador', 'asc')
            ->paginate(50); 

        $data = [
            'title'   => 'Plano de Contas Contábil',
            'titulo'  => 'Arquivos Contábil',
            'contas'  => $contas,
            'filters' => $request->all() 
        ];

        return view('contabilidade.importar', $data);
    }

    public function importarPlanoContas(Request $request)
    {
        $request->validate([
            'arquivo_plano' => 'required|file|mimetypes:text/plain'
        ]);

        $empresaId = $this->getEmpresaId();

        if (!$empresaId) {
            return back()->with('erro', 'Não foi possível identificar a empresa para gravar os registos.');
        }

        $caminho = $request->file('arquivo_plano')->getRealPath();
        $linhas = file($caminho, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);

        DB::beginTransaction();

        try {
            // (Opcional) Se quiser que o sistema limpe o plano antigo antes de importar o novo,
            // basta remover as duas barras "//" da linha abaixo:
            // PlanoContasContabil::where('empresa_id', $empresaId)->delete();

            foreach ($linhas as $linha) {
                // Converte a linha inteira para o padrão UTF-8 para corrigir os acentos (ç, ã, õ)
                $linha = mb_convert_encoding($linha, 'UTF-8', 'ISO-8859-1');

                $codigoAcesso  = trim(mb_substr($linha, 0, 5));
                $classificador = trim(mb_substr($linha, 5, 15));
                $nome          = trim(mb_substr($linha, 20, 30));
                $indicador     = trim(mb_substr($linha, 50, 6));

                $aceitaLancamento = !in_array($indicador, ['0000 0', '000000', '0000N0', '000001']);

                if (!empty($codigoAcesso) && is_numeric($codigoAcesso)) {
                    PlanoContasContabil::create([
                        'empresa_id'        => $empresaId,
                        'codigo_acesso'     => $codigoAcesso,
                        'classificador'     => $classificador,
                        'nome'              => $nome,
                        'aceita_lancamento' => $aceitaLancamento
                    ]);
                }
            }

            DB::commit();

            // Grava o log no histórico do ERP
            if (property_exists($this, 'logService') && $this->logService) {
                $this->logService->registrar('import', PlanoContasContabil::class, [
                    'registro_id' => null,
                    'dados_antes' => null,
                    'dados_depois' => json_encode(['mensagem' => 'Plano de contas TXT importado com sucesso'], JSON_UNESCAPED_UNICODE)
                ]);
            }

            return back()->with('sucesso', 'Plano de contas importado com sucesso!');

        } catch (\Exception $e) {
            DB::rollBack();
            return back()->with('erro', 'Erro ao importar: ' . $e->getMessage());
        }
    }
  
  public function gerarTerceiros(\Illuminate\Http\Request $request)
    {
        // 1. Obtém o ID da Empresa da sessão ou do usuário logado
        $empresaId = auth()->check() ? auth()->user()->empresa_id : session('empresa_id');

        $userLogged = session('user_logged');
        if (!$empresaId && is_array($userLogged)) {
            $empresaId = $userLogged['empresa_id'] ?? null;
            if (!$empresaId && isset($userLogged['empresa'])) {
                $empresa = $userLogged['empresa'];
                $empresaId = is_numeric($empresa) ? $empresa : (is_array($empresa) ? ($empresa['id'] ?? null) : ($empresa->id ?? null));
            }
        } elseif (!$empresaId && is_object($userLogged)) {
            $empresaId = $userLogged->empresa_id ?? null;
        }

        // Se por algum motivo a sessão cair, evita o erro parando o processo
        if (!$empresaId) {
            return redirect()->back()->with('error', 'Sessão expirada ou empresa não identificada.');
        }

        // 2. Instancia o Service
        $contabilidadeService = new \App\Services\ContabilidadeService();

        // 3. Chama a função passando APENAS o número do ID da empresa
        $conteudoArquivo = $contabilidadeService->gerarArquivoTerceiros($empresaId);

        // 4. Retorna o arquivo para download
        return response($conteudoArquivo)
            ->header('Content-Type', 'text/plain')
            ->header('Content-Disposition', 'attachment; filename="Terceiros.txt"');
    }
}