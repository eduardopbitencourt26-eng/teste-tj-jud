-- Dados de exemplo, para um banco vazio criado pelos scripts 01 e 02 (ou pelas migrations).
-- Escrito à mão e carregado também por "doctrine:fixtures:load" (src/DataFixtures/CargaInicialFixtures.php).
-- Casos do relatório: livros com 2 autores, livro com vários assuntos, livro sem assunto,
-- e um autor e um assunto sem livros (podem ser excluídos e não aparecem no relatório).

SET client_encoding = 'UTF8';

INSERT INTO autor (nome) VALUES
    ('Machado de Assis'),
    ('Clarice Lispector'),
    ('Neil Gaiman'),
    ('Terry Pratchett'),
    ('Martin Fowler'),
    ('Kent Beck'),
    ('Cecília Meireles');

INSERT INTO assunto (descricao) VALUES
    ('Romance'),
    ('Clássico'),
    ('Literatura nacional'),
    ('Fantasia'),
    ('Humor'),
    ('Programação'),
    ('Poesia');

INSERT INTO livro (titulo, editora, edicao, anopublicacao, valor) VALUES
    ('Dom Casmurro', 'Penguin-Companhia', 1, '2016', 34.90),
    ('Memórias Póstumas de Brás Cubas', 'Penguin-Companhia', 1, '2014', 29.90),
    ('A Hora da Estrela', 'Rocco', 1, '2020', 39.90),
    ('Belas Maldições', 'Bertrand Brasil', 2, '2019', 54.90),
    ('Coraline', 'Intrínseca', 1, '2020', 44.90),
    ('Refatoração', 'Novatec', 2, '2020', 119.00),
    ('TDD: Desenvolvimento Guiado por Testes', 'Bookman', 1, '2010', 89.00);

INSERT INTO livro_autor (livro_codl, autor_codau)
SELECT l.codl, a.codau
FROM (VALUES
    ('Dom Casmurro', 'Machado de Assis'),
    ('Memórias Póstumas de Brás Cubas', 'Machado de Assis'),
    ('A Hora da Estrela', 'Clarice Lispector'),
    ('Belas Maldições', 'Neil Gaiman'),
    ('Belas Maldições', 'Terry Pratchett'),
    ('Coraline', 'Neil Gaiman'),
    ('Refatoração', 'Martin Fowler'),
    ('Refatoração', 'Kent Beck'),
    ('TDD: Desenvolvimento Guiado por Testes', 'Kent Beck')
) AS v (titulo, nome)
INNER JOIN livro l ON l.titulo = v.titulo
INNER JOIN autor a ON a.nome = v.nome;

INSERT INTO livro_assunto (livro_codl, assunto_codas)
SELECT l.codl, s.codas
FROM (VALUES
    ('Dom Casmurro', 'Romance'),
    ('Dom Casmurro', 'Clássico'),
    ('Dom Casmurro', 'Literatura nacional'),
    ('Memórias Póstumas de Brás Cubas', 'Romance'),
    ('Memórias Póstumas de Brás Cubas', 'Literatura nacional'),
    ('Belas Maldições', 'Fantasia'),
    ('Belas Maldições', 'Humor'),
    ('Coraline', 'Fantasia'),
    ('Refatoração', 'Programação'),
    ('TDD: Desenvolvimento Guiado por Testes', 'Programação')
) AS v (titulo, descricao)
INNER JOIN livro l ON l.titulo = v.titulo
INNER JOIN assunto s ON s.descricao = v.descricao;
