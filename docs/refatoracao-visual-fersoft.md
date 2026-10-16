# FERSOFT — decisão técnica e inventário visual

## Decisão de tecnologia

A aplicação anexada usa Laravel 11, PHP 8.2, Blade e Vite 4. O Vite publica apenas o ponto de entrada pequeno de `resources/css/app.css` e `resources/js/app.js`; a interface operacional carrega componentes legados de `public/css` e `public/js`. Não há uma SPA Vue existente.

A decisão é manter Blade/Laravel e organizar o shell em partials compartilhados. Uma migração para Vue 3 exigiria reimplementar ou integrar novamente 1.119 templates, as rotas server-side e os eventos dos componentes usados pelas telas. Isso elevaria o risco de alterar IDs, nomes de campos, submissões, sessões, permissões e integrações sem ajudar a reproduzir uma referência visual HTML. Não há incompatibilidade concreta que justifique a migração.

## Inventário da cópia de `develop`

- 1.119 arquivos Blade em `resources/views`.
- 668 views de módulos usam `default.layout`; duas views selecionam o layout refinado de forma condicional. Esse layout comum atende os fluxos autenticados de estoque, vendas, compras, fiscal, financeiro, pessoal, administração e demais grupos operacionais.
- `routes/web.php` contém 4.458 linhas e 2.660 chamadas de definição de rota. `routes/api.php` contém 537 linhas. As rotas e seus contratos não foram alterados.
- Os menus lateral e superior têm 41 e 12 grupos/submenus, respectivamente, com 58 e 36 links estáticos detectáveis e condições de visibilidade nos templates. O menu lateral contém 154 verificações condicionais relacionadas a visibilidade/permissões; o superior contém 140. A autorização do servidor permanece a fonte de verdade.
- 917 formulários em 594 templates; 1.056 tabelas em 524 templates; 6.561 ocorrências relacionadas a modais em 182 templates. Os números de modal contam classes/atributos encontrados por busca textual, não diálogos únicos.
- Quatro layouts de PDV preservam ações, campos, IDs, processamento fiscal e integrações. Select2, KTDatatable, AJAX, scripts de ambiente, WhatsApp, sessão e seleção de empresa permanecem no fluxo existente.
- A autenticação da aplicação usa os dados da sessão `user_logged`; o layout e os menus consomem esses dados. Os fluxos de login e as views de impressão seguem seus layouts próprios.

## Implementação visual

- `APP_UI_THEME=refined` passa a ser o padrão, com o preset claro `fer-light` e a tela `APP_UI_THEME=classic` disponível como rollback visual explícito.
- O shell usa partials para sidebar e cabeçalho. O conteúdo dos menus e do cabeçalho foi movido mantendo os links, condições, IDs, ações e integrações existentes.
- A base visual usa os tokens da proposta aprovada: canvas `#F5F7F7`, sidebar e superfícies brancas, acento verde `#1F9866`, borda `#DCE7E7` e tipografia Poppins local. O estilo global cobre cartões, formulários, validação, tabelas, paginação, alertas e modais sem trocar os handlers dos plugins.
- As views internas herdadas de `default.layout` recebem a mesma camada visual. Relatórios HTML recebem a paleta em `media="screen"`; a apresentação de impressão/PDF e o recibo térmico de 80 mm permanecem fora dessa camada.
- O atalho de suporte criado no rodapé da sidebar foi removido. A integração Chatwoot original (`layouts.support`) continua incluída e com seu launcher original visível.
- A navegação móvel continua em drawer acessível. Os testes de navegador cobrem 1920×1032, 1366×768, 768×1024 e 390×844, incluindo foco, submenu, modal, Select2, grid e ausência de overflow horizontal.

## Limites da referência

A proposta HTML representa o shell autenticado e o dashboard do ERP. As lojas públicas, telas de entrega/e-commerce e comprovantes térmicos têm layouts independentes e não foram convertidos no shell administrativo. Seus fluxos, identidade por empresa e formato de impressão continuam preservados. O novo sistema visual é aplicado às telas internas e aos relatórios visualizados na tela.

Nenhuma API, tabela de negócio ou regra fiscal/financeira foi alterada nesta refatoração.
