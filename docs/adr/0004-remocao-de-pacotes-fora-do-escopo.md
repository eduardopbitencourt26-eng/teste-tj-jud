# 0004 - Remoção de pacotes do webapp fora do escopo
Data: 2026-11-06 · Status: Aceita

## Contexto
O `composer require webapp` instala um conjunto genérico de pacotes para aplicações web. Alguns não têm
uso no teste e trazem efeitos colaterais:

- **Messenger com transporte Doctrine** (`symfony/messenger` + `symfony/doctrine-messenger`): cria a tabela
  `messenger_messages`, que entraria na migration da Fase 1. O enunciado exige que o banco siga
  integralmente o modelo de dados, só com as tabelas `Livro`, `Autor`, `Assunto`, `Livro_Autor` e
  `Livro_Assunto`.
- **Mailer** (`symfony/mailer`): sobe um container extra (Mailpit) sem nenhum e-mail para enviar.
- **Notifier** (`symfony/notifier`): sem notificações no escopo.

## Decisão
Remover `symfony/messenger`, `symfony/doctrine-messenger`, `symfony/mailer` e `symfony/notifier` com
`composer remove`. O Flex desfez as receitas: removeu `mailer.yaml`, `notifier.yaml`, as variáveis
`MESSENGER_TRANSPORT_DSN` e `MAILER_DSN` do `.env` e o serviço `mailer` do `compose.override.yaml`.

O `config/packages/messenger.yaml` foi apagado manualmente, porque pertence à receita do
`symfony/webapp-pack` (que o registra no `symfony.lock`), e o Flex não o remove junto com o Messenger.

Ficam:
- **`symfony/security-bundle`**: base para proteção CSRF dos formulários e da exclusão, e para autenticação,
  se for necessária.
- **`symfony/ux-turbo`** e **`symfony/stimulus-bundle`**: ficaram até a etapa de front-end, quando saíram junto com a troca de AssetMapper
  por Webpack Encore, para tratar todo o front-end numa decisão só (ADR 0015).

## Alternativas consideradas
- **Manter os pacotes e só não usá-los** — mais simples agora, mas a migration da Fase 1 traria uma tabela
  fora do modelo, e o ambiente teria um container sem função.
- **Manter o Messenger com transporte `sync://` ou `in-memory://`** — evitaria a tabela, mas manteria um
  componente sem uso.

## Consequências
- O banco gerado pelas migrations contém só as tabelas do enunciado.
- O ambiente de desenvolvimento passa a ter dois containers: `php` e `database`.
- Se algum dia o projeto precisar enviar e-mail ou processar filas, os pacotes voltam com
  `composer require mailer` / `composer require messenger`, e as receitas reconfiguram tudo.
- O `symfony.lock` ainda lista `config/packages/messenger.yaml` na entrada do `symfony/webapp-pack`. É só um
  registro histórico da receita, sem efeito na aplicação.
