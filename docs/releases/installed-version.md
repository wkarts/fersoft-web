# Identidade da versão instalada

O número exibido pelo ERP deve pertencer ao mesmo código que seu commit exibido.
Por exemplo, instalar `v5.0.237` no commit B sobre uma instalação `5.0.236` no
commit A deve mostrar `5.0.237` e B, mantendo A no histórico de `5.0.236`.

`InstalledReleaseIdentity` resolve a identidade local, sem consultar o GitHub:

1. Ref privada do Laravel Updater apontando exatamente para HEAD.
2. Manifesto do pacote cujo SHA corresponda ao HEAD, quando houver Git.
3. Tag local que aponte exatamente para HEAD.

Não se usa a maior tag global nem a tag ancestral mais próxima. Em uma instalação
Git sem evidência válida, o serviço informa identidade não verificada em vez de
combinar uma versão antiga com um commit novo. Instalações legadas sem Git nem
manifesto preservam o fallback declarado, sem atribuir um commit não comprovado.

## Pacote estável

O workflow `release.yml` gera `release-build-info.json` antes de compactar o código:

```json
{
  "schema_version": 1,
  "version": "5.0.237",
  "tag": "v5.0.237",
  "git_commit_sha": "35856404d648c5085b1fc8716dc34865bf5471c7",
  "release_channel": "stable"
}
```

Os valores acima são apenas um exemplo. A versão real vem do cálculo da release
e o SHA vem do checkout. O manifesto gerado pertence ao ZIP; não deve ser salvo
no repositório com uma versão fixa. A validação confere o manifesto dentro do ZIP,
o checkout e, quando presente, o destino da tag. O pacote funciona sem `.git` e
sem depender de um deploy SSH posterior para corrigir o número exibido.

`AppVersionService` reconcilia o registro atual sem regravar notas, commits ou datas
de versões anteriores. O provider e as telas consomem o resultado desse serviço.

## Validação obrigatória no fluxo

```bash
./vendor/bin/phpunit tests/Unit/InstalledReleaseIdentityTest.php tests/Unit/AppVersionServiceIdentityTest.php
python3 -m unittest discover -s tests/release -p 'test_*.py' -v
```

O workflow `Release - Identidade instalada` executa esses cenários nas PRs para
`main`, nos pushes de `main` e como dependência da release. Falhas impedem o fluxo
de publicação de prosseguir. Os testes incluem dados antigos, tags alheias,
rollback e pacote sem Git. Não são uma garantia contra a remoção deliberada das
próprias proteções: o GitHub deve exigir o check `Contrato da versao instalada`
nas regras de proteção de `main` para também impedir merges que o ignorem.

Para homologar, instale um pacote aprovado sobre uma versão anterior e compare
menu, controle interno de versões e Manager do Updater. Confirme número e SHA.
Volte à tag anterior e confirme que o serviço reconhece o rollback e mantém o
histórico. A aparência de `main` e os workflows beta de `develop` são independentes
deste contrato.
