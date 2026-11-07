# 0011 - Arquivos vigiados pelo worker em desenvolvimento
Data: 2026-11-07 · Status: Aceita

## Contexto
Em dev, o FrankenPHP roda em worker mode com `--watch`: quando um arquivo vigiado muda, ele reinicia todas
as threads PHP, para que o próximo request use o código novo. O template symfony-docker configura
`FRANKENPHP_WORKER_CONFIG: watch` sem padrão, e o FrankenPHP usa o padrão default, que vigia todos os
`.php`, `.twig`, `.yaml` e `.env` do projeto, **inclusive `var/cache/`**.

Ao abrir as primeiras telas do CRUD, o log mostrou um ciclo:
1. o Twig compila um template e grava um `.php` em `var/cache/dev/twig/`;
2. o watcher detecta o arquivo e reinicia as 9 threads;
3. cada reinício recarrega o kernel, lendo o `vendor/` pelo bind mount, o que leva de 4 a 8 s no Docker
   Desktop do Windows.

Cada tela nova custava de 12 a 30 segundos, e uma chegou a dar 500 por estourar o `max_execution_time` de
30 s. Isso atrapalharia o avaliador e a demonstração.

Também foi verificado que, com o projeto num caminho do Windows (`C:\...`) montado no container, os eventos
de alteração do bind mount **não chegam** ao watcher, nem com o padrão default. Neste cenário o watch só via
o `var/cache/`, que é interno ao container.

## Decisão
Vigiar só o que exige um kernel novo:

```yaml
FRANKENPHP_WORKER_CONFIG: |
  watch ./src/**/*.php
  watch ./config/**/*.{php,yaml,yml}
  watch ./.env*
  watch ./composer.lock
```

- `var/` fica de fora: é saída do próprio Symfony, não código.
- `templates/` fica de fora: em dev, o Twig confere a data de cada template e recompila sozinho, sem reiniciar
  o worker.
- A sintaxe dos padrões foi verificada numa pasta interna ao container, onde os eventos funcionam.

Resultado medido: nenhum reinício ao navegar, e telas já visitadas em menos de 1 s (antes, de 12 a 30 s).

## Alternativas consideradas
- **Manter o padrão do template** — o custo descrito acima, sem benefício no Windows.
- **Desligar o worker mode em dev** — elimina o problema e o código novo vale em todo request, mas o dev
  passaria a funcionar diferente da produção, que roda em worker mode (ADR 0001).
- **Mover o projeto para o sistema de arquivos do WSL** (`\\wsl$\...`) — os eventos funcionam e o I/O fica
  mais rápido, mas isso é escolha de cada máquina, não algo que o repositório possa impor.

## Consequências
- Em Linux e macOS, alterar código em `src/` ou `config/` continua reiniciando o worker automaticamente.
- No Docker Desktop do Windows, com o projeto num caminho do Windows, alterações em PHP não são detectadas:
  depois de editar código, rode `docker compose restart php`. Isso já acontecia com o padrão do template, que
  só reiniciava por acaso, quando o Twig gravava no cache. O README registra isso.
- O avaliador que só sobe e usa a aplicação não é afetado. Quem edita código no Windows precisa do restart.
