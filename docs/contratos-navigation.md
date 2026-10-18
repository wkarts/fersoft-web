# Navegação de Contratos sem colisão com PDFs

A listagem canônica passa a ser `/contratos/list`, usando o nome existente
`contratos.index`. A rota legada `/contratos` permanece registrada.

## Motivo

Os fluxos históricos geram PDFs na pasta `public/contratos`. Em instalações
nas quais essa pasta existe, o servidor pode interceptar `/contratos/` como
diretório e devolver 403 antes de executar o Laravel. A listagem `/contratos/list`
já usa o mesmo controller e funciona nessas instalações.

Esta correção é distribuída pelo código da aplicação: o menu, os filtros,
Limpar, Voltar, Cancelar e os retornos de salvar/excluir usam a listagem canônica.
Não é necessário alterar Nginx, Apache, arquivos PDF, migrations ou workflows.

## Compatibilidade e autorização

- A chave de permissão continua `/contratos`. Não há migração de perfis.
- O menu separa `rota` (permissão/estado ativo) de `url` (destino opcional).
  Os dois layouts continuam usando `rota` quando `url` não estiver definido.
- O prefixo das ações permanece `/contratos`: `/new`, `/edit`, `/save`,
  `/update`, `/delete`, detalhes e medições mantêm seus endereços.
- Os middlewares de empresa, acesso, contrato/plano e armazenamento são mantidos.
- A geração, assinatura, impressão, localização e download dos PDFs não mudam.
- O acesso direto a um favorito antigo `/contratos/` ainda pode receber 403 do
  servidor. Atualize esse favorito para `/contratos/list`. Esta alteração
  corrige a navegação gerada pela aplicação; não modifica a configuração do servidor.

## Verificação

Teste automatizado: `vendor/bin/phpunit tests/Unit/ContratosListNavigationTest.php`.
Ele cobre roteamento, permissão do menu, destinos dos dois layouts, retornos e
o middleware real com usuário autorizado, negado, visitante e superusuário.
Os cenários de acesso usam SQLite em memória isolada. Filtros são verificados
como parâmetros preservados; não se trata de teste dos resultados SQL da tela.

Validação manual após atualizar uma instalação de teste:

1. Mantenha a pasta `public/contratos` e seus PDFs no lugar.
2. Abra Contratos pelos menus lateral e superior; a URL deve ser `/contratos/list`.
3. Filtre por cliente, filial/matriz e status, depois use Limpar.
4. Confira Voltar em detalhes e no formulário de cadastro/edição.
5. Salve e exclua somente registros descartáveis de teste; o retorno deve ser a listagem.
6. Confira o redirecionamento para login sem sessão e a recusa para um usuário
   sem a permissão `/contratos`, inclusive com filtros na URL canônica.
7. Confira impressão/download de PDF pelos fluxos existentes.

## Implantação e rollback

Distribua os arquivos pelo fluxo habitual de atualização da aplicação. Se a
instalação usa cache de rotas/views, regenere-o pelo procedimento existente.
O updater da dependência já executa `optimize:clear`, `route:cache` e `view:cache`;
o fallback configurado para nomes de rota duplicados executa `route:clear`.
Confirme o sucesso dessa etapa no log da atualização antes de validar a navegação.
Não há migration, seed, variável de ambiente ou dependência nova.

Para reverter, volte o commit pelo fluxo habitual e regenere os mesmos caches.
O rollback restaura os antigos links sujeitos ao 403; os documentos permanecem intactos.
