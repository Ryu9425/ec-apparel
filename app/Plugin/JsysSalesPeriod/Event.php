<?php

namespace Plugin\JsysSalesPeriod;

use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Eccube\Event\EccubeEvents as Events;
use Eccube\Event\EventArgs;
use Eccube\Event\TemplateEvent;
use Plugin\JsysSalesPeriod\Entity\Config;
use Plugin\JsysSalesPeriod\Repository\ConfigRepository;

class Event implements EventSubscriberInterface
{
    /**
     * @var Config
     */
    protected $Config;


    /**
     * Event constructor.
     * @param ConfigRepository $configRepo
     */
    public function __construct(ConfigRepository $configRepo)
    {
        $this->Config = $configRepo->get();
    }

    /**
     * @return array
     */
    public static function getSubscribedEvents()
    {
        return [
            Events::FRONT_PRODUCT_INDEX_SEARCH        => 'onFrontProductIndexSearch',
            Events::FRONT_PRODUCT_DETAIL_INITIALIZE   => 'onFrontProductDetailInitialize',
            Events::FRONT_PRODUCT_CART_ADD_INITIALIZE => 'onFrontProductCartAddInitialize',
            'Product/list.twig'                       => 'onRenderProductList',
            'Product/detail.twig'                     => 'onRenderProductDetail',
            '@admin/Product/product.twig'             => 'onAdminRenderProduct',
        ];
    }

    /**
     * フロント > 商品一覧
     *  - ページネーターに使用するQueryBuilderへ抽出条件を追加
     * @param EventArgs $event
     */
    public function onFrontProductIndexSearch(EventArgs $event)
    {
        /*
         * 表示する商品の条件に下記を追加
         * ・販売期間中または販売期間外でも表示する商品
         *   (
         *     (開始 IS NULL OR 開始 <= 今) AND (終了 IS NULL OR 終了 > 今)
         *     OR
         *     ((開始 > 今 AND 販売前表示 = true) OR (終了 <= 今 AND 終了後表示 = true))
         *   )
         */
        $start    = 'p.jsys_sales_period_sale_start';
        $finish   = 'p.jsys_sales_period_sale_finish';
        $before   = 'p.jsys_sales_period_display_before_sale';
        $finished = 'p.jsys_sales_period_display_finished';

        /** @var \Doctrine\ORM\QueryBuilder $qb */
        $qb = $event->getArgument('qb');
        $qb->andWhere($qb->expr()->orX(
            $qb->expr()->andX(
                $qb->expr()->orX($start . ' IS NULL', $start . ' <= :now'),
                $qb->expr()->orX($finish . ' IS NULL', $finish . ' > :now')
            ),
            $qb->expr()->orX(
                $qb->expr()->andX($start . ' > :now', $before . ' = true'),
                $qb->expr()->andX($finish . ' <= :now', $finished . ' = true')
            )
        ))
        ->setParameter('now', new \DateTime());
    }

    /**
     * フロント > 商品詳細
     *  - 販売期間外で期間外表示をしない商品の場合にNotFoundを発生
     * @param EventArgs $event
     * @throws NotFoundHttpException
     */
    public function onFrontProductDetailInitialize(EventArgs $event)
    {
        /*
         * 販売開始前で販売前表示をしない または
         * 販売終了後で終了後表示をしない 場合にNotFound
         */
        /** @var \Eccube\Entity\Product $Product */
        $Product      = $event->getArgument('Product');
        $isBefore     = $Product->isJsysSalesPeriodBeforeSale();
        $dispBefore   = $Product->getJsysSalesPeriodDisplayBeforeSale();
        $isFinished   = $Product->isJsysSalesPeriodFinishedSale();
        $dispFinished = $Product->getJsysSalesPeriodDisplayFinished();
        
        
        if ($isBefore && !$dispBefore || $isFinished && !$dispFinished) {
            // throw new NotFoundHttpException();
            $url = "https://apparel-oroshitonya.com/?".$Product->getId();
            $message = ($isFinished == 1)?"すでに販売終了日が過ぎました。": "まだ販売開始日前です。";
             echo "<script>alert('" . $message . "'); 
                    window.location.href='" . $url . "';
                </script>";
        }
    }

    /**
     * フロント > カート追加
     *  - 販売期間外の商品の場合にNotFoundを発生
     * @param EventArgs $event
     * @throws NotFoundHttpException
     */
    public function onFrontProductCartAddInitialize(EventArgs $event)
    {
        /** @var \Eccube\Entity\Product $Product */
        $Product = $event->getArgument('Product');
        if ($Product->isJsysSalesPeriodNotSale()) {
            throw new NotFoundHttpException();
        }
    }

    /**
     * フロント > 商品一覧 Twig
     *  - 販売前または終了後でも表示する商品のカートインボタンを置き換え
     * @param TemplateEvent $event
     */
    public function onRenderProductList(TemplateEvent $event)
    {
        $event->addAsset('@JsysSalesPeriod/default/Product/list_replace_button.twig');

        $parameters = $event->getParameters();
        $parameters['JsysSalesPeriodConfig'] = $this->Config;
        $event->setParameters($parameters);
    }

    /**
     * フロント > 商品詳細 Twig
     *  - 販売前または終了後でも表示する商品のカートインボタンを置き換え
     * @param TemplateEvent $event
     */
    public function onRenderProductDetail(TemplateEvent $event)
    {
        $event->addAsset('@JsysSalesPeriod/default/Product/detail_replace_button.twig');

        $parameters = $event->getParameters();
        $parameters['JsysSalesPeriodConfig'] = $this->Config;
        $event->setParameters($parameters);
    }

    /**
     * 管理 > 商品管理 > 商品登録・編集 Twig
     *  - 画面に販売期間設定を追加
     * @param TemplateEvent $event
     */
    public function onAdminRenderProduct(TemplateEvent $event)
    {
        $event->addAsset('@JsysSalesPeriod/admin/Product/ext_product_edit_asset.twig');
        $event->addSnippet('@JsysSalesPeriod/admin/Product/ext_product_edit.twig');
    }

}
