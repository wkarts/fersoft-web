# Análise técnica crítica — Reforma Tributária (NFe x NFC-e)

Data da análise: 2026-04-21.

## 1) Escopo e método

Esta análise rastreia o fluxo de emissão fiscal e aplicação da Reforma Tributária comparando:

- Referência: `NFService` (NFe, modelo 55).
- Alvo 1: `NFCeService` (NFC-e, modelo 65).
- Alvo 2: Fluxos de chamada de NFC-e (`NFCeController` e `AppFiscal/NfceAppController`).
- Propagação de parâmetros de matriz para filial (`ConfigNota` x `Filial`).

## 2) Baseline (NFe) — implementação de referência

### 2.1 Pontos fortes e completos no `NFService`

1. **Anexo estruturado da RT por item** com fallback de métodos (`tagIBSCBS`, `tagImpostoIBSCBS`, `tagIBS`) e log de degradação.  
2. **Normalização da RT com alíquotas fixas centralizadas** via `ReformaTributariaService::applyAliquotasFixas()`.  
3. **Pós-processamento do XML** para garantir grupos `IBSCBS/IS` em itens e `IBSCBSTot/ISTot` no total mesmo quando a lib não gerar automaticamente.  
4. **Persistência defensiva de totais** (PIS/COFINS + IBS/CBS/IS) no documento.  
5. **Controle de exibição em `infCpl`** por flags (`exibir_deolho_imposto_inf_cpl`, `exibir_piscofins_inf_cpl`, `exibir_ibscbs_inf_cpl`).  
6. **Tratamento matriz/filial de IE do emitente** com fallback para IE da matriz quando filial não possuir valor válido.  
7. **Reserva de numeração com lock transacional** (evita colisão em concorrência).

## 3) Comparativo crítico com `NFCeService`

## 3.1 O que está equivalente (aderência boa)

- Estrutura geral de RT por item e total no XML (`tryAttachReformaItemTag`, `finalizeReformaTributariaXml`) está alinhada ao baseline.
- Persistência defensiva dos totais RT/PIS/COFINS também existe.
- Montagem de observações adicionais (`infCpl`) com os três blocos (Olho no Imposto, PIS/COFINS, RT IBS/CBS/IS) existe.

## 3.2 Divergências relevantes (funcionais)

### D1) Normalização de alíquotas RT diferente da NFe (ALTO)

- **NFe (referência)** sobrescreve alíquotas do item com alíquotas fixas centralizadas no `ReformaTributariaService`.
- **NFC-e** usa prioritariamente os valores que já vierem no item (`aliq_ibs_uf`, `aliq_ibs_mun`, `aliq_cbs`) e só calcula valores derivados quando necessário.

**Impacto:** pode gerar XML/total RT divergente entre NFe e NFC-e para o mesmo item/empresa quando dados de item vierem incompletos/inconsistentes.

### D2) Herança de parâmetros de exibição (matriz → filial) incompleta (ALTO)

- Flags `exibir_*_inf_cpl` foram adicionadas em `config_notas` (matriz), **não em `filials`**.
- Nos serviços (`NFService` e `NFCeService`), quando existe `filial_id`, o objeto `$config` passa a ser `Filial`.
- Ao ler `exibir_deolho_imposto_inf_cpl` usa fallback `?? 1`; ao ler `exibir_piscofins_inf_cpl` e `exibir_ibscbs_inf_cpl` usa fallback `?? 0`.

**Efeito prático em filial:**
- Olho no Imposto tende a ficar **sempre habilitado** (default 1).
- PIS/COFINS e RT em `infCpl` tendem a ficar **sempre desabilitados** (default 0).
- Ou seja, a parametrização da matriz **não é herdada de forma consistente** para filial.

### D3) Fluxo App Fiscal NFC-e não injeta `is_filial` no serviço (ALTO)

- `NFCeController` (web) injeta `is_filial` corretamente na construção do `NFCeService`.
- `AppFiscal/NfceAppController` não envia `is_filial`, mesmo quando a venda é de filial.
- O `NFCeService` usa `is_filial` no construtor para escolher certificado (matriz x filial).

