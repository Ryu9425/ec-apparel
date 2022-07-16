<?php
/**
 * Created by SYSTEM_KD
 * Date: 2019-08-13
 */

namespace Plugin\UnderLimitQuantity\Form\Extension;


use Eccube\Form\Type\Admin\ProductClassEditType;
use Plugin\UnderLimitQuantity\Form\EventListener\ProductClassTypeEventListener;
use Plugin\UnderLimitQuantity\Form\Type\UnderQuantityType;
use Symfony\Component\Form\AbstractTypeExtension;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormEvents;

class ProductClassEditTypeExtension extends AbstractTypeExtension
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
        return ProductClassEditType::class;
    }

    public static function getExtendedTypes(): iterable
    {
        return [ProductClassEditType::class];
    }
}
