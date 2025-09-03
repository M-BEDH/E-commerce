<?php

namespace App\Form;

use App\Entity\User;
use App\Entity\Review;
use App\Entity\Product;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;

class ReviewType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('comment')
            ->add('note', IntegerType::class, [
            'attr' => [
                'min' => 0,
                'max' => 5,
            ],
            'label' => 'Note (0 à 5)',
        ])
            // ->add('user', EntityType::class, [
            //     'class' => User::class,
            //     'choice_label' => 'firstName',
            // ])
            ->add('product', EntityType::class, [
                'class' => Product::class,
                'choice_label' => 'name',
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Review::class,
        ]);
    }
}
