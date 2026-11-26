<?php

namespace App\Services\Pagamentos;

use App\Models\{ConfigNota, Filial, PagamentoConexao, Usuario};
use Argws\ElectronicPayments\Environment;

final readonly class Estabelecimento
{
    public function __construct(public int $empresa, public int $local, public Usuario $usuario) {}

    public static function atual(bool $administrador = false): self
    {
        $empresa = (int) session('user_logged.empresa');
        $usuario = Usuario::where('empresa_id', $empresa)->findOrFail(session('user_logged.id'));
        $admin = (bool) $usuario->adm || (bool) session('user_logged.super');
        $permissoes = json_decode($usuario->permissao ?? '[]', true) ?: [];
        abort_unless($admin || (!$administrador && in_array('/pagamentos-eletronicos', $permissoes, true)), 403);
        $local = (int) ($usuario->local_padrao ?? -1);
        $locais = json_decode($usuario->locais ?? '[]', true) ?: [-1];
        abort_unless(in_array($local ?: -1, array_map('intval', $locais), true), 403);
        if ($local > 0) {
            abort_unless(Filial::where('empresa_id', $empresa)->where('status', 1)->whereKey($local)->exists(), 403);
        }
        return new self($empresa, max(0, $local), $usuario);
    }

    public static function fiscal(int $empresa, int $local): Environment
    {
        $ambiente = $local
            ? Filial::where('empresa_id', $empresa)->where('status', 1)->whereKey($local)->value('ambiente')
            : ConfigNota::where('empresa_id', $empresa)->value('ambiente');
        return (string) $ambiente === '1' ? Environment::Production : Environment::Sandbox;
    }

    public function conexoes()
    {
        return PagamentoConexao::where('empresa_id', $this->empresa)->where('local', $this->local);
    }

    public static function disponivel(): ?PagamentoConexao
    {
        $atributos = request()->attributes;
        if (!$atributos->has('pagamentos.conexao')) {
            $c = null;
            if (\Illuminate\Support\Facades\Schema::hasTable('pagamento_conexoes')) {
                try {
                    $site = self::atual();
                    $c = $site->conexoes()->where('selecionada', true)->first();
                    $c?->account()->authorize(self::fiscal($site->empresa, $site->local));
                } catch (\Symfony\Component\HttpKernel\Exception\HttpExceptionInterface|\Illuminate\Database\Eloquent\ModelNotFoundException|\DomainException $e) {
                    $c = null;
                }
            }
            $atributos->set('pagamentos.conexao', $c);
        }
        return $atributos->get('pagamentos.conexao');
    }

    public function ativa(): PagamentoConexao
    {
        $conexao = $this->conexoes()->where('selecionada', true)->first();
        abort_unless($conexao, 409, 'Configure e ative o Asaas para este estabelecimento.');
        $conexao->account()->authorize(self::fiscal($this->empresa, $this->local));
        return $conexao;
    }
}
