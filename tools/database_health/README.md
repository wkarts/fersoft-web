# Auditoria de saúde de dumps de banco

Ferramenta local para examinar dumps MySQL/MariaDB, encontrar tabelas e colunas com maior volume, identificar possíveis Base64/QR, estimar duplicidade de registros configurados e produzir relatórios. O modo de cópia cria um ZIP novo com redactions configuradas. Ela não abre conexão de banco, não executa SQL e não modifica o arquivo de origem.

## Requisitos e formatos

- Python 3.10 ou superior; somente biblioteca padrão.
- Entrada: `.sql`, `.sql.gz`, `.zip` com um ou mais `.sql`/`.sql.gz`.
- Adaptador implementado nesta versão: `mysql-dump` para dumps com instruções `INSERT INTO ... VALUES`.
- Linhas SQL são lidas em fluxo. O parser trata tuplas em linhas separadas ou várias tuplas por instrução; a memória usada para dados é limitada principalmente ao tamanho da maior linha SQL.

O desenho separa o adaptador do analisador e das políticas. Outros formatos (PostgreSQL, SQL Server ou leitura direta read-only) podem ser acrescentados sem mudar as regras de análise e saneamento.

## Analisar sem alterar arquivos

```bash
python tools/database_health/db_health.py analyze banco.zip \
  --config tools/database_health/fersoft-health.json \
  --report-json relatorio.json --report-md relatorio.md
```

O relatório inclui contagem de linhas lidas de `INSERT`, volume aproximado das tuplas por tabela e coluna, datas inicial/final configuradas, frequência de categorias configuradas, duplicatas para as chaves declaradas e indicadores de Base64, Data URI e código QR. Não grava amostras nem conteúdo de linhas. Fingerprints de duplicidade ficam em SQLite temporário com HMAC aleatório por execução e são removidos ao terminar.

## Criar uma cópia saneada

```bash
python tools/database_health/db_health.py clean-copy banco.zip banco-saneado.zip \
  --config tools/database_health/fersoft-health.json
```

O modo padrão somente aplica redactions declaradas no perfil. A cópia é escrita em outro caminho e o programa se recusa a sobrescrever arquivos existentes sem `--overwrite`. Para usar uma regra de exclusão declarada no JSON é necessário passar `--apply-deletes`. Sem esse sinalizador o relatório apenas conta as linhas candidatas.

A limpeza padrão troca códigos de QR de pareamento nos caminhos descritos no JSON, remove assinaturas/data URLs de imagem e Base64 reconhecível nas colunas permitidas e recodifica somente os `INSERT`s dessas tabelas. O restante do dump é copiado sem interpretação nem extração de caminhos do ZIP. Os relatórios JSON e Markdown ficam ao lado do arquivo de saída, por padrão.

## Regras de exclusão opcionais

O perfil começa sem exclusões automáticas. Uma regra precisa ter nome, tabela e todas as condições verdadeiras. Exemplo para uma política revisada por um operador:

```json
"delete_rules": [
  {
    "name": "ruido_404_antigo",
    "table": "logs",
    "where": [
      {"column": "acao", "op": "equals", "value": "exception"},
      {"column": "modelo", "op": "contains", "value": "NotFoundHttpException"}
    ]
  }
]
```

Esse exemplo apenas ilustra a sintaxe; não faz parte do perfil padrão nem estabelece retenção. Antes de usar `--apply-deletes`, valide o volume de candidatos no relatório e restaure a saída em banco isolado.

Operadores disponíveis: `equals`, `contains`, `in` e `is_null`. Várias condições na mesma regra são combinadas com AND. Regras diferentes são combinadas com OR.

## Limites e interpretação

- É uma ferramenta de dump, não de produção ao vivo. Não recebe DSN, usuário ou senha.
- O relatório mede bytes das tuplas `INSERT`; não equivale ao tamanho físico de índices, páginas, fragmentação ou arquivos do servidor.
- A detecção de Base64 é heurística. Um token longo pode ser texto legítimo; uma limpeza só o remove em colunas selecionadas e em campos explicitamente marcados, exceto assinaturas claras de imagem e caminhos de QR configurados.
- A deduplicação compara somente as colunas definidas por tabela. O resultado é candidato de duplicidade, não autorização para apagar registros.
- Regras de retenção não vêm presumidas. Defina-as por ambiente após validar obrigações legais, auditoria e necessidade de reprocessamento.
- Use um dump protegido e mantenha o original. A validação final deve ocorrer em restauração isolada antes de qualquer troca de ambiente.

## Testes

```bash
python -m unittest discover -s tests/tools -v
```
