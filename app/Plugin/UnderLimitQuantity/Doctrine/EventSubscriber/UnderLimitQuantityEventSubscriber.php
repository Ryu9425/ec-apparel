<?php
/**
 * Copyright(c) 2019 SYSTEM_KD
 * Date: 2019/10/29
 */

namespace Plugin\UnderLimitQuantity\Doctrine\EventSubscriber;


use Doctrine\Common\EventSubscriber;
use Doctrine\ORM\Event\LifecycleEventArgs;
use Doctrine\ORM\Events;
use Eccube\Entity\ProductClass;
use Plugin\UnderLimitQuantity\Service\CommonHelper;
use Plugin\UnderLimitQuantity\Service\UnderLimitQuantityService;

class UnderLimitQuantityEventSubscriber implements EventSubscriber
{

    private $commonHelper;

    /** @var UnderLimitQuantityService */
    private $underLimitQuantityService;

    public function __construct(
        CommonHelper $commonHelper,
        UnderLimitQuantityService $underLimitQuantityService
    )
    {
        $this->commonHelper = $commonHelper;
        $this->underLimitQuantityService = $underLimitQuantityService;
    }

    /**
     * Returns an array of events this subscriber wants to listen to.
     *
     * @return string[]
     */
    public function getSubscribedEvents()
    {
        return [
            Events::postLoad,
        ];
    }

    public function postLoad(LifecycleEventArgs $args)
    {
        $entity = $args->getObject();

        $targetRoutes = [
            'product_list', // 商品一覧
            'product_detail', // 商品詳細
            'product_add_cart', // カート追加
            'cart', // カート
            'cart_handle_item', // カート数量変更
            'shopping', // ご注文手続き
            'shopping_confirm', // ご注文内容のご確認
            'shopping_shipping_multiple', // 複数配送
            'shopping_checkout', // 注文処理
        ];

        if ($this->commonHelper->isRoute($targetRoutes)) {
            if ($entity instanceof ProductClass) {

                // 無制限の場合は処理不要
                if ($entity->isStockUnlimited()) {
                    return;
                }

                // 最低購入数に合わせて判定用の在庫数調整
                $underQuantity = $this->underLimitQuantityService
                    ->getUnderQuantity($entity);

                if ($underQuantity > 1) {

                    // 最低購入数 > 現在の在庫
                    if ($underQuantity > $entity->getStock()) {
                        // 在庫切れにする
                        $entity->setStock(0);
                    }
                }
            }
        }
    }
}
