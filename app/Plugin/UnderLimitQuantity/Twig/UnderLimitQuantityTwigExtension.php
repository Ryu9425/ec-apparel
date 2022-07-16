<?php
/**
 * Copyright(c) 2019 SYSTEM_KD
 * Date: 2019/10/27
 */

namespace Plugin\UnderLimitQuantity\Twig;


use Eccube\Entity\Product;
use Eccube\Entity\ProductClass;
use Knp\Bundle\PaginatorBundle\Pagination\SlidingPagination;
use Plugin\UnderLimitQuantity\Service\UnderLimitQuantityService;
use Twig\Extension\AbstractExtension;
use Twig\TwigFunction;

class UnderLimitQuantityTwigExtension extends AbstractExtension
{

    /** @var UnderLimitQuantityService  */
    public $underLimitQuantityService;

    public function __construct(
        UnderLimitQuantityService $underLimitQuantityService
    )
    {
        $this->underLimitQuantityService = $underLimitQuantityService;
    }

    public function getFunctions()
    {
        return [
            new TwigFunction('get_under_limit_quantity_data_list', [$this, 'getUnderLimitQuantityDataList']),
            new TwigFunction('get_under_limit_quantity_data', [$this, 'getUnderLimitQuantityData']),
        ];
    }

    /**
     * 最低販売数量のリストを返却
     * 商品ID/商品規格ID毎に格納
     *
     * @param SlidingPagination $pagination
     * @return false|string
     */
    public function getUnderLimitQuantityDataList(SlidingPagination $pagination)
    {
        $result = [
            'product' => [],
            'productClass' => []
        ];

        /** @var Product $item */
        foreach ($pagination->getItems() as $item) {

            if (!$item->hasProductClass()) continue;

            $underQuantityList = $this->underLimitQuantityService->getUnderQuantityList($item);

            $result['product'] = $result['product'] + $underQuantityList['product'];
            $result['productClass'] = $result['productClass'] + $underQuantityList['productClass'];

        }

        return json_encode($result);
    }

    /**
     * 最低販売数量のリストを返却
     *
     * @param Product $product
     * @return false|string
     */
    public function getUnderLimitQuantityData(Product $product)
    {
        $underQuantityList = $this->underLimitQuantityService->getUnderQuantityList($product);
        return json_encode($underQuantityList);
    }

}
