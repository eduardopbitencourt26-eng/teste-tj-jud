# 0012 - Arquitetura em camadas: Controller, Service e Repository
Data: 2026-11-07 · Status: Aceita

## Contexto
Cada CRUD precisa receber requisições, validar formulários, gravar, excluir e mostrar mensagens. O código
gerado pelo `make:crud` faz tudo no controller, chamando o `EntityManager` direto. Na Fase 4, a exclusão de
autor e de assunto ganha regra de negócio: bloquear se houver livros e traduzir erros de banco em mensagens.
Essa regra precisa de um lugar que não seja o controller.

## Decisão
Três camadas, cada uma com uma responsabilidade:

| Camada | Classe | Responsabilidade | Não faz |
|---|---|---|---|
| Controller | `AssuntoController` | HTTP: rota, formulário, CSRF, flash, redirect, template | Não acessa o banco |
| Service | `AssuntoService` | Casos de uso: listar, salvar, excluir; regras de negócio | Não conhece Request nem Response |
| Repository | `AssuntoRepository` | Consultas | Não grava |

- **Controller fino:** ele recebe o Service por injeção no construtor (autowiring) e só traduz entre HTTP e
  os casos de uso. Cadastro e edição compartilham um método privado, porque o fluxo é o mesmo.
- **Service:** é o único lugar que chama `persist()`, `remove()` e `flush()`. A regra "não excluir assunto
  com livros" (Fase 4) entra em `excluir()`, e qualquer outro chamador, como um comando de console, ganha a
  regra junto.
- **Repository:** herda as consultas prontas do Doctrine (`findBy`) e recebe as específicas quando surgirem
  (por exemplo, a listagem de livros com JOIN, na Fase 3).
- **Validação fica na entidade** (ADR 0010). O formulário a aplica no `isValid()`, e o Service recebe um
  objeto já validado.
- **Exclusão sempre por POST**, com um token CSRF por registro (`excluir_assunto_{codAs}`). O token de um
  registro não exclui outro. Token inválido gera uma flash de erro, e não um redirect silencioso.
- **Rotas em português**, por atributo, com prefixo na classe: `/assuntos`, `/assuntos/novo`,
  `/assuntos/{codAs}/editar`, `/assuntos/{codAs}/excluir` (nomes `assunto_listar`, `assunto_novo` etc.).
- **Partials reutilizáveis:** `_flash.html.twig`, `_form_actions.html.twig` e `_excluir.html.twig` servem
  os três CRUDs.

## Alternativas consideradas
- **Controller com EntityManager (como o maker gera)** — menos arquivos, mas a regra de exclusão ficaria
  presa ao controller, misturada com HTTP, e não seria reaproveitada fora dele.
- **Regra no Repository** — o repository passaria a gravar e a decidir, misturando consulta com caso de uso.
- **Camadas extras (DTO, interfaces para cada Service, CQRS)** — úteis em sistemas grandes. Aqui seriam
  cerimônia sem ganho: o formulário já trabalha direto na entidade e a validação já está nela.

## Consequências
- Autor e Livro seguem o mesmo molde, então os três CRUDs ficam previsíveis para quem lê.
- Na Fase 4, as exceções de domínio nascem no Service e o controller só as transforma em flash.
- O Service é `final` e não tem interface. Se um teste precisar substituí-lo, a interface entra nesse momento.
