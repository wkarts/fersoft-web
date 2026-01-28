<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Veiculo;
use App\Services\SaveFilesDB;
use App\Models\Funcionario;

class VeiculoController extends Controller
{
    protected $empresa_id = null;
    protected $saveFilesDB;
    protected $directoryPath;
    protected $filial_id = null;
    protected $usuario_id = null;

    public function __construct(SaveFilesDB $saveFilesDB)
    {
        $this->saveFilesDB = $saveFilesDB;
        $this->directoryPath = public_path('imgs_veiculos/');

        $this->middleware(function ($request, $next) {
            $this->empresa_id = $request->empresa_id;
            $value = session('user_logged');
            if (!$value) {
                return redirect("/login");
            }

            $this->usuario_id = $value['id'] ?? null;

            $filial = $request->get('filial_id', $value['local_padrao'] ?? null);
            if (isset($filial) && (int) $filial <= 0) {
                $filial = null;
            }
            $this->filial_id = $filial !== null ? (int) $filial : null;

            return $next($request);
        });
    }

    public function index()
    {
        $veiculos = $this->applyFilialFilter(
            Veiculo::with('motorista')->where('empresa_id', $this->empresa_id)
        )->get();

        return view('veiculos/list')
            ->with('veiculos', $veiculos)
            ->with('title', 'Veiculos');
    }

    public function new()
    {
        $tipos = Veiculo::tipos();
        $tiposRodado = Veiculo::tiposRodado();
        $tiposCarroceria = Veiculo::tiposCarroceria();
        $tiposProprietario = Veiculo::tiposProprietario();
        $ufs = Veiculo::cUF();
        $statusManutencao = ['Em dia', 'Atenção', 'Em manutenção'];

        $motoristas = Funcionario::where('empresa_id', $this->empresa_id)
            ->where('status_motorista', 'Ativo')
            ->get();

        return view('veiculos/register')
            ->with('tipos', $tipos)
            ->with('tiposRodado', $tiposRodado)
            ->with('tiposCarroceria', $tiposCarroceria)
            ->with('tiposProprietario', $tiposProprietario)
            ->with('ufs', $ufs)
            ->with('statusManutencao', $statusManutencao)
            ->with('motoristas', $motoristas)
            ->with('veiculoJs', true)
            ->with('title', 'Cadastrar Veiculo');
    }

    public function save(Request $request)
    {
        $this->_validate($request);

        try {
            $veiculo = new Veiculo();
            $request->merge([
                'empresa_id' => $this->empresa_id,
                'usuario_id' => $this->usuario_id,
                'filial_id' => $this->filial_id,
            ]);

            if ((int) ($request->filial_id ?? 0) <= 0) {
                $request->merge(['filial_id' => null]);
            }

            $request->merge([
                'rntrc' => $request->rntrc ?? '',
                'taf' => $request->taf ?? '',
                'numero_registro_estadual' => $request->numero_registro_estadual ?? '',
                'renavam' => $request->renavam ?? '',
                'combustivel' => $request->combustivel ?? '',
                'status_manutencao' => $request->status_manutencao ?? 'Em dia',
            ]);

            // Salvar a imagem usando o SaveFilesDB
            if ($request->hasFile('file') && $request->file('file')->isValid()) {
                $filename = $this->saveFilesDB->SaveFile($request->file('file'), $this->directoryPath, $veiculo, 'foto_veiculo');
                $request->merge(['foto_veiculo' => $filename]);
            }

            $request->merge([
                'motorista_id' => $request->motorista_id ?? null,
            ]);

            $veiculo->fill($request->all());
            $veiculo->save();
            session()->flash("mensagem_sucesso", "Veículo cadastrado com sucesso.");
        } catch (\Exception $e) {
            __saveError($e, $this->empresa_id);
            session()->flash("mensagem_erro", "Algo deu errado: " . $e->getMessage());
        }

        return redirect('/veiculos');
    }

