<?php

namespace App\Services\Pagamentos;

use App\Models\{ConfigNota, ContaEmpresa, Filial, PagamentoConexao, PagamentoProvedor, Usuario};
use Argws\ElectronicPayments\Asaas\AsaasProvider;
use Argws\ElectronicPayments\Environment;
use Illuminate\Support\Facades\Schema;

final readonly class Estabelecimento
{
    public function __construct(public int $empresa, public int $local, public Usuario $usuario) {}

    /** O seletor do cabeçalho grava -1 para a matriz; a integração usa zero. */
    public static function localDoTopo(Usuario $usuario): int
    {
        $local = (int) $usuario->local_padrao;
        return $local === -1 ? 0 : $local;
    }

    public static function atual(bool $administrador = false): self
    {
        $empresa = (int) session('user_logged.empresa');
        $usuario = Usuario::where('empresa_id', $empresa)->findOrFail(session('user_logged.id'));
        $admin = (bool) $usuario->adm || (bool) session('user_logged.super');
        $permissoes = json_decode($usuario->permissao ?? '[]', true) ?: [];
        abort_unless($admin || (!$administrador && in_array('/pagamentos-eletronicos', $permissoes, true)), 403);
        $local = self::localDoTopo($usuario);
        $locais = json_decode($usuario->locais ?? '[]', true) ?: [-1];
        abort_unless(in_array($local ?: -1, array_map('intval', $locais), true), 403);
        if ($local > 0) {
            abort_unless(Filial::where('empresa_id', $empresa)->where('status', 1)->whereKey($local)->exists(), 403);
        }
        return new self($empresa, $local, $usuario);
    }

    public static function fiscal(int $empresa, int $local): Environment
    {
        $ambiente = $local
            ? Filial::where('empresa_id', $empresa)->where('status', 1)->whereKey($local)->value('ambiente')
            : ConfigNota::where('empresa_id', $empresa)->value('ambiente');
        return (string) $ambiente === '1' ? Environment::Production : Environment::Sandbox;
    }

    /** Estabelecimentos que o administrador pode parametrizar na central da empresa. */
    public static function locaisNaCentral(Usuario $usuario): array
    {
        $super = session('user_logged.super');
        $permissoes = json_decode($usuario->permissao ?? '[]', true) ?: [];
        if (!$super) {
            abort_unless($usuario->adm && in_array('/configuracao-pagamentos', $permissoes, true), 403);
            $permissoesEmpresa = json_decode($usuario->empresa?->permissao ?? '[]', true) ?: $permissoes;
            abort_unless(\App\Models\Empresa::validaLink('/configuracao-pagamentos', $permissoesEmpresa), 403);
        }

        $ids = array_map('intval', json_decode($usuario->locais ?? '[]', true) ?: [-1]);
        $locais = [];
        if (in_array(-1, $ids, true) && ConfigNota::where('empresa_id', $usuario->empresa_id)->exists()) {
            $locais[0] = 'Matriz';
        }
        $filiais = Filial::withoutDeleted()->where('empresa_id', $usuario->empresa_id)->where('status', 1)
            ->whereIn('id', $ids)->orderBy('nome_fantasia')->get(['id', 'nome_fantasia', 'razao_social', 'descricao']);
        foreach ($filiais as $filial) {
            $nome = $filial->nome_fantasia ?: ($filial->descricao ?: $filial->razao_social);
            $locais[$filial->id] = 'Filial #'.$filial->id.($nome ? ' · '.$nome : '');
        }
        return $locais;
    }

    public static function paraParametros(int $local): ?self
    {
        $empresa = (int) session('user_logged.empresa');
        $usuario = Usuario::where('empresa_id', $empresa)->find(session('user_logged.id'));
        if (!$usuario || (!$usuario->adm && !session('user_logged.super'))) {
            return null;
        }
        $permissoes = json_decode($usuario->permissao ?? '[]', true) ?: [];
        $locais = json_decode($usuario->locais ?? '[]', true) ?: [-1];
        if (!session('user_logged.super') && !in_array($local ? '/filial' : '/configNF', $permissoes, true)) {
            return null;
        }
        if (!in_array($local ?: -1, array_map('intval', $locais), true)) {
            return null;
        }
        $existe = $local
            ? Filial::withoutDeleted()->where('empresa_id', $empresa)->whereKey($local)->exists()
            : ConfigNota::where('empresa_id', $empresa)->exists();
        return $existe ? new self($empresa, $local, $usuario) : null;
    }

    public function contasBancarias()
    {
        return ContaEmpresa::withoutGlobalScopes()->withoutDeleted()->where('empresa_id', $this->empresa)
            ->where('filial_id', $this->local ?: null)->where('status', true);
    }

    public function provedores()
    {
        return PagamentoProvedor::where('empresa_id', $this->empresa)->where('local', $this->local);
    }

    public function conexoes(string $provedor = 'asaas')
    {
        return PagamentoConexao::where('empresa_id', $this->empresa)->where('local', $this->local)->where('provedor', $provedor);
    }

    /** A integração só pode ser usada depois de atualizar configuração, vendor e banco. */
    public static function integracaoInstalada(): bool
    {
        $provedores = config('pagamentos_eletronicos.provedores');
        return is_array($provedores) && $provedores !== []
            && enum_exists(Environment::class)
            && method_exists(AsaasProvider::class, 'webhookWithTokenHash')
            && Schema::hasColumn('pagamento_cobrancas', 'parcelamento_externo');
    }

    public static function disponivel(): ?PagamentoConexao
    {
        $atributos = request()->attributes;
        if (!$atributos->has('pagamentos.conexao')) {
            $c = null;
            if (self::integracaoInstalada()) {
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
