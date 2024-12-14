<?php

namespace App\Form;

use App\Entity\Address;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\Length;
use Symfony\Component\Validator\Constraints\NotBlank;

class AddressType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('name_res', TextType::class, [
                'label' => 'Nom de la résidence',
                'constraints' => [
                    new NotBlank([
                        'message' => 'Veuillez entrer le nom de la résidence',
                    ]),
                    new Length([
                        'max' => 100,
                        'maxMessage' => 'Le nom de la résidence ne peut pas dépasser {{ limit }} caractères',
                    ]),
                ],
                'attr' => [
                    'placeholder' => 'Nom de la résidence ou du bâtiment',
                ],
            ])
            ->add('street', TextType::class, [
                'label' => 'Rue',
                'constraints' => [
                    new NotBlank([
                        'message' => 'Veuillez entrer l\'adresse',
                    ]),
                    new Length([
                        'max' => 100,
                        'maxMessage' => 'L\'adresse ne peut pas dépasser {{ limit }} caractères',
                    ]),
                ],
                'attr' => [
                    'placeholder' => 'Numéro et nom de rue',
                ],
            ])
            ->add('zipcode', TextType::class, [
                'label' => 'Code postal',
                'constraints' => [
                    new NotBlank([
                        'message' => 'Veuillez entrer le code postal',
                    ]),
                    new Length([
                        'max' => 15,
                        'maxMessage' => 'Le code postal ne peut pas dépasser {{ limit }} caractères',
                    ]),
                ],
                'attr' => [
                    'placeholder' => 'Code postal',
                ],
            ])
            ->add('city', TextType::class, [
                'label' => 'Ville',
                'constraints' => [
                    new NotBlank([
                        'message' => 'Veuillez entrer la ville',
                    ]),
                    new Length([
                        'max' => 50,
                        'maxMessage' => 'Le nom de la ville ne peut pas dépasser {{ limit }} caractères',
                    ]),
                ],
                'attr' => [
                    'placeholder' => 'Ville',
                ],
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Address::class,
        ]);
    }
}