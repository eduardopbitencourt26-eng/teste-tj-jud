# 0020 - Contador de caracteres como Web Component com Shadow DOM
Data: 2026-11-14 · Status: Aceita

## Contexto
Os opcionais do enunciado são TypeScript e Web Component. Os campos de texto têm limite (40 caracteres em
título, editora e nome; 20 na descrição do assunto), e o `maxlength` corta a digitação sem avisar quanto falta.
O formulário precisa continuar funcionando sem JavaScript (ADR 0015).

## Decisão
**Um Custom Element `<limite-caracteres for="id-do-campo" max="40">`, em TypeScript, com Shadow DOM.**
Ele mostra "N/40" abaixo do campo e muda de cor a partir de 80% do limite e no limite.

- **Shadow DOM porque o componente tem estilo próprio.** As regras dele (`.perto`, `.limite`, `:host`) ficam
  dentro da raiz de sombra: não vazam para a página e as do Bootstrap não entram. Os nomes de classe não
  precisam de prefixo, e um tema novo do Bootstrap não muda o componente sem querer.
- As cores vêm das variáveis CSS do Bootstrap (`--bs-danger-text-emphasis`...), com valor padrão. Variáveis CSS
  atravessam o Shadow DOM por herança: é o canal de tema que o componente aceita de fora, de propósito.
- O componente entra no `help` do campo (`help_html`). Assim fica logo abaixo do input, e o input recebe
  `aria-describedby` apontando para ele.
- O limite vem de `attr.maxlength` do Form, que repete o `Assert\Length` da entidade (ADR 0010).
- O TypeScript é compilado pelo Encore (`enableTypeScriptLoader`, `tsconfig.json` com `strict`).
- Sem JavaScript, o navegador ignora a tag desconhecida: o `maxlength` e a validação no servidor continuam.

## Alternativas consideradas
- **Sem Shadow DOM, com classes do Bootstrap (`form-text`, `text-danger`)** — menos código, mas o componente
  dependeria do CSS da página e não seria um componente isolado.
- **Stimulus** — removido no ADR 0004. Para um comportamento, voltaria um pacote inteiro.
- **Customized built-in (`is="..."` num `<input>`)** — o Safari não implementa.

## Consequências
- O limite fica em três lugares: entidade, Form e, por consequência, o componente. O componente lê do Form,
  então só a entidade e o Form precisam mudar juntos.
- O contador conta unidades UTF-16 (`value.length`), igual ao `maxlength` do navegador. Um emoji conta 2; o
  `Assert\Length` conta 1. Só afeta casos raros, e o servidor continua sendo a regra.
