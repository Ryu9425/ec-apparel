<?php
/**
 * Copyright(c) 2019 SYSTEM_KD
 * Date: 2019/10/27
 */

namespace Plugin\UnderLimitQuantity\EventSubscriber;


use Eccube\Entity\Product;
use Eccube\Entity\ProductClass;
use Eccube\Event\EccubeEvents;
use Eccube\Event\EventArgs;
use Eccube\Event\TemplateEvent;
use Plugin\UnderLimitQuantity\Service\TwigRenderService\EventSubscriber\TwigRenderTrait;
use Plugin\UnderLimitQuantity\Service\UnderLimitQuantityHelper;
use Plugin\UnderLimitQuantity\Service\UnderLimitQuantityService;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\Form\FormInterface;

class ProductEventSubscriber implements EventSubscriberInterface
{

    use TwigRenderTrait;

    /** @var UnderLimitQuantityService */
    protected $underLimitQuantityService;

    /** @var UnderLimitQuantityHelper */
    protected $underLimitQuantityHelper;

    public function __construct(
        UnderLimitQuantityService $underLimitQuantityService,
        UnderLimitQuantityHelper $underLimitQuantityHelper
    )
    {
        $this->underLimitQuantityService = $underLimitQuantityService;
        $this->underLimitQuantityHelper = $underLimitQuantityHelper;
    }

    /**
     * 商品一覧
     *
     * @param TemplateEvent $event
     * @throws \Twig_Error_Loader
     * @throws \Twig_Error_Runtime
     * @throws \Twig_Error_Syntax
     */
    public function onTemplateProductList(TemplateEvent $event)
    {
        $this->initRenderService($event);

        // 数量制御用JSの追加
        $this->addTwigRenderSnippet(
            null,
            '@UnderLimitQuantity/default/Product/list_ex_js.twig'
        );

        // Script改変
        $this->underLimitQuantityHelper->changeAddCartJS($event);
    }

    /**
     * 商品詳細
     *
     * @param TemplateEvent $event
     * @throws \Twig_Error_Loader
     * @throws \Twig_Error_Runtime
     * @throws \Twig_Error_Syntax
     */
    public function onTemplateProductDetail(TemplateEvent $event)
    {
        $this->initRenderService($event);

        // 数量制御用JSの追加
        $this->addTwigRenderSnippet(
            null,
            '@UnderLimitQuantity/default/Product/detail_ex_js.twig'
        );

        // Script改変
        $this->underLimitQuantityHelper->changeAddCartJS($event);
    }

    /**
     * カート追加処理完了時
     *
     * @param EventArgs $event
     */
    public function onFrontProductCartAddComplete(EventArgs $event)
    {
        /** @var Product $product */
        $product = $event->getArgument('Product');

        /** @var FormInterface $form */
        $form = $event->getArgument('form');

        /** @var ProductClass $productClass */
        $productClass = $form->get('ProductClass')->getData();

        $underQuantity = $this->underLimitQuantityService->getUnderQuantity($productClass);

        // 最低購入数が設定されていない場合は処理を抜ける
        if ($underQuantity == 1) {
            return;
        }

        // カートへの追加が成功しているか
        $nowQuantity = $this->underLimitQuantityService->getCartProductClassQuantity($productClass);

        if ($nowQuantity > 0) {
            // 追加成功 or 既に追加されている
            $underQuantity = $this->underLimitQuantityService->getUnderQuantity($productClass);

            if ($nowQuantity >= $underQuantity) {
                // 必要数量がセットされている
                return;
            } else {
                // 最低購入数を返却
                $event->setResponse(
                    $this->underLimitQuantityHelper->getAddCartNGResponse($productClass, ($underQuantity - $nowQuantity))
                );
            }

        } else {
            // カートに商品なし = 追加に失敗
            // 最低購入数を返却
            $event->setResponse(
                $this->underLimitQuantityHelper->getAddCartNGResponse($productClass, ($underQuantity))
            );
        }
    }

    /**
     * Returns an array of event names this subscriber wants to listen to.
     *
     * The array keys are event names and the value can be:
     *
     *  * The method name to call (priority defaults to 0)
     *  * An array composed of the method name to call and the priority
     *  * An array of arrays composed of the method names to call and respective
     *    priorities, or 0 if unset
     *
     * For instance:
     *
     *  * ['eventName' => 'methodName']
     *  * ['eventName' => ['methodName', $priority]]
     *  * ['eventName' => [['methodName1', $priority], ['methodName2']]]
     *
     * @return array The event names to listen to
     */
    public static function getSubscribedEvents()
    {
        return [
            // 商品一覧
            'Product/list.twig' => ['onTemplateProductList'],
            // 商品詳細
            'Product/detail.twig' => ['onTemplateProductDetail'],
            // カート追加完了
            EccubeEvents::FRONT_PRODUCT_CART_ADD_COMPLETE => ['onFrontProductCartAddComplete', -10],
        ];
    }
}
