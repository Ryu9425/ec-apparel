<?php
/**
 * Copyright(c) 2019 SYSTEM_KD
 * Date: 2019/10/24
 */

namespace Plugin\UnderLimitQuantity\Form\Type;


use Plugin\UnderLimitQuantity\Entity\UnderQuantity;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\NumberType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints as Assert;

class UnderQuantityType extends AbstractType
{

    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('quantity', NumberType::class, [
                'label' => 'under_quantity.admin.quantity_label',
                'required' => false,
                'constraints' => [
                    new Assert\Length([
                        'max' => 10,
                    ]),
                    new Assert\GreaterThanOrEqual([
                        'value' => 1,
                    ]),
                    new Assert\Regex([
                        'pattern' => "/^\d+$/u",
                        'message' => 'form_error.numeric_only',
                    ]),
                ],
            ]);

    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'data_class' => UnderQuantity::class,
        ]);
    }
}
