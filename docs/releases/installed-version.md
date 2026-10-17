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

## Manifesto comum aos dois canais

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

Os valores acima são apenas um exemplo estável. A versão real vem do cálculo da release
e o SHA vem do checkout. O manifesto gerado pertence ao ZIP; não deve ser salvo
no repositório com uma versão fixa. A validação confere o manifesto dentro do ZIP,
o checkout e, quando presente, o destino da tag. O pacote funciona sem `.git` e
sem depender de um deploy SSH posterior para corrigir o número exibido.

A beta também escreve `release-build-info.json` no ZIP, com canal
`beta-continuous`, tag `ui-continuous`, base estável e número de build. Mantém os
dois arquivos legados `ui-build-info.json` para os consumidores atuais. Assim,
aplicar um ZIP beta sobre um estável, ou o inverso, substitui o mesmo marcador
canônico e não deixa a versão do canal anterior prevalecer sem Git.

`AppVersionService` reconcilia o registro atual sem regravar notas, commits ou datas
de versões anteriores. O provider e as telas consomem o resultado desse serviço.

## Validação obrigatória no fluxo

```bash
./vendor/bin/phpunit tests/Unit/InstalledReleaseIdentityTest.php tests/Unit/AppVersionServiceIdentityTest.php
python3 -m unittest discover -s tests/release -p 'test_*.py' -v
python3 tests/ui/test-main-version-identity-sync.py
```

O workflow `Release - Identidade instalada` executa esses cenários nas PRs e pushes
de `main` e `develop`, e como dependência das releases estável e beta. Falhas impedem o fluxo
de publicação de prosseguir. Os testes incluem dados antigos, tags alheias,
rollback e pacote sem Git. Não são uma garantia contra a remoção deliberada das
próprias proteções: o GitHub deve exigir os checks `Contrato da versao instalada`
e `Compatibilidade main para develop`
nas regras de proteção de `main` para também impedir merges que o ignorem.

## Sincronização main → develop

O check de compatibilidade monta um candidato local com a develop atual e a PR
da main. Executa os testes dos dois canais sem fazer push, tag ou release. O
autosync também valida a identidade combinada antes de enviar qualquer commit.

Há uma resolução transitória para o backport da correção original da PR #299:
Git vê quatro conflitos porque esses arquivos foram introduzidos separadamente
nas duas branches. O script `merge-main-version-identity.sh` tenta primeiro o
merge normal. Apenas com a base comum
`35856404d648c5085b1fc8716dc34865bf5471c7` e a correção compartilhada
`f69e545206d960f0595fd9544dce3a96d422447b` ancestral da develop, permite uma nova
fusão de conteúdo dos quatro arquivos de identidade, usando a correção
compartilhada como base funcional e preservando alterações de ambos os lados.

Não seleciona uma branch inteira nem resolve outros arquivos automaticamente.
Conflitos adicionais, uma base inesperada ou conflito restante nessa fusão
abortam a operação. Depois da primeira sincronização, os merges seguintes usam
a ancestralidade Git normal; a exceção antiga não vale para novas bases. O
provider e toda a camada visual continuam sujeitos ao merge normal.

Script e proveniência são copiados para uma pasta temporária antes do checkout
de develop, pois ainda não existem nessa branch durante a primeira importação.
O script só cria commits locais; o workflow faz push somente após os testes.

Para homologar, instale um pacote aprovado sobre uma versão anterior e compare
menu, controle interno de versões e Manager do Updater. Confirme número e SHA.
Volte à tag anterior e confirme que o serviço reconhece o rollback e mantém o
histórico. Confira também a beta após a sincronização: versão completa, base
estável, build e SHA devem continuar corretos. As duas interfaces são preservadas;
o contrato de identidade é compartilhado.
