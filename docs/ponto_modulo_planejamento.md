# Módulo de Controle de Ponto - Planejamento Incremental

## Baseline de versão
- Versão mínima de referência: **v5.0.1**.
- A contabilização de evolução do módulo de ponto deve considerar **v5.0.1** como marco inicial.

## Inventário reaproveitado na base atual
- Model central de colaborador: `Funcionario`.
- Vínculo multiempresa via `empresa_id` em rotas/controllers e tabelas novas do módulo.
- Entidades já existentes no módulo de ponto: `PontoRelogio`, `PontoAfdArquivo`, `PontoAfdRegistro`, `PontoMarcacao`, `PontoJornada`, `PontoEscala`, `PontoTurno`, `PontoOcorrencia`, `PontoAjuste`, `PontoAjusteAprovacao`, `PontoBancoHora`, `PontoFechamento`, `PontoDispositivo`.
- Serviços já existentes: parser/importação AFD, tratamento de jornada, fechamento e banco de horas.

## Tabelas e models novos desta etapa
- `ponto_importacao_logs` → `PontoImportacaoLog` (auditoria importação AFD).
- Sem novas tabelas nesta etapa adicional.

## Campos incrementais de `funcionarios` já previstos
- `matricula`
- `pis`
- `data_admissao`
- `data_demissao`
- `jornada_padrao_id`
- `escala_padrao_id`
- `gestor_id`
- `centro_custo_id`
- `ativo_ponto_mobile`
- `codigo_relogio`
- `observacao_ponto`

## Permissões do módulo ponto (alvo)
- `ponto_view`
- `ponto_create`
- `ponto_edit`
- `ponto_delete`
- `ponto_importar_afd`
- `ponto_tratar_jornada`
- `ponto_ajustar`
- `ponto_aprovar_ajuste`
- `ponto_fechar_competencia`
- `ponto_reabrir_competencia`
- `ponto_relatorios`
- `ponto_mobile`

## Rotas já mapeadas
- Web: grupo `/ponto` com relógios, importação AFD, marcações, jornadas, ajustes, fechamentos, banco de horas e relatórios.
- API: prefixo `ponto-mobile` para marcações remotas.

## Próxima etapa aplicada
- Comando operacional para reprocessamento de período:
  - `ponto:reprocessar-periodo`
- Comando operacional para validação de inconsistências:
  - `ponto:validar-inconsistencias`
- Comando operacional para recalcular banco de horas:
  - `ponto:recalcular-banco-horas`
- Comando operacional para fechamento de competência:
  - `ponto:fechar-competencia`

Esses comandos reforçam o fluxo incremental entre FASE 3 (tratamento) e FASE 4 (auditoria/fechamento), sem alterar arquitetura da base.
