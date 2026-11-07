# 0003 - Variáveis de ambiente e arquivos .env
Data: 2026-11-06 · Status: Aceita

## Contexto
Em projetos Laravel, a regra é nunca versionar o `.env`: ele guarda as credenciais reais da máquina
(inclusive a `APP_KEY`), e o repositório leva só um `.env.example`, que cada pessoa copia.
O Symfony usa uma convenção diferente para os mesmos arquivos, e é preciso decidir qual seguir.

## Decisão
Seguir a **convenção do Symfony**, como faz a documentação oficial e como esperam as receitas do Flex.

O Symfony carrega os arquivos em camadas, e o último vence:

| Ordem | Arquivo | Versionado? | Conteúdo |
|---|---|---|---|
| 1 | `.env` | sim | valores padrão de todas as variáveis que a aplicação usa, sem segredos reais |
| 2 | `.env.local` | **não** | ajustes da máquina local |
| 3 | `.env.$APP_ENV` (`.env.dev`, `.env.test`) | sim | padrões de um ambiente específico |
| 4 | `.env.$APP_ENV.local` | **não** | ajustes locais de um ambiente |
| — | variáveis de ambiente reais (Docker, servidor) | — | vencem todos os arquivos |

O critério é o mesmo do Laravel (segredo nunca vai para o repositório), só muda o nome do arquivo:
no Symfony, quem guarda o que é da máquina é o `.env.local`, ignorado pelo `.gitignore`.

Regras do projeto:
- O `.env` tem `APP_SECRET=` vazio. O `APP_SECRET` do `.env.dev` e do `.env.test` foi gerado pela receita
  e só vale para esses ambientes.
- Nenhuma senha, token ou chave real entra em arquivo versionado. Valores reais vão para `.env.local` ou
  variáveis de ambiente; em produção também é possível usar o cofre do Symfony (`bin/console secrets:set`).
- No Docker, o `DATABASE_URL` do serviço `php` é definido no `compose.yaml`. Por ser variável de ambiente
  real, ele vence o valor do `.env` dentro do container.
- Em produção, `compose.prod.yaml` exige `APP_SECRET` vindo do ambiente.

## Alternativas consideradas
- **`.env.dist` versionado e `.env` ignorado** — o Symfony carrega `.env.dist` nativamente quando não
  existe `.env`, então funcionaria sem cópia manual. Não escolhido porque foge da convenção atual da
  documentação e as receitas do Flex continuariam escrevendo no `.env`, exigindo ajuste manual a cada pacote.
- **`.env.example` versionado e `.env` ignorado (estilo Laravel)** — familiar, mas o Symfony não suporta
  isso nativamente: sem `cp .env.example .env` a aplicação não sobe, o que adiciona um passo para o avaliador.

## Consequências
- O avaliador clona e roda `docker compose up`, sem copiar nenhum arquivo de configuração.
- Quem vem do Laravel pode estranhar o `.env` no repositório. Este ADR existe para explicar por quê.
- Exige disciplina: qualquer valor sensível adicionado no futuro vai para `.env.local` ou variável de
  ambiente, nunca para `.env`, `.env.dev` ou `.env.test`.
