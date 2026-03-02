<?php

namespace App\Http\Controllers;

use App\Http\Requests\PontoAfdImportRequest;
use App\Models\PontoAfdArquivo;
use App\Models\PontoAfdRegistro;
use App\Models\PontoRelogio;
use App\Services\Ponto\PontoAfdImportService;

class PontoAfdController extends Controller
{
    protected $empresa_id;

    public function __construct(private PontoAfdImportService $importService)
    {
        $this->middleware(function ($request, $next) {
            $this->empresa_id = $request->empresa_id;
            if (!session('user_logged')) {
                return redirect('/login');
            }
            return $next($request);
        });
    }

    public function index()
    {
        $arquivos = PontoAfdArquivo::where('empresa_id', $this->empresa_id)
            ->orderBy('id', 'desc')
            ->paginate(20);

        $relogios = PontoRelogio::where('empresa_id', $this->empresa_id)->where('ativo', true)->get();

        return view('ponto_afd.index', compact('arquivos', 'relogios'))
            ->with('title', 'Importação AFD');
    }

    public function store(PontoAfdImportRequest $request)
    {
        $value = session('user_logged');

        $result = $this->importService->importar(
            $request->file('arquivo_afd'),
            (int)$this->empresa_id,
            $value['id'] ?? null,
            $request->input('ponto_relogio_id')
        );

        if ($result['duplicado']) {
            session()->flash('mensagem_erro', 'Arquivo já importado para esta empresa.');
            return redirect('/ponto/importacao-afd');
        }

        session()->flash('mensagem_sucesso', 'Arquivo AFD importado com sucesso!');
        return redirect('/ponto/importacao-afd/' . $result['arquivo']->id);
    }

    public function show($id)
    {
        $arquivo = PontoAfdArquivo::where('empresa_id', $this->empresa_id)->findOrFail($id);
        $registros = PontoAfdRegistro::where('empresa_id', $this->empresa_id)
            ->where('ponto_afd_arquivo_id', $id)
            ->orderBy('numero_linha')
            ->paginate(50);

        return view('ponto_afd.show', compact('arquivo', 'registros'))
            ->with('title', 'Detalhes da Importação AFD');
    }
}
