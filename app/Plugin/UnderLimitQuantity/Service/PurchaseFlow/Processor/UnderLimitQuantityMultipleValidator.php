<?php
/**
 * Copyright(c) 2019 SYSTEM_KD
 * Date: 2019/11/04
 */

namespace Plugin\UnderLimitQuantity\Service\PurchaseFlow\Processor;


use Eccube\Annotation\ShoppingFlow;
use Eccube\Entity\ItemHolderInterface;
use Eccube\Entity\OrderItem;
use Eccube\Entity\ProductClass;
use Eccube\Entity\Shipping;
use Eccube\Repository\ProductClassRepository;
use Eccube\Service\PurchaseFlow\InvalidItemException;
use Eccube\Service\PurchaseFlow\ItemHolderValidator;
use Eccube\Service\PurchaseFlow\PurchaseContext;
use Plugin\UnderLimitQuantity\Service\UnderLimitQuantityService;

/**
 * @ShoppingFlow
 *
 * Class UnderLimitQuantityMultipleValidator
 * @package Plugin\UnderLimitQuantity\Service\PurchaseFlow\Processor
 */
class UnderLimitQuantityMultipleValidator extends ItemHolderValidator
{

    /** @var ProductClassRepository */
    protected $productClassRepository;

    /** @var UnderLimitQuantityService */
    protected $underLimitQuantityService;

    public function __construct(
        ProductClassRepository $productClassRepository,
        UnderLimitQuantityService $underLimitQuantityService
    )
    {
        $this->productClassRepository = $productClassRepository;
        $this->underLimitQuantityService = $underLimitQuantityService;
    }

    /**
     * @param ItemHolderInterface $itemHolder
     * @param PurchaseContext $context
     *
     * @throws InvalidItemException
     */
    protected function validate(ItemHolderInterface $itemHolder, PurchaseContext $context)
    {
        $OrderItemsByProductClass = [];
        /** @var Shipping $Shipping */
        foreach ($itemHolder->getShippings() as $Shipping) {
            foreach ($Shipping->getOrderItems() as $item) {
                if ($item->isProduct()) {
                    $id = $item->getProductClass()->getId();
                    $OrderItemsByProductClass[$id][] = $item;
                }
            }
        }

        foreach ($OrderItemsByProductClass as $id => $items) {

            /** @var ProductClass $productClass */
            $productClass = $this->productClassRepository->find($id);
            $underQuantity = $this->underLimitQuantityService->getUnderQuantity($productClass);

            $targetKey = 0;

            /** @var OrderItem $item */
            foreach ($items as $key => $item) {

                if($item->getQuantity() > 0) {
                    $targetKey = $key;
                }

                $underQuantity = $underQuantity - $item->getQuantity();
            }

            if ($underQuantity > 0) {

                /** @var OrderItem $item */
                $item = $items[$targetKey];

                // 最低購入数の不足分を加算
                $item->setQuantity(intval($item->getQuantity()) + $underQuantity);

                $this->throwInvalidItemException('under_quantity.front.shopping.under_limit', $productClass, true);
            }
        }
    }
}
