<?php
/**
 * Copyright(c) 2019 SYSTEM_KD
 * Date: 2019/10/24
 */

namespace Plugin\UnderLimitQuantity\EventSubscriber;


use Eccube\Entity\Product;
use Eccube\Event\TemplateEvent;
use Plugin\UnderLimitQuantity\Service\TwigRenderService\EventSubscriber\TwigRenderTrait;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

class AdminProductEventSubscriber implements EventSubscriberInterface
{

    use TwigRenderTrait;

    /**
     * 商品管理
     *
     * @param TemplateEvent $event
     */
    public function onTemplateProductProduct(TemplateEvent $event)
    {
        /** @var Product $Product */
        $Product = $event->getParameter('Product');

        if ($Product->hasProductClass()) {
            // 規格あり商品
        } else {
            // 規格なし商品
            $this->initRenderService($event);

            $this->createInsertBuilder()
                ->find('#basicConfig > div')
                ->setTargetId('under_quantity_form')
                ->eq(0)
                ->setInsertModeAppend();

            $this->addTwigRenderSnippet('@UnderLimitQuantity/admin/Product/default/product_ex.twig');
        }
    }

    /**
     * 商品規格登録
     *
     * @param TemplateEvent $event
     * @throws \Twig_Error_Loader
     * @throws \Twig_Error_Runtime
     * @throws \Twig_Error_Syntax
     */
    public function onTemplateProductClassEdit(TemplateEvent $event)
    {

        $this->initRenderService($event);

        $this->twigRenderHelper->addProductClassEditArea(
            $event,
            '@UnderLimitQuantity/admin/Product/class/under_limit_quantity_title.twig',
            '@UnderLimitQuantity/admin/Product/class/under_limit_quantity_body.twig',
            'UnderQuantity',
            "最低購入数設定"
        );

        $this->addTwigRenderSnippetSlow();
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
            // 商品詳細
            '@admin/Product/product.twig' => ['onTemplateProductProduct'],
            // 規格登録
            '@admin/Product/product_class.twig' => ['onTemplateProductClassEdit', -10],
        ];
    }
}
