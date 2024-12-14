<?php

namespace App\Form;

use App\Entity\Celebrity;
use App\Entity\Profession;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\FileType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class CelebrityType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('realFirstName', TextType::class, [
                'label' => 'Prénom',
                'attr' => [
                    'class' => 'mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-300 focus:ring focus:ring-indigo-200 focus:ring-opacity-50'
                ]
            ])
            ->add('realLastName', TextType::class, [
                'label' => 'Nom',
                'attr' => [
                    'class' => 'mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-300 focus:ring focus:ring-indigo-200 focus:ring-opacity-50'
                ]
            ])
            ->add('stageName', TextType::class, [
                'label' => 'Nom de scene',
                'attr' => [
                    'class' => 'mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-300 focus:ring focus:ring-indigo-200 focus:ring-opacity-50'
                ]
            ])
            ->add('biography', TextareaType::class, [
                'label' => 'Biographie',
                'attr' => [
                    'class' => 'mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-300 focus:ring focus:ring-indigo-200 focus:ring-opacity-50',
                    'rows' => 5
                ]
            ])
            ->add('image', FileType::class, [
                'label' => 'Photo',
                'required' => false,
                'attr' => [
                    'class' => 'mt-1 block w-full'
                ]
            ])
            ->add('video_pres', FileType::class, [
                'label' => 'Vidéo de présentation',
                'required' => false,
                'attr' => [
                    'class' => 'mt-1 block w-full',
                    'accept' => 'video/*'
                ]
            ])
            ->add('video_pres_alt', TextType::class, [
                'label' => 'Description de la vidéo de présentation',
                'required' => false,
                'attr' => [
                    'class' => 'mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-300 focus:ring focus:ring-indigo-200 focus:ring-opacity-50',
                ]
            ])
            ->add('video_thanks', FileType::class, [
                'label' => 'Vidéo de remerciement',
                'required' => false,
                'attr' => [
                    'class' => 'mt-1 block w-full',
                    'accept' => 'video/*'
                ]
            ])
            ->add('video_thanks_alt', TextType::class, [
                'label' => 'Description de la vidéo de remerciement',
                'required' => false,
                'attr' => [
                    'class' => 'mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-300 focus:ring focus:ring-indigo-200 focus:ring-opacity-50',
                ]
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Celebrity::class,
        ]);
    }
}