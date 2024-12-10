<?php

namespace App\Form;

use App\Entity\User;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\PasswordType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\IsTrue;
use Symfony\Component\Validator\Constraints\Length;
use Symfony\Component\Validator\Constraints\NotBlank;

class RegistrationFormType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('email', TextType::class, [
                'attr'=>[
                    'autocomplete'=>'email',
                    'class'=>"block w-full rounded-md border-0 py-1.5 text-gray-900 shadow-sm ring-1 ring-inset ring-gray-300 placeholder:text-gray-400 focus:ring-2 focus:ring-inset focus:ring-indigo-600 sm:text-sm/6"
                ]
            ])
            ->add('plainPassword', PasswordType::class, [
                // instead of being set onto the object directly,
                // this is read and encoded in the controller
                'label' => 'Mot de passe',
                'mapped' => false,
                'attr' => [
                    'autocomplete' => 'new-password',
                    'class' => 'block w-full rounded-md border-0 py-1.5 text-gray-900 shadow-sm ring-1 ring-inset ring-gray-300 placeholder:text-gray-400 focus:ring-2 focus:ring-inset focus:ring-indigo-600 sm:text-sm/6'
                ],
                'constraints' => [
                    new NotBlank([
                        'message' => 'Please enter a password',
                    ]),
                    new Length([
                        'min' => 6,
                        'minMessage' => 'Your password should be at least {{ limit }} characters',
                        // max length allowed by Symfony for security reasons
                        'max' => 4096,
                    ]),
                ],
            ])
            ->add('firstName', TextType::class, [
                'label' => 'Prénom',
                'attr'=>[
                    'autocomplete'=>'prenom',
                    'class'=>"block w-full rounded-md border-0 py-1.5 text-gray-900 shadow-sm ring-1 ring-inset ring-gray-300 placeholder:text-gray-400 focus:ring-2 focus:ring-inset focus:ring-indigo-600 sm:text-sm/6"
                ]
            ])
            ->add('lastName', TextType::class, [
                'label' => 'Nom',
                'attr'=>[
                    'autocomplete'=>'nom',
                    'class'=>"block w-full rounded-md border-0 py-1.5 text-gray-900 shadow-sm ring-1 ring-inset ring-gray-300 placeholder:text-gray-400 focus:ring-2 focus:ring-inset focus:ring-indigo-600 sm:text-sm/6"
                ]
            ])
            ->add('telephone', TextType::class, [
                'label' => 'Téléphone',
                'attr'=>[
                    'autocomplete'=>'telephone',
                    'class'=>"block w-full rounded-md border-0 py-1.5 text-gray-900 shadow-sm ring-1 ring-inset ring-gray-300 placeholder:text-gray-400 focus:ring-2 focus:ring-inset focus:ring-indigo-600 sm:text-sm/6"
                ]
            ])
            ->add('submit', SubmitType::class, [
                'attr' => [
                    'class' => "btn btn-lg btn-primary w-1/2 mx-auto block py-2 rounded-md",
                    'value' => 'Inscris toi !'
                ]
            ]);
        if (!$options['is_edit']) {
            $builder->add('roles', ChoiceType::class, [
                'label' => 'Rôle',
                'choices' => [
                    'Utilisateur' => 'ROLE_USER',
                    'Vendeur' => 'ROLE_SELLER',
                    'Admin' => 'ROLE_ADMIN'
                ],
                'multiple' => false,
                'expanded' => false,
                'attr' => [
                    'class' => 'block w-full rounded-md border-0 py-1.5 text-gray-900 shadow-sm ring-1 ring-inset ring-gray-300 focus:ring-2 focus:ring-inset focus:ring-indigo-600 sm:text-sm/6'
                ]
            ])
                ->add('checkIdentity', CheckboxType::class, [
                    'label' => 'Vérifier mon identité',
                    'mapped' => false,
                    'required' => true,
                    'attr' => [
                        'class' => 'h-6 w-11 appearance-none rounded-full bg-gray-200 
                              cursor-pointer checked:bg-indigo-600 duration-200 
                              ease-in-out peer relative'
                    ],
                    'label_attr' => [
                        'class' => 'inline-flex items-center text-sm font-medium text-gray-700 cursor-pointer'
                    ],
//                    'constraints' => [
//                        new IsTrue([
//                            'message' => 'Vous devez vérifier votre identité.',
//                        ]),
//                    ]
                ]);

        }

    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => User::class,
            'is_edit' => false,
        ]);
    }
}
