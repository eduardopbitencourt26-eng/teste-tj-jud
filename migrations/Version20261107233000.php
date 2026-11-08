<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Cria a view do relatório de livros por autor. 
 */
final class Version20261107233000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Cria a view vw_livros_por_autor, usada pelo relatório';
    }

    public function up(Schema $schema): void
    {
        $this->addSql(<<<'SQL'
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
            GROUP BY au.codau, l.codl
            SQL);
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DROP VIEW vw_livros_por_autor');
    }
}
