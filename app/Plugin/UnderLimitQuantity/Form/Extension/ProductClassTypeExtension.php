<?php
/**
 * Copyright(c) 2019 SYSTEM_KD
 * Date: 2019/10/23
 */

namespace Plugin\UnderLimitQuantity\Form\Extension;


use Eccube\Form\Type\Admin\ProductClassType;
use Plugin\UnderLimitQuantity\Form\EventListener\ProductClassTypeEventListener;
use Plugin\UnderLimitQuantity\Form\Type\UnderQuantityType;
use Symfony\Component\Form\AbstractTypeExtension;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormEvents;

class ProductClassTypeExtension extends AbstractTypeExtension
{
    /** @var ProductClassTypeEventListener */
    protected $formEventListener;

    public function __construct(
        ProductClassTypeEventListener $formEventListener
    )
    {
        $this->formEventListener = $formEventListener;
    }

    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('UnderQuantity', UnderQuantityType::class)
            ->addEventListener(FormEvents::POST_SUBMIT, [
                $this->formEventListener, "postSubmit"
            ]);
    }

    /**
     * Returns the name of the type being extended.
     *
     * @return string The name of the type being extended
     */
    public function getExtendedType()
    {
        return ProductClassType::class;
    }

    public static function getExtendedTypes(): iterable
    {
        return [ProductClassType::class];
    }
}