    public function edit($id)
    {
        $tipos = Veiculo::tipos();
        $tiposRodado = Veiculo::tiposRodado();
        $tiposCarroceria = Veiculo::tiposCarroceria();
        $tiposProprietario = Veiculo::tiposProprietario();
        $ufs = Veiculo::cUF();
        $statusManutencao = ['Em dia', 'Atenção', 'Em manutenção'];

        $resp = $this->applyFilialFilter(
            Veiculo::where('empresa_id', $this->empresa_id)->where('id', $id)
        )->first();

        $motoristas = Funcionario::where('empresa_id', $this->empresa_id)
            ->where('status_motorista', 'Ativo')
            ->get();

        if (valida_objeto($resp)) {
            return view('veiculos/register')
                ->with('veiculo', $resp)
                ->with('tipos', $tipos)
                ->with('tiposRodado', $tiposRodado)
                ->with('tiposCarroceria', $tiposCarroceria)
                ->with('tiposProprietario', $tiposProprietario)
                ->with('ufs', $ufs)
                ->with('veiculo', $resp)
                ->with('statusManutencao', $statusManutencao)
                ->with('motoristas', $motoristas)
                ->with('veiculoJs', true)
                ->with('title', 'Editar Veiculo');
        } else {
            return redirect('/403');
        }
    }

    public function update(Request $request)
    {
        $resp = $this->applyFilialFilter(
            Veiculo::where('empresa_id', $this->empresa_id)->where('id', $request->id)
        )->firstOrFail();
        $veiculo = $resp;
        $this->_validate($request);

        try {
            // Verificar se a imagem deve ser removida
            if ($request->input('remove_image') == '1') {
                $imagePath = $this->directoryPath . $resp->foto_veiculo;

                // Exclui o arquivo físico se ele existir
                if ($resp->foto_veiculo && file_exists($imagePath)) {
                    unlink($imagePath);
                    \Log::info("Imagem {$imagePath} removida com sucesso.");
                } else {
                    \Log::warning("Imagem {$imagePath} não encontrada para remoção.");
                }

                // Define o campo como nulo no banco para remoção da referência
                $resp->foto_veiculo = null;
            }

            // Atualizar a imagem usando o SaveFilesDB
            if ($request->hasFile('file') && $request->file('file')->isValid()) {
                $filename = $this->saveFilesDB->SaveFile($request->file('file'), $this->directoryPath, $resp, 'foto_veiculo');
                $request->merge(['foto_veiculo' => $filename]);
            }

            $request->merge([
                'status_manutencao' => $request->status_manutencao ?? $resp->status_manutencao ?? 'Em dia',
            ]);

            // Atualizar outros campos do veículo
            $veiculo->cor = $request->input('cor');
            $veiculo->marca = $request->input('marca');
            $veiculo->modelo = $request->input('modelo');
            $veiculo->combustivel = $request->input('combustivel');
            $veiculo->placa = $request->input('placa');
            $veiculo->tipo = $request->input('tipo');
            $veiculo->uf = $request->input('uf');
            $veiculo->rntrc = $request->input('rntrc');
            $veiculo->taf = $request->input('taf');
            $veiculo->numero_registro_estadual = $request->input('numero_registro_estadual');
            $veiculo->renavam = $request->input('renavam');
            $veiculo->tipo_carroceira = $request->input('tipo_carroceira');
            $veiculo->tipo_rodado = $request->input('tipo_rodado');
            $veiculo->tara = $request->input('tara');
            $veiculo->capacidade = $request->input('capacidade');
            $veiculo->proprietario_nome = $request->input('proprietario_nome');
            $veiculo->proprietario_ie = $request->input('proprietario_ie');
            $veiculo->proprietario_uf = $request->input('proprietario_uf');
            $veiculo->proprietario_tp = $request->input('proprietario_tp');
            $veiculo->proprietario_documento = $request->input('proprietario_documento');
            $veiculo->traccar_id = $request->input('traccar_id');

            $resp->motorista_id = $request->input('motorista_id');

            $request->merge([
                'empresa_id' => $this->empresa_id,
                'usuario_id' => $this->usuario_id,
                'filial_id' => $this->filial_id,
            ]);

            if ((int) ($request->filial_id ?? 0) <= 0) {
                $request->merge(['filial_id' => null]);
            }

            $resp->fill($request->all());
            $resp->save();
            session()->flash('mensagem_sucesso', 'Veículo editado com sucesso!');
        } catch (\Exception $e) {
            __saveError($e, $this->empresa_id);
            session()->flash("mensagem_erro", "Algo deu errado: " . $e->getMessage());
        }

        return redirect('/veiculos');
    }

