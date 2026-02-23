# Check-up ERP Estoque + Pesagem + Monitor Realtime

## Escopo mapeado (pontos de alteração de estoque/faturamento)

### Núcleo de estoque atual (legado)
- `app/Helpers/StockMove.php`
  - `pluStock(...)`: incrementa saldo em `estoques`.
  - `downStock(...)`: reduz saldo em `estoques`.
  - Limitação: sem ledger histórico, sem idempotência, sem contexto ERP/PESAGEM.

### Entradas (compras)
- `app/Http/Controllers/CompraManualController.php`
  - Criação/edição/exclusão de itens de compra altera estoque via `StockMove`.

### Saídas (vendas)
- `app/Http/Controllers/VendaController.php`
  - Baixa de estoque em fechamento de venda e fluxos relacionados.
- `app/Http/Controllers/VendaCaixaController.php`
  - Baixa/estorno de itens no PDV.
- `app/Http/Controllers/AppFiscal/VendaController.php`
  - Baixas em fluxos fiscais.

### Devoluções / estornos / ajustes
- `app/Http/Controllers/DevolucaoController.php`
  - Ajuste de estoque conforme devolução/cancelamento.
- `app/Http/Controllers/StockController.php`
  - Apontamentos manuais (incremento/redução), zeragem e ajustes.

### Pesagem (sucata)
- `app/Http/Controllers/PesagemController.php`
  - Conclusão de pesagem e geração de compra/venda a partir de tickets.
  - Fluxo atual gera documentos oficiais, mas sem contexto de estoque operacional separado.

## Monitor de pesagem atual
- `app/Events/MovimentoRealtime.php`
  - Evento broadcast `movimento.realtime` em canais privados por empresa/filial.
- `app/Services/MonitorPesagemService.php`
  - Monta payload consolidado da pesagem (tickets, pesos, parceiro, valores).
- `app/Http/Controllers/MonitorPesagemController.php`
  - Endpoint/view com feed e analíticos agregados por query de `Pesagem`.
- `resources/views/monitor/pesagens.blade.php`
  - UI com cards gerais, feed e tabelas analíticas.

## Problemas detectados
1. Estoque sem ledger transacional (apenas saldo), dificultando auditoria e reversão.
2. Sem idempotência global por origem de evento (retry pode duplicar movimentação).
3. Pesagem e ERP compartilhando lógica sem separação explícita de contexto.
4. Monitor depende de recomputação por query de pesagens; risco de custo alto e drift visual.
5. Falta snapshot específico de estoque por contexto (PESAGEM vs ERP) para recuperar estado.

## Arquitetura recomendada (incremental)
1. **Ledger/Kardex** (`stock_movements`) com contexto (`ERP`, `PESAGEM`), tipo (`entrada`, `saida`), origem e `idempotency_key`.
2. **Agregado diário** (`stock_daily_aggregates`) por produto/contexto para leitura rápida do monitor.
3. **StockService central**:
   - `mover(...)` idempotente/transacional.
   - `transferirEntreContextos(...)` para PESAGEM -> ERP sem duplicidade.
4. **Realtime robusto**:
   - Broadcast de delta via `MovimentoRealtime` (`type=stock.delta`).
   - Endpoint snapshot para o dia (`/monitor/pesagens/snapshot`).
5. **Reversão por estorno**:
   - Sempre novo movimento inverso (não apagar histórico).

## Etapas de rollout
1. Ativar migrations + modelos + serviço (feature flag desligada).
2. Integrar pesagem ao ledger em paralelo (`STOCK_LEDGER_ENABLED=true`).
3. Ativar transferência automática PESAGEM->ERP (`STOCK_LEDGER_TRANSFER_PESAGEM_TO_ERP=true`) de forma gradual por filial.
4. Trocar monitor para leitura híbrida (delta + snapshot agregado).
5. Expandir integração para compras/vendas/PDV/devoluções no mesmo serviço.

## Rollback
- Desligar flags:
  - `STOCK_LEDGER_ENABLED=false`
  - `STOCK_LEDGER_TRANSFER_PESAGEM_TO_ERP=false`
- Monitor continua funcional com dados legados, pois feed de pesagem permanece.

## Patch incremental multi-tenant (emitente/ConfigNota)
- A regra de conversão PESAGEM -> ERP deixou de depender de `.env` e passou a aceitar configuração por emitente (`config_notas.usa_produto_referenciado_pesagem`).
- Default da flag é `false`, preservando comportamento legado (produto pesado também no ERP).

### Cenário A (legado / padrão)
- Flag desativada (`false`):
  - PESAGEM usa produto pesado.
  - ERP usa produto pesado.

### Cenário B (opcional por emitente)
- Flag ativada (`true`):
  - PESAGEM continua usando produto pesado.
  - ERP tenta usar `produto_referenciado_id` do produto pesado.
  - Se não houver referenciado, aplica fallback silencioso para produto pesado (sem bloqueio do fluxo).

### Auditoria e rastreabilidade
- `stock_movements.usuario_id` passa a registrar o usuário de conversão quando disponível.
- Metadata da ponte inclui:
  - `pesagem_id`, `ticket_ids`, `produto_origem_id`, `produto_destino_id`, `regra_aplicada`, `fallback_referenciado`, `ponte_direcao`.

### Idempotência e quantidades
- Quantidade sempre positiva e direção definida por `tipo` (`entrada`/`saida`).
- A ponte passou a tratar entradas e saídas separadamente (sem saldo assinado), evitando perda de movimentações.
- `idempotency_key` da transferência inclui produto origem/destino e regra aplicada, mantendo consistência em retries.

