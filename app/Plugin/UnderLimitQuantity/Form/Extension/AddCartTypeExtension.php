<?php
/**
 * Copyright(c) 2019 SYSTEM_KD
 * Date: 2019/10/27
 */

namespace Plugin\UnderLimitQuantity\Form\Extension;


use Eccube\Form\Type\AddCartType;
use Plugin\UnderLimitQuantity\Form\EventListener\AddCartTypeQuantityEventListener;
use Symfony\Component\Form\AbstractTypeExtension;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormEvents;

class AddCartTypeExtension extends AbstractTypeExtension
{

    /** @var AddCartTypeQuantityEventListener */
    protected $formEventListener;

    public function __construct(
        AddCartTypeQuantityEventListener $formEventListener
    )
    {
        $this->formEventListener = $formEventListener;
    }

    public function buildForm(FormBuilderInterface $builder, array $options)
    {

        if ($builder->has('quantity')) {

            $builder
                ->get('quantity')
                ->addEventListener(FormEvents::PRE_SET_DATA, [
                    $this->formEventListener, "preSetData"
                ]);
        }
    }

    /**
     * Returns the name of the type being extended.
     *
     * @return string The name of the type being extended
     */
    public function getExtendedType()
    {
        return AddCartType::class;
    }

    public static function getExtendedTypes(): iterable
    {
        return [AddCartType::class];
    }
}
