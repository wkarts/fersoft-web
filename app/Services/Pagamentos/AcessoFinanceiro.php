<?php

namespace App\Services\Pagamentos;

use Argws\ElectronicPayments\Asaas\Reports\AsaasReportCatalog;
use Illuminate\Support\Facades\DB;

final class AcessoFinanceiro
{
    public static function opcoes(): array
    {
        $opcoes = [];
        foreach (AsaasReportCatalog::publicDefinitions() as $relatorio) {
            $opcoes[$relatorio['id']] = $relatorio['title'];
        }
        return $opcoes + ['exportar' => 'Baixar relatórios permitidos em CSV', 'importar' => 'Importar extrato na conciliação bancária',
            'sincronizar' => 'Conciliar manualmente uma cobrança', 'cancelar' => 'Cancelar uma cobrança', 'estornar' => 'Solicitar um estorno'];
    }

    public static function doUsuario(Estabelecimento $site): array
    {
        $json = DB::table('pagamento_acessos')->whereIn('provedor_id', $site->provedores()->where('provedor', 'asaas')->select('id'))
            ->where('usuario_id', $site->usuario->id)->value('recursos');
        return array_values(array_intersect(json_decode($json ?? '[]', true), array_keys(self::opcoes())));
    }

    public static function permiteCsv(string $relatorio, array $acessos): bool
    {
        // O arquivo de extrato do provedor contém o saldo após cada lançamento.
        return in_array('exportar', $acessos, true) && in_array($relatorio, $acessos, true)
            && ($relatorio !== 'statement' || in_array('balance', $acessos, true));
    }
}
