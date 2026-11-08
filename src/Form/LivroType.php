<?php

namespace App\Form;

use App\Entity\Assunto;
use App\Entity\Autor;
use App\Entity\Livro;
use Doctrine\ORM\EntityRepository;
use Doctrine\ORM\QueryBuilder;
use SortDirection;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\CallbackTransformer;
use Symfony\Component\Form\Exception\TransformationFailedException;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\Extension\Core\Type\MoneyType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

final class LivroType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('titulo', TextType::class, [
                'label' => 'Título',
                'attr' => ['maxlength' => 40],
            ])
            ->add('editora', TextType::class, [
                'label' => 'Editora',
                'attr' => ['maxlength' => 40],
            ])
            ->add('edicao', IntegerType::class, [
                'label' => 'Edição',
                'attr' => ['min' => 1],
                'invalid_message' => 'Informe a edição como número inteiro, como 2.',
            ])
            ->add('anoPublicacao', TextType::class, [
                'label' => 'Ano de publicação',
                'attr' => ['maxlength' => 4, 'inputmode' => 'numeric', 'placeholder' => 'AAAA'],
            ])
            // input "string": o valor vai do campo para a entidade como string ("1234.56"), sem passar por
            // float, o mesmo formato do DECIMAL e do Regex da entidade. grouping: para o separador de milhar 
            ->add('valor', MoneyType::class, [
                'label' => 'Valor',
                'currency' => 'BRL',
                'input' => 'string',
                'grouping' => true,
                'invalid_message' => 'Informe o valor no formato 1.234,56.',
            ])
            ->add('autores', EntityType::class, [
                'label' => 'Autores',
                'class' => Autor::class,
                'choice_label' => 'nome',
                'query_builder' => static fn (EntityRepository $repositorio): QueryBuilder => $repositorio
                    ->createQueryBuilder('au')
                    ->orderBy('au.nome', SortDirection::Ascending),
                'multiple' => true,
                'expanded' => true,
                'by_reference' => false,
            ])
            ->add('assuntos', EntityType::class, [
                'label' => 'Assuntos',
                'class' => Assunto::class,
                'choice_label' => 'descricao',
                'query_builder' => static fn (EntityRepository $repositorio): QueryBuilder => $repositorio
                    ->createQueryBuilder('ass')
                    ->orderBy('ass.descricao', SortDirection::Ascending),
                'multiple' => true,
                'expanded' => true,
                'by_reference' => false,
                'required' => false,
            ]);

        $builder->get('valor')->addViewTransformer(new CallbackTransformer(
            static fn (?string $valor): ?string => $valor,
            self::recusarMaisDeDuasCasas(...),
        ));
    }

    private static function recusarMaisDeDuasCasas(?string $valor): ?string
    {
        $separador = (new \NumberFormatter(\Locale::getDefault(), \NumberFormatter::DECIMAL))
            ->getSymbol(\NumberFormatter::DECIMAL_SEPARATOR_SYMBOL);
        $casas = strrchr((string) $valor, $separador);

        if (false !== $casas && preg_match('/\d{3,}/', $casas)) {
            throw new TransformationFailedException('Valor com mais de 2 casas decimais.', invalidMessage: 'O valor deve ter no máximo 2 casas decimais.');
        }

        return $valor;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Livro::class,
        ]);
    }
}
