<?php
namespace Plugin\JsysSalesPeriod\Service\PurchaseFlow\Processor;

use Eccube\Annotation\CartFlow;
use Eccube\Annotation\ShoppingFlow;
use Eccube\Entity\ItemInterface;
use Eccube\Service\PurchaseFlow\ItemValidator;
use Eccube\Service\PurchaseFlow\PurchaseContext;

/**
 * 販売期間内の商品かどうか
 * @author manabe
 *
 * @CartFlow
 * @ShoppingFlow
 */
class ProductSalesPeriodValidator extends ItemValidator
{
    /**
     * {@inheritDoc}
     * @see \Eccube\Service\PurchaseFlow\ItemValidator::validate()
     */
    protected function validate(ItemInterface $item, PurchaseContext $context)
    {
        if ($item->isProduct()) {
            $Product = $item->getProductClass()->getProduct();
            if ($Product->isJsysSalesPeriodNotSale()) {
                $this->throwInvalidItemException('front.shopping.not_purchase');
            }
        }
    }

    /**
     * {@inheritDoc}
     * @see \Eccube\Service\PurchaseFlow\ItemValidator::handle()
     */
    protected function handle(ItemInterface $item, PurchaseContext $context)
    {
        $item->setQuantity(0);
    }

}
