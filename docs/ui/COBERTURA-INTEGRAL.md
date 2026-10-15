# Expansão visual integral — responsabilidade do desenvolvimento, sem tarefas nas hospedagens

## Entrega contratada

O novo visual do FERSOFT WEB é entregue como **código da aplicação**, por PRs destinadas à **`develop`**, com pré-release beta contínua. O desenvolvedor implementa o novo shell, componentes, estilos, adaptadores, módulos e testes. A pessoa que utiliza ou hospeda o ERP **não precisa executar scripts de auditoria, registrar manualmente 1.695 aprovações ou preencher planilhas**.

A `main` permanece a linha estável. A promoção final `develop → main` é deliberada, mediante aprovação do redesenho, e a versão beta continua permanentemente na `develop`.

## Como o diagnóstico é realizado — AUTOMATICAMENTE

O GitHub Actions executa no desenvolvimento, sem banco nem instalação em hospedagens:

1. `scripts/auditar-tema.php` — encontra e classifica as views Blade.
2. `scripts/auditar-assets.php` — identifica arquivos CSS/JS e dependências técnicas.
3. `scripts/ui/coverage-report.php` — cruza os dois inventários e emite JSON/CSV com grupos, tipo de integração herdada, indicadores e prioridade de migração.
4. O próprio workflow `theme-audit.yml` anexa os três relatórios aos artifacts do GitHub Actions.

Os comandos são **internos ao CI**. São documentados para reprodução técnica por desenvolvedores, não para execução por clientes, superadmins da aplicação nem operadores de hospedagem. Nenhum servidor cliente precisa instalar, configurar, programar cron ou executar qualquer relatório desse tipo.

O relatório é um diagnóstico, não uma lista de assinaturas: **nenhum arquivo exige marcação manual de `homologado` ou `dispensado`**. Os testes e correções são realizados durante o desenvolvimento e vinculados às PRs reais que alteram os módulos.

## Números medidos

A auditoria executada em 15/10/2026 na PR #276 identificou:

| Elemento | Quantidade |
|---|---:|
| Views Blade | 1.115 |
| Grupos de views | 241 |
| Views que herdam diretamente o shell ERP | 669 |
| Assets CSS/JS e relacionados | 580 |
| Total de arquivos rastreados tecnicamente | 1.695 |

Essas quantidades mudam com novos arquivos. **Não representam 1.695 modificações isoladas obrigatórias:** componentes compartilhados podem modernizar várias telas sem reescrever cada Blade. No entanto, a aplicação prática desses componentes precisa ser verificada por módulo, especialmente onde há scripts e estilos próprios. Também não significa que as 669 views herdadas já estejam homologadas.

## Expansão que eu devo implementar no código

| Área | Entrega efetiva |
|---|---|
| Shell lateral e superior | Navegação operacional, permissões originais, topbar, empresa, filial, ambiente, PDV, WhatsApp, alertas, suporte e contexto de sessão preservados |
| Design system | Componentes Blade/JS próprios, fontes do access_4, tokens, claro/escuro, sidebar clara/escura, densidade, responsividade, acessibilidade |
| Componentes operacionais | Formulários, máscaras, validações, Select2, tabelas, DataTables/KTDatatable, grids AJAX, modais, wizards, notificações, calendários, uploads |
| Cadastros, estoque, compras, vendas | Redesenho de views reais com salvar, editar, filtrar e navegar funcionando como antes |
| Operações especiais | PDV, caixa, delivery, contratos/medições, fiscal, NFS-e/NF-e/NFC-e, MTR, financeiro, conciliação e relatórios |
| Superfícies específicas | E-mails, PDFs, impressão fiscal, login, portais e PWA preservados nos seus contratos, sem aplicação indiscriminada do CSS interno |
| Regressão | Testes automatizados de código, componentes e browser nos fluxos mais críticos; comparação com capturas reais sem inventar dados |

Metronic/Bootstrap 4 permanecem **onde ainda houver dependência**, até o componente correspondente ser substituído e testado. O objetivo de longo prazo é um shell realmente independente, não apenas outro CSS em cima do Metronic.

## Implantação — sem tarefa extra em cada hospedagem

No ambiente clássico, o sistema continua em `APP_UI_THEME=classic`. O novo visual é controlado pela mesma configuração já prevista:

```dotenv
APP_UI_THEME=classic   # interface atual, padrão e fallback
APP_UI_THEME=refined   # nova arquitetura visual, após distribuição homologada
```

O catálogo de preferências por usuário/empresa é opcional e independente da distribuição de versões.

O deploy usa os **mecanismos já existentes da aplicação**, incluindo as migrations da feature quando cabíveis ao ambiente. Scripts de auditoria não são parte da rotina de instalação, atualização, inicialização da aplicação nem do acesso do usuário. Ações normais de deploy Laravel, como migrations quando realmente necessárias, não devem ser confundidas com execução manual de ferramentas internas de auditoria.

A pré-release é gerada pelo GitHub a cada commit/merge na `develop`, independentemente da fase `transition` ou `standard`. Nada é empurrado automaticamente da `develop` para a `main`.

## Critério real de conclusão

A finalização depende do **código implementado, testes automatizados, homologação nos módulos reais e aprovação do redesenho**. Uma planilha completa, uma contagem de arquivos ou um CI verde por si só não provam a experiência funcional.

A fundação foi introduzida pela PR #275. A PR #276 apenas automatiza a análise técnica para orientar as próximas implementações e **não adiciona requisitos operacionais às hospedagens**. A PR #274, que eventualmente promoverá o novo visual para `main`, continua rascunho até a conclusão real.
