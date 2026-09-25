<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\BairroDeliveryLoja;
use App\Models\ClienteDelivery;
use App\Models\DeliveryConfig;
use App\Models\EnderecoDelivery;

class EnderecoDeliveryController extends Controller
{
    private function empresaId()
    {
        return session('empresa_id');
    }

    private function clienteLogado()
    {
        $clienteLog = session('cliente_log');
        if (!$clienteLog || empty($clienteLog['id']) || !$this->empresaId()) {
            return null;
        }

        return ClienteDelivery::where('id', $clienteLog['id'])
            ->where('empresa_id', $this->empresaId())
            ->first();
    }

    public function save(Request $request)
    {
        $cliente = $this->clienteLogado();
        if (!$cliente) {
            return response()->json(['erro' => 'Cliente não autenticado para esta empresa.'], 401);
        }

        $data = $request->input('data', []);
        $bairroInformado = trim((string)($data['bairro'] ?? ''));
        $bairroId = 0;
        $bairroNome = $bairroInformado;

        // Compatibilidade com o formato histórico "id:123" e com o nome enviado pelas telas atuais.
        if (preg_match('/^id:(\d+)$/', $bairroInformado, $matches)) {
            $bairroId = (int)$matches[1];
        } elseif (!empty($data['bairro_id'])) {
            $bairroId = (int)$data['bairro_id'];
        }

        $bairroLoja = null;
        if ($bairroId > 0) {
            $bairroLoja = BairroDeliveryLoja::where('id', $bairroId)
                ->where('empresa_id', $this->empresaId())
                ->first();
        } elseif ($bairroInformado !== '') {
            $bairroLoja = BairroDeliveryLoja::where('empresa_id', $this->empresaId())
                ->where('nome', 'like', '%' . $bairroInformado . '%')
                ->first();
        }

        if ($bairroLoja) {
            $bairroId = $bairroLoja->id;
            $bairroNome = $bairroLoja->nome;
        }

        $config = DeliveryConfig::where('empresa_id', $this->empresaId())->first();
        $cidadeId = $config ? (int)$config->cidade_id : 0;

        if ($cidadeId <= 0) {
            return response()->json(['erro' => 'Cidade do Delivery não configurada para esta empresa.'], 422);
        }

        try {
            $result = EnderecoDelivery::create([
                'cliente_id' => $cliente->id,
                'cidade_id' => $cidadeId,
                'tipo' => $data['tipo'] ?? 'casa',
                'principal' => isset($data['principal']) ? (bool)$data['principal'] : true,
                'padrao' => isset($data['padrao']) ? (bool)$data['padrao'] : false,
                'cep' => $data['cep'] ?? ($config->cep ?? ''),
                'rua' => $data['rua'] ?? '',
                'numero' => $data['numero'] ?? '',
                'bairro' => $bairroNome,
                'bairro_id' => $bairroId,
                'referencia' => $data['referencia'] ?? '',
                'latitude' => !empty($data['latitude']) ? substr((string)$data['latitude'], 0, 10) : '',
                'longitude' => !empty($data['longitude']) ? substr((string)$data['longitude'], 0, 10) : '',
            ]);

            return response()->json($result, 200);
        } catch (\Throwable $e) {
            \Log::error('Erro ao salvar endereço Delivery', [
                'empresa_id' => $this->empresaId(),
                'cliente_id' => $cliente->id,
                'erro' => $e->getMessage(),
            ]);
            return response()->json(['erro' => 'Não foi possível salvar o endereço.'], 500);
        }
    }

    public function get(Request $request)
    {
        $cliente = $this->clienteLogado();
        if (!$cliente) {
            return response()->json(null, 401);
        }

        $endereco = EnderecoDelivery::where('id', $request->route('endereco_id') ?? $request->endereco_id)
            ->where('cliente_id', $cliente->id)
            ->first();

        return $endereco
            ? response()->json($endereco, 200)
            : response()->json(null, 404);
    }

    public function getValorBairro(Request $request)
    {
        $cliente = $this->clienteLogado();
        if (!$cliente) {
            return response()->json(0.00, 401);
        }

        $endereco = EnderecoDelivery::where('id', $request->route('endereco_id') ?? $request->endereco_id)
            ->where('cliente_id', $cliente->id)
            ->first();

        if (!$endereco || (int)$endereco->bairro_id <= 0) {
            return response()->json(0.00, 200);
        }

        $bairro = BairroDeliveryLoja::where('id', $endereco->bairro_id)
            ->where('empresa_id', $this->empresaId())
            ->first();

        return response()->json($bairro ? (float)$bairro->valor_entrega : 0.00, 200);
    }
}