    public function delete($id)
    {
        try {
            $resp = $this->applyFilialFilter(
                Veiculo::where('empresa_id', $this->empresa_id)->where('id', $id)
            )->first();
            if (valida_objeto($resp)) {
                if ($resp->foto_veiculo && file_exists($this->directoryPath . $resp->foto_veiculo)) {
                    unlink($this->directoryPath . $resp->foto_veiculo);
                }
                if ($resp->delete()) {
                    session()->flash('mensagem_sucesso', 'Registro removido!');
                } else {
                    session()->flash('mensagem_erro', 'Erro!');
                }
                return redirect('/veiculos');
            } else {
                return redirect('/403');
            }
        } catch (\Exception $e) {
            return view('errors.sql')
                ->with('title', 'Erro ao deletar veiculo')
                ->with('motivo', 'Não é possível remover veículos presentes em transportes!');
        }
    }

    private function applyFilialFilter($query)
    {
        if ($this->filial_id === null) {
            return $query;
        }

        return $query->where(function ($builder) {
            $builder->where('filial_id', $this->filial_id)
                ->orWhereNull('filial_id');
        });
    }

    private function _validate(Request $request)
    {
        $rules = [
            'placa' => 'required|max:8',
            'cor' => 'required|max:10',
            'marca' => 'required|max:20',
            'modelo' => 'required|max:20',
            'tara' => 'required|max:10',
            'rntrc' => 'max:9',
            'capacidade' => 'required|max:10',
            'combustivel' => 'required|max:20',
            'proprietario_nome' => 'required|max:40',
            'proprietario_ie' => 'required|max:13',
            'proprietario_documento' => 'required|max:20',
            'quilometragem' => 'nullable|numeric|min:0',
            'data_ultima_manutencao' => 'nullable|date',
            'quilometragem_ultima_manutencao' => 'nullable|numeric|min:0',
            'proxima_manutencao_km' => 'nullable|numeric|min:0',
            'data_proxima_revisao' => 'nullable|date',
            'status_manutencao' => 'required|in:Em dia,Atenção,Em manutenção',
            'observacoes_manutencao' => 'nullable|string',
            'motorista_id' => 'nullable|exists:funcionarios,id',
        ];

        $messages = [
            'placa.required' => 'O campo placa é obrigatório.',
            'placa.max' => '8 caracteres máximos permitidos.',
            'cor.required' => 'O campo cor é obrigatório.',
            'cor.max' => '10 caracteres máximos permitidos.',
            'marca.required' => 'O campo marca é obrigatório.',
            'marca.max' => '20 caracteres máximos permitidos.',
            'modelo.required' => 'O campo modelo é obrigatório.',
            'modelo.max' => '20 caracteres máximos permitidos.',
            'capacidade.required' => 'O campo capacidade é obrigatório.',
            'capacidade.max' => '10 caracteres máximos permitidos.',
            'tara.required' => 'O campo tara é obrigatório.',
            'tara.max' => '10 caracteres máximos permitidos.',
            'combustivel.required' => 'O campo combustível é obrigatório.',
            'combustivel.max' => '20 caracteres máximos permitidos.',
            'rntrc.required' => 'O campo RNTRC é obrigatório.',
            'rntrc.min' => '8 caracteres permitidos.',
            'rntrc.max' => '9 caracteres permitidos.',
            'proprietario_nome.required' => 'O campo Nome proprietário é obrigatório.',
            'proprietario_nome.max' => '40 caracteres máximos permitidos.',
            'proprietario_ie.required' => 'O campo I.E proprietário é obrigatório.',
            'proprietario_ie.max' => '13 caracteres máximos permitidos.',
            'proprietario_documento.required' => 'O campo CPF/CNPJ proprietário é obrigatório.',
            'proprietario_documento.max' => '20 caracteres máximos permitidos.',
            'quilometragem.numeric' => 'Informe a quilometragem utilizando apenas números.',
            'quilometragem.min' => 'A quilometragem deve ser maior ou igual a zero.',
            'quilometragem_ultima_manutencao.numeric' => 'Informe a quilometragem da última manutenção com números.',
            'quilometragem_ultima_manutencao.min' => 'A quilometragem da última manutenção deve ser maior ou igual a zero.',
            'proxima_manutencao_km.numeric' => 'Informe a quilometragem da próxima manutenção com números.',
            'proxima_manutencao_km.min' => 'A quilometragem da próxima manutenção deve ser maior ou igual a zero.',
            'data_ultima_manutencao.date' => 'Informe uma data válida para a última manutenção.',
            'data_proxima_revisao.date' => 'Informe uma data válida para a próxima revisão.',
            'status_manutencao.required' => 'Selecione o status da manutenção.',
            'status_manutencao.in' => 'O status de manutenção informado é inválido.',
            'motorista_id.exists' => 'Motorista selecionado é inválido.',
        ];

        $this->validate($request, $rules, $messages);
    }
}
