<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\SpedNfceConsolidado;
use App\Services\Sped\ConsolidarSomenteSaidaNfceService;

class SpedNfceConsolidadoController extends BaseController
{
    protected $model = SpedNfceConsolidado::class;
    protected $table = 'sped_nfce_consolidados';
    protected $resource = 'sped_nfce_consolidados';
    protected $formTitle = 'SPED NFC-e Consolidado';
    protected $listView = 'sped_nfce_consolidado.index'; // ÚNICA VIEW
    protected $registerView = 'sped_nfce_consolidado.index';
    protected $Prefix_Route = '/sped-nfce-consolidado';
    protected $redirectPage = '/sped-nfce-consolidado/list';

    public function __construct()
    {
        parent::__construct();
    }

    protected function headers(): array
    {
        return [
            'ID', 'CNPJ', 'Período', 'Original', 'Consolidado', 'NFCE (orig)', 'Grupos', 'Status', 'Ações'
        ];
    }

    protected function fields(): array
    {
        return [
            'id',
            'cnpj',
            function ($r) {
                $ini = $r->periodo_inicio?->format('d/m/Y');
                $fim = $r->periodo_fim?->format('d/m/Y');
                return trim(($ini ?: '') . ' - ' . ($fim ?: ''));
            },
            function ($r) {
                return '<a target="_blank" href="' . e($r->input_url) . '">' . e($r->input_filename) . '</a>';
            },
            function ($r) {
                return '<a target="_blank" href="' . e($r->output_url) . '">' . e($r->output_filename) . '</a>';
            },
            'total_nfce_original',
            'total_grupos_consolidados',
            'status',
            function ($r) {
                $dlOut = $this->Prefix_Route . '/download/' . $r->id . '?t=out';
                $dlIn  = $this->Prefix_Route . '/download/' . $r->id . '?t=in';
                return '<a class="btn btn-sm btn-primary" href="' . e($dlOut) . '">Baixar</a>
                        <a class="btn btn-sm btn-light" href="' . e($dlIn)  . '">Original</a>';
            },
        ];
    }

    protected function rules(): array
    {
        return [
            'arquivo_sped' => 'required|file|mimes:txt,text|max:512000',
        ];
    }

    protected function messages(): array
    {
        return [
            'arquivo_sped.required' => 'Selecione um arquivo SPED (.txt).',
            'arquivo_sped.mimes'    => 'O arquivo deve ser .txt.',
            'arquivo_sped.max'      => 'Arquivo muito grande (máx. 500MB).',
        ];
    }

    protected function defineFilters(Request $request): array
    {
        return [
            ['name' => 'cnpj',  'label' => 'CNPJ',  'type' => 'text',   'default' => ''],
            ['name' => 'status','label' => 'Status','type' => 'select', 'default' => ''],
        ];
    }

    public function list(Request $request)
    {
        $query = $this->getTenantRecords()->orderByDesc('id');

        if ($request->filled('cnpj')) {
            $query->where('cnpj', 'like', '%' . preg_replace('/\D+/', '', $request->cnpj) . '%');
        }
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        $records = $query->paginate(20);

        $title = $this->formatString($this->listTitle, ['form_title' => $this->formTitle]);
        $configSystemWhats = \App\Models\ConfigNota::where('empresa_id', $this->empresa_id)->first();
        $filiais = $this->filial_id;

        return view($this->listView, [
            'records' => $records,
            'title' => $title,
            'headers' => $this->headers(),
            'fields' => $this->fields(),
            'filterUrl' => "{$this->redirectPage}",
            'configSystemWhats' => $configSystemWhats,
            'filiais' => $filiais,
            'actionSave' => "{$this->Prefix_Route}/save",
            'actionCancel' => $this->redirectPage,
        ]);
    }

    public function save(Request $request)
    {
        if (!$this->validateRequest($request)) {
            return redirect()->back()->withInput();
        }

        try {
            $file = $request->file('arquivo_sped');
            if (!$file || !$file->isValid()) {
                throw new \Exception('Upload inválido.');
            }

            $tmpPath  = $file->getPathname();
            $origName = $file->getClientOriginalName();

            /** @var ConsolidarSomenteSaidaNfceService $svc */
            $svc = app(ConsolidarSomenteSaidaNfceService::class);

            $payload = $svc->processar($tmpPath, $origName, [
                'empresa_id' => $this->empresa_id,
                'usuario_id' => $this->usuario_id,
                'filial_id'  => $this->filial_id,
            ]);

            $payload['empresa_id'] = $this->empresa_id;
            $payload['usuario_id'] = $this->usuario_id;
            $payload['filial_id']  = $this->filial_id;

            SpedNfceConsolidado::create($payload);

            session()->flash('mensagem_sucesso', 'Consolidação concluída (somente SAÍDA NFC-e).');
        } catch (\Throwable $e) {
            \Log::error('Erro consolidando SPED NFC-e (somente saída, mod 65)', [
                'empresa_id' => $this->empresa_id,
                'usuario_id' => $this->usuario_id,
                'filial_id'  => $this->filial_id,
                'erro' => $e->getMessage(),
            ]);
            session()->flash('mensagem_erro', 'Falha na consolidação: ' . $e->getMessage());
        }

        return redirect($this->redirectPage);
    }

    public function download($id, Request $request)
    {
        $t = $request->query('t', 'out'); // out|in
        /** @var SpedNfceConsolidado $rec */
        $rec = SpedNfceConsolidado::where('empresa_id', $this->empresa_id)->findOrFail($id);

        $path = public_path($t === 'in' ? $rec->input_path : $rec->output_path);
        if (!file_exists($path)) {
            abort(404, 'Arquivo não encontrado.');
        }

        $name = $t === 'in' ? $rec->input_filename : $rec->output_filename;

        return response()->download($path, $name, [
            'Content-Type' => 'text/plain; charset=Windows-1252',
            'Cache-Control' => 'no-store, no-cache, must-revalidate, max-age=0',
        ]);
    }
}
