<?php
/**
 * Copyright(c) 2019 SYSTEM_KD
 * Date: 2019/10/27
 */

namespace Plugin\UnderLimitQuantity\Service;


use Eccube\Entity\CartItem;
use Eccube\Entity\Product;
use Eccube\Entity\ProductClass;
use Eccube\Service\CartService;
use Plugin\UnderLimitQuantity\Entity\UnderQuantity;

class UnderLimitQuantityService
{

    /** @var CartService */
    protected $cartService;

    public function __construct(
        CartService $cartService
    )
    {
        $this->cartService = $cartService;
    }

    /**
     * 最低購入数数量リストを返却
     *
     * @param Product $product
     * @return array
     */
    public function getUnderQuantityList(Product $product)
    {
        $result = [];

        $productId = $product->getId();

        $result['product'][$productId] =
            $this->getUnderQuantityByProduct($product);

        /** @var ProductClass $productClass */
        foreach ($product->getProductClasses() as $productClass) {

            $productClassId = $productClass->getId();
            $result['productClass'][$productClassId] = $this->calcCartUnderQuantity($productClass);
        }

        return $result;
    }

    /**
     * 指定された商品の最低購入数返却
     * 規格商品の場合は 1
     *
     * @param Product $product
     * @return int
     */
    public function getUnderQuantityByProduct(Product $product)
    {

        // 規格あり商品の場合
        if ($product->hasProductClass()) {

            $underQuantities = [];
            /** @var ProductClass $productClass */
            foreach ($product->getProductClasses() as $productClass) {
                $underQuantities[] = $this->calcCartUnderQuantity($productClass);
            }

            // 規格の最低購入数が全て同じ場合は、その値を返す
            if (min($underQuantities) == max($underQuantities)) {
                return min($underQuantities);
            } else {
                return 1;
            }
        }

        /** @var ProductClass $productClass */
        $productClass = $product->getProductClasses()[0];

        return $this->calcCartUnderQuantity($productClass);
    }

    /**
     * 指定した商品規格の最低購入数を返却
     *
     * @param ProductClass $productClass
     * @return int
     */
    public function getUnderQuantity(ProductClass $productClass)
    {
        /** @var UnderQuantity $underQuantity */
        $underQuantity = $productClass->getUnderQuantity();

        if ($underQuantity) {
            return $underQuantity->getQuantity();
        }

        return 1;
    }

    /**
     * 指定した商品がカートにある場合数量を返却
     *
     * @param ProductClass $productClass
     * @return int|string
     */
    public function getCartProductClassQuantity(ProductClass $productClass)
    {

        $Carts = $this->cartService->getCarts();

        foreach ($Carts as $cart) {
            /** @var CartItem $cartItem */
            foreach ($cart->getCartItems() as $cartItem) {

                if ($cartItem->isProduct()) {

                    if($productClass->getId() == $cartItem->getProductClass()->getId()) {
                        return $cartItem->getQuantity();
                    }
                }
            }
        }

        return 0;
    }

    /**
     * カートの数量を加味した最低購入数量を返却
     *
     * @param ProductClass $productClass
     * @return int
     */
    private function calcCartUnderQuantity(ProductClass $productClass)
    {
        // 最低購入数
        $quantity = $this->getUnderQuantity($productClass);

        // カート数量
        $cartQuantity = $this->getCartProductClassQuantity($productClass);

        $diffQuantity = $quantity - $cartQuantity;

        if($diffQuantity > 0) {
            // まだ最低購入数に達していない
            return $diffQuantity;
        } else {
            // 既に最低購入数がカートに入っている
            return 1;
        }
    }
}
