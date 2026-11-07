# 0005 - Nomes de tabelas e colunas no PostgreSQL
Data: 2026-11-06 · Status: Aceita

## Contexto
O enunciado define nomes com letras maiúsculas: tabela `Livro`, colunas `CodL`, `AnoPublicacao`,
`Livro_CodL`, `Assunto_codAs` etc. O PostgreSQL converte para minúsculas todo identificador escrito sem
aspas: `CREATE TABLE Livro (CodL INT)` cria a tabela `livro` com a coluna `codl`. Para preservar as
maiúsculas, o nome precisa estar entre aspas duplas, e aí as aspas passam a ser obrigatórias em
**todo** SQL que use esse nome (`SELECT "CodL" FROM "Livro"`).

## Decisão
Usar identificadores **sem aspas, em minúsculas**, e escrevê-los já em minúsculas no mapeamento do Doctrine.

| Modelo do enunciado | No banco e no mapeamento | Propriedade PHP |
|---|---|---|
| `Livro.CodL` | `livro.codl` | `Livro::$codL` |
| `Livro.AnoPublicacao` | `livro.anopublicacao` | `Livro::$anoPublicacao` |
| `Autor.CodAu` | `autor.codau` | `Autor::$codAu` |
| `Assunto.codAs` | `assunto.codas` | `Assunto::$codAs` |
| `Livro_Autor.Livro_CodL` / `Autor_CodAu` | `livro_autor.livro_codl` / `autor_codau` | (tabela associativa) |
| `Livro_Assunto.Livro_CodL` / `Assunto_codAs` | `livro_assunto.livro_codl` / `assunto_codas` | (tabela associativa) |

- Os nomes são iguais aos do modelo, ignorando maiúsculas e minúsculas.
- O mapeamento declara tudo explicitamente (`#[ORM\Table(name: 'livro')]`, `#[ORM\Column(name: 'codl')]`,
  `#[ORM\JoinTable(name: 'livro_autor')]`). Assim não dependemos da estratégia de nomes do Doctrine, que
  converteria `anoPublicacao` em `ano_publicacao`.
- Escrever em minúsculas no mapeamento faz o nome no código ser exatamente o nome que existe no banco.
  Não há conversão implícita para lembrar.
- As propriedades PHP mantêm a grafia do modelo em camelCase (`codL`, `anoPublicacao`), para o código
  continuar legível e reconhecível em relação ao enunciado.

### Ambiguidade no nome da chave do livro: `CodI` ou `CodL`
No diagrama do enunciado, a chave do livro pode ser lida como `CodI` (i maiúsculo) ou `Codl` (L minúsculo).
Na fonte usada, `I` maiúsculo e `l` minúsculo são desenhados de forma idêntica, então o diagrama sozinho não
decide. Adotamos **`CodL`** (L de Livro): segue o padrão das outras chaves, em que o sufixo abrevia a
entidade (`CodAu` de Autor, `CodAs` de Assunto). Isso vale em todo lugar: coluna `livro.codl`, FKs
`livro_autor.livro_codl` e `livro_assunto.livro_codl`, propriedade `Livro::$codL`, método `getCodL()`.

A primeira versão da migration usava `codi`. A troca foi feita antes do primeiro commit do modelo: a
migration foi regenerada (o Doctrine também muda o nome dos índices e FKs, que são derivados das colunas), o
banco de desenvolvimento foi recriado e o de teste também.

Verificado: depois da migration, `doctrine:schema:validate` diz que o banco está sincronizado e
`doctrine:migrations:diff` responde "No changes detected".

## Alternativas consideradas
- **Nomes entre aspas no mapeamento** (`` `Livro` `` / `"CodL"`), preservando as maiúsculas no banco — fica
  idêntico ao enunciado, mas obriga aspas em todo SQL manual, na VIEW do relatório e nos scripts de
  `database/`. Esquecer uma aspa gera "relation livro does not exist", um erro difícil de entender para quem
  mantém o sistema.
- **Nomes com maiúsculas no mapeamento, sem aspas** — o banco teria minúsculas e o código maiúsculas.
  Funciona, mas o código mostraria um nome que não existe no banco.
- **snake_case** (`ano_publicacao`, `cod_l`) — convenção comum no PostgreSQL, mas se afasta dos nomes do
  enunciado, que pede para segui-los.

## Consequências
- SQL manual, VIEW e scripts usam os nomes em minúsculas e sem aspas: `SELECT codl, titulo FROM livro`.
- Quem lê o banco vê `anopublicacao` e não `AnoPublicacao`. A tabela acima registra a correspondência.
- Se o avaliador confirmar que o nome era `CodI`, a troca é uma migration de `RENAME COLUMN` mais o
  mapeamento. Nenhuma regra de negócio depende do nome.
- Além das tabelas do modelo, o banco tem `doctrine_migration_versions`, a tabela em que o Doctrine Migrations
  registra quais migrations já rodaram. Ela é infraestrutura e não faz parte do modelo: não existe quando o
  banco é criado pelos scripts de `database/`.
