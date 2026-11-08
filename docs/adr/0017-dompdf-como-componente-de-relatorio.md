# 0017 - Dompdf como componente de relatório
Data: 2026-11-07 · Status: Aceita

## Contexto
O enunciado exige um relatório gerado por um componente de relatório, com a consulta vindo de uma view do
banco. O relatório é uma listagem agrupada por autor, com subtotais e total, sem gráficos nem sub-relatórios.

## Decisão
O relatório é renderizado num **template Twig**, e o **Dompdf** converte esse HTML em PDF.
- O conteúdo (`relatorio/_conteudo.html.twig`) é o mesmo na tela (`/relatorio`) e no PDF (`/relatorio/pdf`).
  Muda só o CSS: Bootstrap na tela, CSS embutido no PDF.
- O PDF tem cabeçalho, data e hora de geração, os grupos por autor com quantidade e subtotal, o total geral e
  a nota sobre livros com mais de um autor (ADR 0018). É servido como `inline`, com nome
  `livros-por-autor-AAAA-MM-DD.pdf`.
- O `GeradorDePdf` (Service) isola o Dompdf: o controller pede "este template, este contexto" e recebe os
  bytes do PDF. Configuração:
  - fonte **DejaVu Sans**, que vem com o Dompdf e cobre os acentos;
  - **sem acesso remoto** (`isRemoteEnabled: false`): o template não carrega nada de fora, e um dado
    cadastrado não consegue fazer o Dompdf buscar URLs ou arquivos;
  - cache de métricas de fonte em `var/cache`, porque `vendor/` não é gravável na imagem de produção.
- O CSS do PDF é CSS 2.1 com tabelas (o Dompdf não suporta flexbox nem grid). Com `table-layout: fixed`, a
  primeira linha define as larguras das colunas. Por isso o nome do autor fica num título acima da tabela, e
  não numa linha com `colspan`, que o Dompdf dividiria em partes iguais.
- **Fuso horário:** `date.timezone = America/Sao_Paulo` no `php.ini` da aplicação. Com UTC, o relatório
  gerado às 22h mostrava 07/11 na tela (Twig) e 08/11 no nome do arquivo (PHP). Um fuso só, definido no PHP,
  vale para os dois.

## Alternativas consideradas
- **JasperReports / outro gerador de relatórios** — tem designer visual e recursos avançados, mas exige Java
  ou um servidor à parte. É desproporcional para uma listagem agrupada.
- **wkhtmltopdf (KnpSnappy)** — renderiza melhor CSS moderno, mas é um binário externo e não é mais mantido.
- **Chrome headless (Gotenberg, Browsershot)** — fidelidade total ao CSS, mas exige um navegador num container
  a mais.
- **TCPDF / FPDF** — o PDF é montado por código, célula a célula, e o layout deixa de ser um template.
- **mPDF** — equivalente ao Dompdf (HTML para PDF em PHP puro). O Dompdf tem integração mais comum com
  Symfony e não precisa de extensões além das já instaladas.

## Consequências
- Mudar o layout do relatório é mudar Twig e CSS, sem código PHP.
- O PDF gera em memória, na requisição. Para relatórios muito grandes (milhares de páginas), seria preciso
  gerar em segundo plano.
- O teste funcional confere que a rota responde `application/pdf` e que o conteúdo começa com `%PDF-`.
