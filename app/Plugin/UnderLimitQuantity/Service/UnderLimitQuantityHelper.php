<?php
/**
 * Copyright(c) 2019 SYSTEM_KD
 * Date: 2019/10/29
 */

namespace Plugin\UnderLimitQuantity\Service;


use Eccube\Entity\Product;
use Eccube\Entity\ProductClass;
use Eccube\Event\TemplateEvent;
use Plugin\UnderLimitQuantity\Service\TwigRenderService\TwigRenderService;
use Symfony\Component\HttpFoundation\JsonResponse;

class UnderLimitQuantityHelper
{

    /** @var CommonHelper */
    protected $commonHelper;

    /** @var TwigRenderService */
    protected $twigRenderService;

    public function __construct(
        CommonHelper $commonHelper,
        TwigRenderService $twigRenderService
    )
    {
        $this->commonHelper = $commonHelper;
        $this->twigRenderService = $twigRenderService;
    }

    /**
     * リクエストからProductClassIdを取り出し
     * カート判定に使用
     *
     * @return mixed
     */
    public function getCartRequestProductClassId()
    {
        $request = $this->commonHelper->getCurrentRequest();
        $productClassId = $request->get('productClassId');

        return $productClassId;
    }

    /**
     * 最低購入数チェックNGの際のレスポンス
     *
     * @param ProductClass $productClass
     * @param $quantity
     * @return JsonResponse
     */
    public function getAddCartNGResponse(ProductClass $productClass, $quantity)
    {

        /** @var Product $product */
        $product = $productClass->getProduct();

        $productName = $productClass->getProduct()->getName();
        if ($productClass->hasClassCategory1()) {
            $productName .= ' - ' . $productClass->getClassCategory1()->getName();
        }
        if ($productClass->hasClassCategory2()) {
            $productName .= ' - ' . $productClass->getClassCategory2()->getName();
        }

        $message = trans('under_quantity.front.shopping.under_limit_zero', ['%product%' => $productName]);

        $result = [
            'done' => false,
            'messages' => [$message],
            'product_id' => $product->getId(),
            'under_quantity' => $quantity,
        ];

        return $this->commonHelper->getJsonResponse($result);
    }

    /**
     * 最低購入数チェック時のUI制御用JS埋め込み
     *
     * @param TemplateEvent $event
     * @throws \Twig_Error_Loader
     * @throws \Twig_Error_Runtime
     * @throws \Twig_Error_Syntax
     */
    public function changeAddCartJS(TemplateEvent $event)
    {
        $searchKey = "$('#ec-modal-header').html(this);";

        $insertJS = $this->twigRenderService->readTemplate('@UnderLimitQuantity/default/Product/over/cart_over_js.twig');

        $cutKey1 = "<script>";
        $cutKey2 = "</script>";

        // scriptタグカット
        $insertJS = str_replace($cutKey1, "", $insertJS);
        $insertJS = str_replace($cutKey2, "", $insertJS);

        $source = $event->getSource();
        $source = str_replace($searchKey, $insertJS, $source);

        $event->setSource($source);
    }

}
