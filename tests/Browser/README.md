# Regressões de layout do access_4

Os testes PHPUnit existentes validam rotas e renderização, mas não medem a
geometria calculada pelo navegador. Esta suíte acrescenta verificação de layout
em Chromium, Firefox e WebKit, com os arquivos Blade, CSS, JavaScript e fontes
Poppins/Montserrat reais do repositório.

O workflow `Login - Navegador e responsividade` usa somente o banco isolado
`fersoft_test`, renderiza 36 combinações (8 modelos + padrão original, 2 fontes,
com/sem conteúdo publicado) e testa 15 dimensões: 1.620 cenários nos três motores.

Verifica transbordamento horizontal no documento e nos painéis, ausência de
rolagem interna nas colunas, alcance de todos os campos e botões, mobile sem
painel decorativo, conteúdo publicado acessível em seção expansível, IDs únicos,
recuperação de senha e o contrato HTTP do OTP com respostas de teste.

As chamadas de rede são interceptadas; não há login, recuperação ou OTP contra
servidor real e nenhuma mensagem é enviada. O ensaio de OTP prova a interface e
o payload, não a validade de uma conta real. Capturas e JSON são publicados como
artefatos do workflow. Fontes e dados do banco não entram no artefato.

Não há nova dependência do runtime do ERP. Playwright e os navegadores pertencem
apenas aos testes de desenvolvimento/CI. A seleção do login continua `PAG_LOGIN`.

Para executar, use uma cópia isolada de teste com `APP_ENV=testing` e o banco
`fersoft_test` (ou SQLite `:memory:` apenas onde a suíte existente o permitir):

```sh
./vendor/bin/phpunit --filter LoginResponsiveBrowserFixturesTest
python -m pip install -r tests/Browser/requirements.txt
python -m playwright install --with-deps chromium firefox webkit
python tests/Browser/access4_responsive.py --browsers chromium firefox webkit
```

Nunca execute a preparação do banco de testes sobre uma instalação de produção.
Em telas com pouco espaço vertical, texto ampliado ou muitos comunicados, uma
rolagem vertical única é intencional: não se oculta nem recorta o conteúdo.
