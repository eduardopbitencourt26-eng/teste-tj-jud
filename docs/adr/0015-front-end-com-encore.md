# 0015 - Front-end com Webpack Encore, Bootstrap via SCSS e sem Stimulus/Turbo
Data: 2026-11-07 · Status: Aceita

## Contexto
O enunciado exige CSS controlando no mínimo **cor e tamanho** dos componentes, e Bootstrap é diferencial.
O symfony-docker vem com AssetMapper, Stimulus e Turbo. O AssetMapper serve arquivos sem compilação e não
compila SCSS sozinho (precisaria de outro bundle). Configurar o Bootstrap pelas variáveis Sass exige compilar.
O avaliador deve continuar precisando só de Docker (ADR 0001).

## Decisão

**Webpack Encore no lugar do AssetMapper.**
- `assets/app.js` é a única entrada. Ele importa `styles/app.scss` e o plugin de colapso do Bootstrap
  (`bootstrap/js/dist/collapse.js`), que abre e fecha o menu no celular. O Bootstrap JS completo não entra.
- Em produção, nomes com hash (`enableVersioning`) e build minificado.
- Sem Babel com polyfills do core-js (vinha na receita): o JS do projeto é um import.

**Bootstrap configurado por variáveis próprias.** `assets/styles/_variaveis.scss` define as cores e os
tamanhos do projeto em português (`$cor-primaria`, `$tamanho-fonte-base`, `$raio-borda`, `$espaco-base`...) e
os repassa às variáveis do Bootstrap (`$primary`, `$font-size-base`, `$border-radius`, `$spacer`...).
O arquivo vem **antes** do import do Bootstrap: lá toda variável é `!default` e só assume o valor padrão se
ainda não existir. Os componentes próprios (`.lista-opcoes`, `.tabela-cadastro`, `.cartao-inicio`) usam as
mesmas variáveis.
- O Bootstrap 5.3 ainda é distribuído para `@import`, que o Sass atual marca como deprecated. Os avisos estão
  silenciados no `webpack.config.js` (`quietDeps` e `silenceDeprecations: ['import']`). A migração para `@use`
  acontece com o Bootstrap 6.

**Node no Docker Compose, em duas formas:**

| Ambiente | Onde roda | Como |
|---|---|---|
| Dev | Serviço `node` no `compose.override.yaml`, com `profiles: [tools]` | Não sobe com o `up`; roda sob demanda (`docker compose run --rm node npm ...`, ou `make assets`/`make watch`) |
| Prod | Estágio `assets_builder` do `Dockerfile` | Compila e entrega só `public/build` à imagem final, que não tem Node |

- A mesma imagem `node:24.22.2-alpine` é fixada por tag + digest nos dois lugares, como a do FrankenPHP.
- `node_modules` fica num volume nomeado, fora do bind mount: mais rápido no Mac/Windows.
- `npm run watch` usa polling, porque no Docker Desktop do Windows os eventos de arquivo não chegam ao
  container (ADR 0011).

**Stimulus e Turbo removidos** (anunciado no ADR 0004). As telas são formulários e listagens
renderizados no servidor, e o único comportamento em JS é o menu colapsável.
- O CSRF dos formulários continua protegido sem JS. O modo stateless (Symfony 7.2+) confere o header
  `Origin` (ou o `Referer`), que todo navegador envia num POST. Testado: POST com `Origin` grava; sem ele, é
  recusado com 422. A configuração `check_header`, do Turbo, saiu junto.

**Os testes não dependem do build:** em `test`, `strict_mode: false` faz as tags de CSS e JS saírem vazias
quando o `public/build` não existe.

## Alternativas consideradas
- **AssetMapper + `symfonycasts/sass-bundle`** — sem Node, mas o Bootstrap SCSS dependeria de um binário Dart
  Sass baixado pelo bundle, e a pilha seria menos comum.
- **Bootstrap pronto via CDN e CSS por cima** — cor e tamanho só por sobrescrita de regras, sem as
  variáveis do Bootstrap. É frágil e duplica CSS.
- **Vite** — mais rápido, mas o Encore é a integração oficial do Symfony (Twig `encore_entry_*_tags`).
- **Node instalado na imagem do php** — mais uma linguagem na imagem de produção, só para compilar assets.
- **Serviço `node` sempre rodando `watch`** — gastaria CPU com polling o tempo todo, mesmo quando ninguém
  mexe no front-end.

## Consequências
- Mudar a identidade visual é mudar `_variaveis.scss` e recompilar.
- O CSS de produção tem o Bootstrap inteiro (cerca de 230 KiB minificado). Importar só os módulos usados
  reduziria o tamanho, ao custo de manter a lista de módulos.
- Em modo worker, o `EntrypointLookup` guarda o `entrypoints.json` em memória (o `reset()` entre requisições
  não o limpa). Ao trocar entre o build de dev e o de produção, que mudam os nomes dos arquivos, é preciso
  reiniciar o php. Os atalhos `make assets` e `make build-assets` já fazem isso.
- TypeScript e um Web Component ficam como opcionais, para o fim do projeto.
