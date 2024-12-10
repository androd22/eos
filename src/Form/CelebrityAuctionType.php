<?php

namespace App\Form;

use App\Entity\Auction;
use App\Entity\Celebrity;
use App\Entity\Product;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class CelebrityAuctionType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('celebrity', CelebrityType::class, [
                'label' => false,
                'data_class' => Celebrity::class,
            ])

            // Section Enchère
            ->add('auction', AuctionType::class, [
                'label' => false,
                'data_class' => Auction::class,
                'is_celebrity_registration' => $options['auction_options']['is_celebrity_registration']
            ])

            // Section Produit
//            ->add('product', ProductType::class, [
//                'label' => false,
//                'data_class' => Product::class,
//            ]);
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'auction_options' => [
            'is_celebrity_registration' => true
        ]
        ]);
    }
}
