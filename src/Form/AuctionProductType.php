<?php

namespace App\Form;

use App\Entity\Auction;
use App\Entity\Product;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class AuctionProductType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('auction', AuctionType::class, [
                'label' => false,
                'data_class' => Auction::class,
            ])
            ->add('product', ProductType::class, [
                'label' => false,
                'data_class' => Product::class,
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            // Configure your form options here
        ]);
    }
}