**Impacto:** em emissão via App Fiscal, há risco de usar certificado da matriz com dados de emitente de filial, gerando rejeição/assinatura inválida/inconsistência operacional.

### D4) IE do emitente na filial: NFe possui fallback, NFC-e não (MÉDIO/ALTO)

- NFe possui `resolveEmitenteIE()` com fallback para IE da matriz em emissão de filial.
- NFC-e usa `preg_replace` direto em `$config->ie` da filial, sem fallback.

**Impacto:** filial sem IE válida pode quebrar emissão na NFC-e enquanto NFe segue com fallback controlado.

### D5) Numeração NFC-e sem lock transacional (MÉDIO)

- NFe reserva número com transação + `lockForUpdate` (matriz e filial).
- NFC-e usa `last + 1` em memória e atualização posterior (controller), sem reserva transacional no serviço.

**Impacto:** risco de colisão de número/série em concorrência (PDV com múltiplos caixas/processos).

## 4) Matriz x filial — diagnóstico solicitado

## 4.1 Parâmetros criados para matriz: propagam para filial?

### Resultado: **parcial/inconsistente**

- Estrutura de filial não contém os campos novos de `infCpl` da RT (somente `config_notas` recebeu esses campos).
- Como a emissão de filial troca `$config` para instância de `Filial`, os flags deixam de refletir o cadastro da matriz.
- Não existe camada explícita de herança (`resolveFlag(matriz, filial)`) para esses parâmetros.

## 4.2 Comportamento por empresa e por filial

- A decisão macro de aplicar RT (`shouldApply`) é por `empresa_id` e considera flags/tabelas da empresa, ambiente/regime.
- Porém a **apresentação/observabilidade em XML infCpl** na filial está desalinhada por falta de herança de parâmetros do emitente matriz.

## 5) Olho no Imposto / IBS / CBS — status

- **Olho no Imposto**: implementado em ambos serviços com cálculo IBPT e `vTotTrib`; em filial tende a aparecer por default (1) quando configuração específica não existe na filial.
- **IBS/CBS/IS estruturado no XML**: implementado nos dois serviços com reforço no pós-processamento.
- **Resumo textual RT em `infCpl`**: depende de flag; para filial está suscetível a desativação indevida por ausência de herança.

## 6) Conclusão executiva

A NFC-e está **funcionalmente próxima** da NFe na estrutura de RT (tags, totais, persistência), mas **não equivalente** em pontos críticos de governança e consistência:

1. Divergência de cálculo/normalização de alíquotas RT entre NFe e NFC-e.
2. Quebra de herança matriz→filial para flags de exibição (especialmente IBS/CBS/PIS-COFINS em `infCpl`).
3. Fluxo App Fiscal sem `is_filial` no construtor do serviço (potencial uso de certificado incorreto).
4. Falta de fallback de IE para filial na NFC-e.
5. Numeração NFC-e sem reserva transacional robusta.

## 7) Ajustes necessários para consistência plena

1. **Unificar normalização RT da NFC-e com a NFe** (reuso explícito de `applyAliquotasFixas` + regra única de cálculo).
2. **Implementar herança de parâmetros de exibição** (`exibir_*_inf_cpl`) quando `config` for filial:
   - prioridade: filial (se campo existir e preenchido) > matriz > default.
3. **Corrigir App Fiscal NFC-e** para enviar `is_filial` ao construir `NFCeService`.
4. **Portar fallback de IE do emitente para NFC-e** (mesma lógica de `resolveEmitenteIE` da NFe).
5. **Adicionar reserva transacional de numeração NFC-e** com lock por matriz/filial antes da montagem final do XML.

## 8) Risco operacional se não ajustar

- Inconsistência fiscal entre documentos 55 e 65 da mesma operação.
- Divergência de conteúdo DANFE/XML entre matriz e filiais.
- Rejeições intermitentes por certificado/IE em filial (principalmente no App Fiscal).
- Colisão de numeração em cenários concorrentes de PDV.

