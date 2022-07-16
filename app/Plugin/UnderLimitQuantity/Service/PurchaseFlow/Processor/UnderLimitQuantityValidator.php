<?php
/**
 * Copyright(c) 2019 SYSTEM_KD
 * Date: 2019/10/27
 */

namespace Plugin\UnderLimitQuantity\Service\PurchaseFlow\Processor;


use Eccube\Annotation\CartFlow;
use Eccube\Entity\ItemInterface;
use Eccube\Entity\Product;
use Eccube\Entity\ProductClass;
use Eccube\Service\PurchaseFlow\ItemValidator;
use Eccube\Service\PurchaseFlow\PurchaseContext;
use Plugin\UnderLimitQuantity\Service\UnderLimitQuantityHelper;
use Plugin\UnderLimitQuantity\Service\UnderLimitQuantityService;

/**
 * @CartFlow
 *
 * Class UnderLimitQuantityValidator
 * @package Plugin\UnderLimitQuantity\Service\PurchaseFlow\Processor
 */
class UnderLimitQuantityValidator extends ItemValidator
{

    /** @var UnderLimitQuantityService */
    protected $underLimitQuantityService;

    /** @var UnderLimitQuantityHelper */
    protected $underLimitQuantityHelper;

    protected $underLimitClearFlg;

    public function __construct(
        UnderLimitQuantityService $underLimitQuantityService,
        UnderLimitQuantityHelper $underLimitQuantityHelper
    )
    {
        $this->underLimitQuantityService = $underLimitQuantityService;
        $this->underLimitQuantityHelper = $underLimitQuantityHelper;
    }

    /**
     * 妥当性検証を行う.
     *
     * @param ItemInterface $item
     * @param PurchaseContext $context
     * @throws \Eccube\Service\PurchaseFlow\InvalidItemException
     */
    protected function validate(ItemInterface $item, PurchaseContext $context)
    {
        if (!$item->isProduct()) {
            return;
        }

        $quantity = $item->getQuantity();

        /** @var ProductClass $productClass */
        $productClass = $item->getProductClass();

        /** @var Product $product */
        $product = $productClass->getProduct();
        if (!$product->getStockFind()) {
            return;
        }

        // 最低購入数取得
        $underLimitQuantity = $this->underLimitQuantityService->getUnderQuantity($productClass);

        // カート判定
        $productClassId = $this->underLimitQuantityHelper->getCartRequestProductClassId();

        // 数量が変更された商品のチェック制御
        if (!is_null($productClassId)
            && $productClassId == $item->getProductClass()->getId()) {
            if ($quantity < $underLimitQuantity) {
                // 最低購入数以下の数量となっている
                $this->underLimitClearFlg = false;
                $this->throwInvalidItemException('under_quantity.front.shopping.under_limit', $item->getProductClass());
            }
        } else {
            if ($quantity < $underLimitQuantity) {
                // 最低購入数以下の数量となっている
                $this->underLimitClearFlg = true;
                $this->throwInvalidItemException('under_quantity.front.shopping.under_limit_zero', $item->getProductClass());
            }
        }

    }

    /**
     * @param ItemInterface $item
     * @param PurchaseContext $context
     */
    protected function handle(ItemInterface $item, PurchaseContext $context)
    {
        $productClass = $item->getProductClass();
        $underLimitQuantity = $this->underLimitQuantityService->getUnderQuantity($productClass);
        if ($this->underLimitClearFlg) {
            // カート追加時
            $item->setQuantity(0);
        } else {
            // カート数量変更時
            $item->setQuantity($underLimitQuantity);
        }
    }
}
