<?php

namespace Tests\Feature;

use App\Events\MovimentoRealtime;
use App\Models\StockDailyAggregate;
use App\Services\StockService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Tests\TestCase;

class StockLedgerTest extends TestCase
{
    use RefreshDatabase;

    public function test_movimento_pesagem_idempotente(): void
    {
        $service = app(StockService::class);

        $service->mover([
            'empresa_id' => 1,
            'filial_id' => 1,
            'produto_id' => 10,
            'contexto' => 'PESAGEM',
            'tipo' => 'entrada',
            'quantidade' => 100,
            'idempotency_key' => 'k1',
            'movimentado_em' => now(),
        ]);

        $service->mover([
            'empresa_id' => 1,
            'filial_id' => 1,
            'produto_id' => 10,
            'contexto' => 'PESAGEM',
            'tipo' => 'entrada',
            'quantidade' => 100,
            'idempotency_key' => 'k1',
            'movimentado_em' => now(),
        ]);

        $this->assertDatabaseCount('stock_movements', 1);
        $this->assertDatabaseHas('stock_daily_aggregates', [
            'empresa_id' => 1,
            'produto_id' => 10,
            'contexto' => 'PESAGEM',
        ]);
    }

    public function test_transferencia_pesagem_para_erp_gera_duas_movimentacoes(): void
    {
        $service = app(StockService::class);

        $service->transferirEntreContextos([
            'empresa_id' => 1,
            'filial_id' => 1,
            'produto_id' => 20,
            'quantidade' => 50,
            'contexto_origem' => 'PESAGEM',
            'contexto_destino' => 'ERP',
            'idempotency_key' => 'k-transfer',
            'movimentado_em' => now(),
        ]);

        $this->assertDatabaseHas('stock_movements', ['contexto' => 'PESAGEM', 'tipo' => 'saida', 'produto_id' => 20]);
        $this->assertDatabaseHas('stock_movements', ['contexto' => 'ERP', 'tipo' => 'entrada', 'produto_id' => 20]);
    }

    public function test_evento_realtime_e_snapshot_agregado_conferem(): void
    {
        Event::fake([MovimentoRealtime::class]);

        $service = app(StockService::class);

        $service->mover([
            'empresa_id' => 1,
            'filial_id' => 1,
            'produto_id' => 10,
            'contexto' => 'PESAGEM',
            'tipo' => 'entrada',
            'quantidade' => 30,
            'idempotency_key' => 'k-snap-1',
            'movimentado_em' => now(),
        ]);

        $service->mover([
            'empresa_id' => 1,
            'filial_id' => 1,
            'produto_id' => 10,
            'contexto' => 'PESAGEM',
            'tipo' => 'saida',
            'quantidade' => 5,
            'idempotency_key' => 'k-snap-2',
            'movimentado_em' => now(),
        ]);

        Event::assertDispatched(MovimentoRealtime::class, 2);

        $agg = StockDailyAggregate::where('empresa_id', 1)
            ->where('produto_id', 10)
            ->where('contexto', 'PESAGEM')
            ->first();

        $this->assertNotNull($agg);
        $this->assertEquals(30.0, (float) $agg->entrada);
        $this->assertEquals(5.0, (float) $agg->saida);
        $this->assertEquals(25.0, (float) $agg->saldo);
    }
}
