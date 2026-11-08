CREATE VIEW vw_livros_por_autor AS
SELECT
    au.codau AS autor_codau,
    au.nome AS autor_nome,
    l.codl AS livro_codl,
    l.titulo,
    l.editora,
    l.edicao,
    l.anopublicacao AS ano_publicacao,
    l.valor,
    STRING_AGG(ass.descricao, ', ' ORDER BY ass.descricao) AS assuntos
FROM autor au
INNER JOIN livro_autor la ON la.autor_codau = au.codau
INNER JOIN livro l ON l.codl = la.livro_codl
LEFT JOIN livro_assunto las ON las.livro_codl = l.codl
LEFT JOIN assunto ass ON ass.codas = las.assunto_codas
GROUP BY au.codau, l.codl;
