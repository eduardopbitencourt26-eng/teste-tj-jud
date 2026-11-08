<?php

namespace App\Service;

use App\Relatorio\GrupoDoAutor;
use App\Relatorio\RelatorioLivrosPorAutor;
use App\Repository\RelatorioRepository;

final class RelatorioService
{
    public function __construct(private readonly RelatorioRepository $relatorioRepository)
    {
    }

    public function gerar(): RelatorioLivrosPorAutor
    {
        $linhasPorAutor = [];
        $valorPorLivro = [];

        foreach ($this->relatorioRepository->linhasPorAutor() as $linha) {
            $linhasPorAutor[$linha['autor_codau']][] = $linha;
            // Indexado pelo código do livro, o livro com 2 autores entra uma vez só no total geral.
            $valorPorLivro[$linha['livro_codl']] = self::centavos($linha['valor']);
        }

        $grupos = [];
        foreach ($linhasPorAutor as $linhas) {
            $subtotal = array_sum(array_map(static fn (array $linha): int => self::centavos($linha['valor']), $linhas));
            $grupos[] = new GrupoDoAutor($linhas[0]['autor_nome'], $linhas, self::reais($subtotal));
        }

        return new RelatorioLivrosPorAutor(
            $grupos,
            \count($valorPorLivro),
            self::reais(array_sum($valorPorLivro)),
            new \DateTimeImmutable(),
        );
    }

    private static function centavos(string $valor): int
    {
        [$inteiros, $decimais] = explode('.', $valor) + [1 => '0'];

        return (int) $inteiros * 100 + (int) str_pad(substr($decimais, 0, 2), 2, '0');
    }

    private static function reais(int $centavos): string
    {
        return \sprintf('%d.%02d', intdiv($centavos, 100), $centavos % 100);
    }
}
