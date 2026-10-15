# Cobertura integral e homologação do redesenho — FERSOFT WEB

> Documento de controle da expansão visual, vinculado EXCLUSIVAMENTE à \`develop\`. A PR de promoção \`develop → main\` (#274) continua como rascunho até a conclusão integral. A versão clássica segue preservada.

## Qual é o escopo, sem reduzir ou mascarar

A auditoria inicial identificou **1.112 views Blade**, **241 grupos**, **668 Blades que estendem diretamente o shell ERP** e **576 assets CSS/JS/TS/SCSS**. Estes números são o ponto de partida da auditoria (não uma contagem congelada). Os scripts \`scripts/auditar-tema.php\` e \`scripts/auditar-assets.php\` devem recalculá-los em todo novo build de inventário.

**Nenhuma herança de \`default.layout\`, classe CSS \`ui-theme-refined\`, estilização superficial nem CI verde equivale à homologação visual de uma tela.** A PR #275, já incorporada à \`develop\`, entrega somente a fundação compartilhada: presets, catálogo SUPERADMIN, seleção por empresa/usuário, shell progressivo e busca por módulos. Ela não concluiu a substituição do Metronic nem a revisão individual de todos os módulos.

Todas as views/assets inventariados devem passar por triagem expressa:
- **Homologado:** design novo funcional e aprovado no contexto real; registro de evidência (PR/teste/captura), responsável e verificação de regressão.
- **Dispensado com justificativa:** arquivo que não deve herdar o visual interno por função específica (p.ex., XML, HTML de e-mail, PDF fiscal, impressão dedicada, página pública/login independente). A dispensa precisa de responsável, justificativa e evidência. Não é exclusão silenciosa.
- **Pendente:** estado automático de todos os arquivos não revisados, mesmo quando seus componentes herdam parte do shell.

Arquivos novos entram no inventário automaticamente como **pendentes**. A meta final não é modificar arbitrariamente 1.112 arquivos: é garantir que **100% deles tenham tratamento justificado e verificado**, evitando danos a e-mails, impressões, portais e rotas públicas.

## Contrato funcional — todas as áreas

| Macroetapa | Abrangência | Resultado que comprova a entrega |
|---|---|---|
| 1. Shell e identidade | Layout lateral/superior, logo oficial, topo com empresa/ambiente/filial/PDV/WhatsApp/perfil/logoff, sessão, rolagem e suporte | Regressão visual real no desktop/mobile/PWA e todos os controles ativos |
| 2. Design system próprio | Tokens, temas claro/sidebar escura/noturno, densidades, fontes access_4, botões, cards, formulários, responsividade, focus/feedback, acessibilidade | Biblioteca reutilizável com contratos Blade/JS e testes sem exigir mudança de regras de negócio |
| 3. Componentes e adaptadores | Modal, wizard, Select2, DataTable/KTDatatable, tooltips, alerts, toasts, tabs, filtros, anexos, calendários, grids AJAX | Mesmas ações/eventos preservados; componentes migrados deixam dependências antigas quando homologados |
| 4. Cadastros e gestão | Clientes, produtos, fornecedores, usuários, permissões, empresas, perfis, serviços, estoque, inventário, compras | Salvar, editar, excluir conforme autorização; grid continua acessível sem recarregar |
| 5. Comercial e operação | Vendas, orçamento, pedidos, PDV, caixa, delivery, NFC-e offline, serviços, contratos/medições | Fluxos completos sem regressão de validação, preço, unidade, cliente ou navegação |
| 6. Financeiro/fiscal/documentos | Contas a pagar/receber, conciliação, banking, NF-e/NFC-e/NFS-e/CT-e/MDF-e/DFe, MTR, SPED, relatórios | Mesmos valores, retornos, transmissões e permissões, sem adulterar cálculos, XML ou impressões |
| 7. Superfícies independentes | Login \`access_4\`, portais, modelos de e-mail, impressões, PDF, páginas públicas, PWA | Avaliação contextual com preservação de contratos, ou exceção técnica documentada |
| 8. Homologação integral | Todos os grupos/views e assets inventariados e revisados, incluindo inclusão de telas posteriores | Nenhum pendente sem justificativa; CI + QA de navegador real; rollback \`APP_UI_THEME=classic\` |

Não inventar métricas nem gráficos do dashboard: os dados reais, ausência de série, saldos e alertas são preservados. A barra preta de janela instalada pertence ao contêiner do aplicativo/PWA e não deve ser copiada como HTML de Blade.

## Registro obrigatório por arquivo

O CI gera:
- \`tema-inventario.json\`: caminho, módulo, herança do layout, sinais de JS/CSS, formulários, tabelas, modais etc.
- \`tema-assets.json\`: caminho, tipo, tamanho, indicadores de plugins e SHA.
- \`tema-cobertura.json\` e \`tema-cobertura.csv\`: estado **de cada view e asset** e totais por grupo, sem declarar migração concluída por inferência.

Aprovações manuais entram em \`docs/ui/coverage-reviews.json\` e devem trazer \`situacao\`, \`responsavel\`, \`evidencia\`; para \`dispensado_justificado\`, trazer também \`justificativa\`. Sem esses campos, o relatório deve falhar. Não preencher aprovações falsas nem copiar evidências de outro módulo.

Na promoção FINAL do redesign, o comando \`php scripts/ui/coverage-report.php --require-complete\` pode bloquear integração quando houver arquivos sem revisão. Na fase de implantação parcial, o mesmo comando **sem** \`--require-complete\` informa lacunas sem impedir os builds beta contínuos da \`develop\`.

## Fluxo de desenvolvimento imutável

\`feature/* → PR para develop → CI + homologação → beta contínua da develop\`.

A \`main\` continua estável; atualizações na \`main\` são importadas unilateralmente para a \`develop\`. Somente depois da aprovação final: \`develop → PR #274 → main → release estável\`. A pré-release beta permanece para sempre vinculada à \`develop\` nas duas fases.

Não substituir bibliotecas Metronic/Bootstrap 4 de uma só vez: manter adaptadores para módulos ainda não convertidos, sem erros de focus, scroll, z-index, event handlers ou Ajax. Uma vez migrado e testado o módulo, eliminar sua dependência específica quando possível.

## Verificação mínima por tela

Toda tela autenticada deve ser testada com menu lateral e superior, tema claro e escuro quando aplicável, densidade confortável/compacta, ao menos desktop e mobile, usuário comum e SUPERADMIN segundo permissões. Para tela operacional: filtros, modal, Select2, DataTable, salvar/editar/Ajax, print/exportação e navegação de volta. Anexar referência de prova no registro.

A matriz é uma ferramenta de **contabilidade de cobertura**, não um gerador automático de CSS para todos os arquivos.
