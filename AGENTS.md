# Contrato da versão instalada

Ao alterar código, dependências, atualizações ou workflows, preserve o contrato de
`docs/releases/installed-version.md`. Ele vale também para alterações feitas por
agentes de IA.

- Versão e commit exibidos devem identificar o mesmo código instalado. Nunca
  associe o HEAD novo a uma versão antiga do banco, Composer, ENV ou release notes.
- Use `InstalledReleaseIdentity` e `AppVersionService` como fonte comum. Não
  reintroduza resolvedores próprios em menus, providers ou controllers.
- Tags/refs do Updater só identificam uma instalação Git quando apontam exatamente
  para HEAD; manifestos só são aceitos se seu SHA corresponder ao HEAD. Não use a
  maior tag global, uma tag ancestral ou consulta remota como versão instalada.
- ZIP estável deve incluir `release-build-info.json`, com versão, tag e SHA
  conferidos antes da publicação. Não fixe a versão atual no código-fonte.
- Preserve commits, notas e datas do histórico ao reconciliar a versão instalada.
- Execute os dois testes PHP de identidade e `tests/release` antes de propor uma
  alteração nessa lógica. Não remova, pule ou enfraqueça o workflow
  `release-identity.yml` nem sua dependência no workflow de release para fazer
  uma alteração passar. Atualize os testes quando o contrato evoluir.
- `main` publica o canal estável; `develop` mantém a nova interface e seu canal
  beta. Uma correção de identidade não autoriza promover a interface de develop.
