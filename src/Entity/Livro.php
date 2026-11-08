<?php

namespace App\Entity;

use App\Repository\LivroRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: LivroRepository::class)]
#[ORM\Table(name: 'livro')]
class Livro
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(name: 'codl', type: Types::INTEGER)]
    private ?int $codL = null;

    #[ORM\Column(name: 'titulo', type: Types::STRING, length: 40)]
    #[Assert\NotBlank(message: 'Informe o título.', normalizer: 'trim')]
    #[Assert\Length(max: 40, maxMessage: 'O título deve ter no máximo {{ limit }} caracteres.')]
    private ?string $titulo = null;

    #[ORM\Column(name: 'editora', type: Types::STRING, length: 40)]
    #[Assert\NotBlank(message: 'Informe a editora.', normalizer: 'trim')]
    #[Assert\Length(max: 40, maxMessage: 'A editora deve ter no máximo {{ limit }} caracteres.')]
    private ?string $editora = null;

    #[ORM\Column(name: 'edicao', type: Types::INTEGER)]
    #[Assert\NotNull(message: 'Informe a edição.')]
    #[Assert\Positive(message: 'A edição deve ser maior que zero.')]
    private ?int $edicao = null;

    #[ORM\Column(name: 'anopublicacao', type: Types::STRING, length: 4)]
    #[Assert\NotBlank(message: 'Informe o ano de publicação.')]
    #[Assert\Regex(pattern: '/^\d{4}$/', message: 'O ano de publicação deve ter 4 dígitos, como 1999.')]
    private ?string $anoPublicacao = null;

    #[ORM\Column(name: 'valor', type: Types::DECIMAL, precision: 10, scale: 2)]
    #[Assert\NotBlank(message: 'Informe o valor.')]
    #[Assert\Regex(
        pattern: '/^\d{1,8}(\.\d{1,2})?$/',
        message: 'O valor deve estar entre 0,00 e 99.999.999,99, com até 2 casas decimais.',
    )]
    private ?string $valor = null;

    /**
     * @var Collection<int, Autor>
     */
    #[ORM\ManyToMany(targetEntity: Autor::class, inversedBy: 'livros')]
    #[ORM\JoinTable(name: 'livro_autor')]
    #[ORM\JoinColumn(name: 'livro_codl', referencedColumnName: 'codl', onDelete: 'CASCADE')]
    #[ORM\InverseJoinColumn(name: 'autor_codau', referencedColumnName: 'codau')]
    #[Assert\Count(min: 1, minMessage: 'Informe ao menos um autor.')]
    private Collection $autores;

    /**
     * @var Collection<int, Assunto>
     */
    #[ORM\ManyToMany(targetEntity: Assunto::class, inversedBy: 'livros')]
    #[ORM\JoinTable(name: 'livro_assunto')]
    #[ORM\JoinColumn(name: 'livro_codl', referencedColumnName: 'codl', onDelete: 'CASCADE')]
    #[ORM\InverseJoinColumn(name: 'assunto_codas', referencedColumnName: 'codas')]
    private Collection $assuntos;

    public function __construct()
    {
        $this->autores = new ArrayCollection();
        $this->assuntos = new ArrayCollection();
    }

    public function getCodL(): ?int
    {
        return $this->codL;
    }

    public function getTitulo(): ?string
    {
        return $this->titulo;
    }

    public function setTitulo(?string $titulo): static
    {
        $this->titulo = $titulo;

        return $this;
    }

    public function getEditora(): ?string
    {
        return $this->editora;
    }

    public function setEditora(?string $editora): static
    {
        $this->editora = $editora;

        return $this;
    }

    public function getEdicao(): ?int
    {
        return $this->edicao;
    }

    public function setEdicao(?int $edicao): static
    {
        $this->edicao = $edicao;

        return $this;
    }

    public function getAnoPublicacao(): ?string
    {
        return $this->anoPublicacao;
    }

    public function setAnoPublicacao(?string $anoPublicacao): static
    {
        $this->anoPublicacao = $anoPublicacao;

        return $this;
    }

    public function getValor(): ?string
    {
        return $this->valor;
    }

    public function setValor(?string $valor): static
    {
        $this->valor = $valor;

        return $this;
    }

    /**
     * @return Collection<int, Autor>
     */
    public function getAutores(): Collection
    {
        return $this->autores;
    }

    public function addAutor(Autor $autor): static
    {
        if (!$this->autores->contains($autor)) {
            $this->autores->add($autor);
            $autor->getLivros()->add($this);
        }

        return $this;
    }

    public function removeAutor(Autor $autor): static
    {
        if ($this->autores->removeElement($autor)) {
            $autor->getLivros()->removeElement($this);
        }

        return $this;
    }

    /**
     * @return Collection<int, Assunto>
     */
    public function getAssuntos(): Collection
    {
        return $this->assuntos;
    }

    public function addAssunto(Assunto $assunto): static
    {
        if (!$this->assuntos->contains($assunto)) {
            $this->assuntos->add($assunto);
            $assunto->getLivros()->add($this);
        }

        return $this;
    }

    public function removeAssunto(Assunto $assunto): static
    {
        if ($this->assuntos->removeElement($assunto)) {
            $assunto->getLivros()->removeElement($this);
        }

        return $this;
    }

    public function __toString(): string
    {
        return (string) $this->titulo;
    }
}
