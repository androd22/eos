<?php

namespace App\Form;

use App\Entity\Auction;
use App\Entity\Celebrity;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CollectionType;
use Symfony\Component\Form\Extension\Core\Type\DateTimeType;
use Symfony\Component\Form\Extension\Core\Type\FileType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\Valid;

class AuctionType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        // Ajouter le champ celebrity uniquement si on n'est pas dans le contexte d'inscription
        if (!$options['is_celebrity_registration']) {
            $builder->add('celebrity', EntityType::class, [
                'class' => Celebrity::class,
                'choice_label' => function ($celebrity) {
                    return $celebrity->getStageName() ?: $celebrity->getRealFirstName() . ' ' . $celebrity->getRealLastName();
                },
                'label' => 'Célébrité',
                'attr' => [
                    'class' => 'mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-violet-500 focus:ring focus:ring-violet-200 focus:ring-opacity-50'
                ],
                'placeholder' => 'Sélectionnez une célébrité',
                'required' => true,
            ]);
        }

        $builder
            ->add('name', TextType::class, [
                'label' => 'Nom de l\'enchère',
                'attr' => [
                    'class' => 'mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-violet-500 focus:ring focus:ring-violet-200 focus:ring-opacity-50',
                    'placeholder' => 'Entrez le nom de l\'enchère'
                ]
            ])
            ->add('description', TextareaType::class, [
                'label' => 'Description',
                'attr' => [
                    'class' => 'mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-violet-500 focus:ring focus:ring-violet-200 focus:ring-opacity-50',
                    'rows' => 4,
                    'placeholder' => 'Décrivez votre enchère en détail'
                ]
            ])
            ->add('startedAt', DateTimeType::class, [
                'label' => 'Date de début',
                'widget' => 'single_text',
                'attr' => [
                    'class' => 'mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-violet-500 focus:ring focus:ring-violet-200 focus:ring-opacity-50 pl-10'
                ]
            ])
            ->add('finishedAt', DateTimeType::class, [
                'label' => 'Date de fin',
                'widget' => 'single_text',
                'attr' => [
                    'class' => 'mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-violet-500 focus:ring focus:ring-violet-200 focus:ring-opacity-50 pl-10'
                ]
            ])
//            ->add('products', CollectionType::class, [
//                'entry_type' => ProductType::class,
//                'entry_options' => ['label' => false],
//                'allow_add' => true,
//                'allow_delete' => true,
//                'by_reference' => false,
//                'prototype' => true,
//                'prototype_name' => '__name__',
//                'constraints' => [
//                    new Valid()
//                ],
//                'label' => false,
//            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Auction::class,
            'is_celebrity_registration' => false,
            'allow_file_upload' => true
        ]);
    }
}