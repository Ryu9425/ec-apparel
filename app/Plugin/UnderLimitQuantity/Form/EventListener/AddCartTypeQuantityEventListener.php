<?php
/**
 * Copyright(c) 2019 SYSTEM_KD
 * Date: 2019/10/27
 */

namespace Plugin\UnderLimitQuantity\Form\EventListener;


use Eccube\Entity\Product;
use Plugin\UnderLimitQuantity\Service\UnderLimitQuantityService;
use Symfony\Component\Form\FormEvent;

class AddCartTypeQuantityEventListener
{

    protected $underLimitQuantityService;

    public function __construct(
        UnderLimitQuantityService $underLimitQuantityService
    )
    {
        $this->underLimitQuantityService = $underLimitQuantityService;
    }

    public function preSetData(FormEvent $event)
    {

        $form = $event->getForm();

        // quantity -> AddCartType > config
        /** @var Product $product */
        $product = $form->getParent()
            ->getConfig()
            ->getOption('product');

        if ($product->getStockFind()) {

            // 規格なし商品
            $underQuantity = $this->underLimitQuantityService->getUnderQuantityByProduct($product);
            $event->setData($underQuantity);
        }

    }
}
