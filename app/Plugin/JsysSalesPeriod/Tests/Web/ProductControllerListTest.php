<?php
namespace Plugin\JsysSalesPeriod\Tests\Web;

use Faker\Generator;
use Symfony\Component\DomCrawler\Crawler;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Eccube\Entity\Product;
use Eccube\Tests\Web\AbstractWebTestCase;
use Plugin\JsysSalesPeriod\Entity\Config;
use Plugin\JsysSalesPeriod\Repository\ConfigRepository;

class ProductControllerListTest extends AbstractWebTestCase
{
    /**
     * 在庫無制限
     * @var int
     */
    const UNLIMITED = 0;

    /**
     * 一部だけ在庫0
     * @var int
     */
    const PARTLY = 1;

    /**
     * 全て在庫0
     * @var int
     */
    const ALL = 2;


    /**
     * @var Generator
     */
    protected $faker;

    /**
     * @var ConfigRepository
     */
    protected $configRepo;


    /**
     * Setup method.
     * {@inheritDoc}
     * @see \Eccube\Tests\Web\AbstractWebTestCase::setUp()
     */
    public function setUp()
    {
        parent::setUp();

        $this->faker      = $this->getFaker();
        $this->configRepo = $this->container->get(ConfigRepository::class);
    }

    /**
     * 販売期間での表示・非表示を確認
     */
    public function testHideAndShow()
    {
        $now    = new \DateTimeImmutable();
        $past   = $now->modify('-1 day');
        $future = $now->modify('+1 day');

        // 設定を作成
        $this->createConfig();
        // 各種パターンの商品を作成
        // 期間未設定：販売中
        $proA = $this->createTestProduct();
        // 終了日が過去：販売終了
        $proB = $this->createTestProduct(null, $past);
        // 終了日が未来：販売中
        $proC = $this->createTestProduct(null, $future);
        // 開始日が未来：販売前
        $proD = $this->createTestProduct($future);
        // 開始・終了日が未来：販売前
        $proE = $this->createTestProduct($future, $future->modify('+1 day'));
        // 開始日が過去：販売中
        $proF = $this->createTestProduct($past);
        // 開始・終了日が過去：販売終了
        $proG = $this->createTestProduct($past->modify('-1 day'), $past);
        // 開始日が過去、終了日が未来：販売中
        $proH = $this->createTestProduct($past, $future);

        // 商品一覧へアクセス
        $crawler = $this->client->request('GET', $this->generateUrl('product_list'));
        $this->assertTrue($this->client->getResponse()->isSuccessful());

        // 商品名で表示非表示を確認
        $html = $crawler->filter('ul.ec-shelfGrid')->html();
        $this->assertContains($proA->getName(), $html);
        $this->assertNotContains($proB->getName(), $html);
        $this->assertContains($proC->getName(), $html);
        $this->assertNotContains($proD->getName(), $html);
        $this->assertNotContains($proE->getName(), $html);
        $this->assertContains($proF->getName(), $html);
        $this->assertNotContains($proG->getName(), $html);
        $this->assertContains($proH->getName(), $html);
    }

    /**
     * 販売前・終了後にも表示する商品の表示とカートボタンの置き換えを確認
     */
    public function testReplacedButton()
    {
        $now    = new \DateTimeImmutable();
        $past   = $now->modify('-1 day');
        $future = $now->modify('+1 day');

        // 設定を作成
        $this->createConfig();
        // 各種パターンの商品を作成
        // 終了日が過去：販売終了
        $proA = $this->createTestProduct(null, $past, false, true);
        // 終了日が未来：販売中
        $proB = $this->createTestProduct(null, $future, false, true);
        // 開始日が未来：販売前
        $proC = $this->createTestProduct($future, null, true, false);
        // 開始・終了日が未来：販売前
        $proD = $this->createTestProduct($future, $future->modify('+1 day'), true, true);
        // 開始日が過去：販売中
        $proE = $this->createTestProduct($past, null, true, false);
        // 開始・終了日が過去：販売終了
        $proF = $this->createTestProduct($past->modify('-1 day'), $past, true, true);
        // 開始日が過去、終了日が未来：販売中
        $proG = $this->createTestProduct($past, $future, true, true);

        // 商品一覧へアクセス
        $crawler = $this->client->request('GET', $this->generateUrl('product_list'));
        $this->assertTrue($this->client->getResponse()->isSuccessful());

        // 各商品のカートインボタンの置き換えjsを確認
        $before   = '.replaceWith($before)';
        $finished = '.replaceWith($finished)';
        $this->assertContains($finished, $this->getReplaceJs($proA, $crawler));
        $this->assertEmpty($this->getReplaceJs($proB, $crawler));
        $this->assertContains($before, $this->getReplaceJs($proC, $crawler));
        $this->assertContains($before, $this->getReplaceJs($proD, $crawler));
        $this->assertEmpty($this->getReplaceJs($proE, $crawler));
        $this->assertContains($finished, $this->getReplaceJs($proF, $crawler));
        $this->assertEmpty($this->getReplaceJs($proG, $crawler));
    }

    /**
     * 販売前・終了後にも表示する商品が品切れの場合の表示とカートボタンの置き換えを確認
     */
    public function testZeroStockReplacedButton()
    {
        $now       = new \DateTimeImmutable();
        $past      = $now->modify('-1 day');
        $future    = $now->modify('+1 day');
        $farPast   = $past->modify('-1 day');
        $farFuture = $future->modify('+1 day');

        // 設定を作成
        $this->createConfig();
        // 各種パターンの商品を作成
        // 終了日が過去：販売終了
        $proA = $this->createTestProduct(null, $past, false, true, 1, self::ALL);
        // 終了日が未来：販売中
        $proB = $this->createTestProduct(null, $future, false, true, 1, self::ALL);
        // 開始日が未来：販売前
        $proC = $this->createTestProduct($future, null, true, false, 1, self::ALL);
        // 開始・終了日が未来：販売前
        $proD = $this->createTestProduct($future, $farFuture, true, true, 1, self::ALL);
        // 開始日が過去：販売中
        $proE = $this->createTestProduct($past, null, true, false, 1, self::ALL);
        // 開始・終了日が過去：販売終了
        $proF = $this->createTestProduct($farPast, $past, true, true, 1, self::ALL);
        // 開始日が過去、終了日が未来：販売中
        $proG = $this->createTestProduct($past, $future, true, true, 1, self::ALL);

        // 商品一覧へアクセス
        $crawler = $this->client->request('GET', $this->generateUrl('product_list'));
        $this->assertTrue($this->client->getResponse()->isSuccessful());

        // 各商品のカートインボタンの置き換えjsを確認
        $before   = '.replaceWith($before)';
        $finished = '.replaceWith($finished)';
        $this->assertContains($finished, $this->getReplaceJs($proA, $crawler));
        $this->assertEmpty($this->getReplaceJs($proB, $crawler));
        $this->assertContains($before, $this->getReplaceJs($proC, $crawler));
        $this->assertContains($before, $this->getReplaceJs($proD, $crawler));
        $this->assertEmpty($this->getReplaceJs($proE, $crawler));
        $this->assertContains($finished, $this->getReplaceJs($proF, $crawler));
        $this->assertEmpty($this->getReplaceJs($proG, $crawler));
    }

    /**
     * 規格が複数あり、一部または全てが品切れの場合の表示とカートボタン置き換えを確認
     */
    public function testProductClassReplacedButton()
    {
        $now       = new \DateTimeImmutable();
        $past      = $now->modify('-1 day');
        $future    = $now->modify('+1 day');
        $farPast   = $past->modify('-1 day');
        $farFuture = $future->modify('+1 day');

        // 設定を作成
        $this->createConfig();
        // 各種パターンの商品を作成
        // 開始日が未来：販売前
        $proA = $this->createTestProduct($future, null, true, false, 3, self::PARTLY);
        // 開始日が過去：販売中
        $proB = $this->createTestProduct($past, null, true, false, 3, self::PARTLY);
        // 開始・終了日が過去：販売終了
        $proC = $this->createTestProduct($farPast, $past, true, true, 3, self::PARTLY);
        // 開始・終了日が未来：販売前
        $proD = $this->createTestProduct($future, $farFuture, true, true, 3, self::ALL);
        // 開始日が過去、終了日が未来：販売中
        $proE = $this->createTestProduct($past, $future, true, true, 3, self::ALL);
        // 終了日が過去：販売終了
        $proF = $this->createTestProduct(null, $past, false, true, 3, self::ALL);

        // 商品一覧へアクセス
        $crawler = $this->client->request('GET', $this->generateUrl('product_list'));
        $this->assertTrue($this->client->getResponse()->isSuccessful());

        // 各商品のカートインボタンの置き換えjsを確認
        $before   = '.replaceWith($before)';
        $finished = '.replaceWith($finished)';
        $this->assertContains($before, $this->getReplaceJs($proA, $crawler));
        $this->assertEmpty($this->getReplaceJs($proB, $crawler));
        $this->assertContains($finished, $this->getReplaceJs($proC, $crawler));
        $this->assertContains($before, $this->getReplaceJs($proD, $crawler));
        $this->assertEmpty($this->getReplaceJs($proE, $crawler));
        $this->assertContains($finished, $this->getReplaceJs($proF, $crawler));
    }

    /**
     * 何かが原因で販売期間が逆転した場合にボタンが販売前か終了後になるか確認
     */
    public function testContradictionReplacedButton()
    {
        $now       = new \DateTimeImmutable();
        $past      = $now->modify('-1 day');
        $future    = $now->modify('+1 day');
        $farPast   = $past->modify('-1 day');
        $farFuture = $future->modify('+1 day');

        // 設定を作成
        $this->createConfig();
        // 各種パターンの商品を作成
        // 開始日が過去、終了日が開始日より過去：終了後判定
        $proA = $this->createTestProduct($past, $farPast, true, true);
        // 開始日が終了日より未来、終了日が未来：販売前判定
        $proB = $this->createTestProduct($farFuture, $future, true, true);
        // 開始日が未来、終了日が過去：販売前判定
        $proC = $this->createTestProduct($future, $past, true, true);

        // 商品一覧へアクセス
        $crawler = $this->client->request('GET', $this->generateUrl('product_list'));
        $this->assertTrue($this->client->getResponse()->isSuccessful());

        // 各商品のカートインボタンの置き換えjsを確認
        $before   = '.replaceWith($before)';
        $finished = '.replaceWith($finished)';
        $this->assertContains($finished, $this->getReplaceJs($proA, $crawler));
        $this->assertContains($before, $this->getReplaceJs($proB, $crawler));
        $this->assertContains($before, $this->getReplaceJs($proC, $crawler));
    }


    /**
     * プラグイン設定を作成します。
     * @return \Plugin\JsysSalesPeriod\Entity\Config
     */
    private function createConfig()
    {
        $Config = $this->configRepo->get();
        if (!$Config) {
            $Config = new Config();
        }

        $Config
            ->setBtnstrBeforeSale($this->faker->word)
            ->setBtnstrFinished($this->faker->word);

        $this->entityManager->persist($Config);
        $this->entityManager->flush($Config);

        return $Config;
    }

    /**
     * テスト用商品を作成します。
     * @param \DateTime|NULL $start
     * @param \DateTime|NULL $finish
     * @param boolean $before
     * @param boolean $finished
     * @param int $productClassNum
     * @param int $stockType
     * @return \Eccube\Entity\Product
     */
    private function createTestProduct(
        $start = null,
        $finish = null,
        $before = false,
        $finished = false,
        $productClassNum = 1,
        $stockType = 0
    ) {
        // 商品を作成し、拡張カラムを設定
        $Product = $this->createProduct(null, $productClassNum);
        $Product
            ->setJsysSalesPeriodSaleStart($start)
            ->setJsysSalesPeriodSaleFinish($finish)
            ->setJsysSalesPeriodDisplayBeforeSale($before)
            ->setJsysSalesPeriodDisplayFinished($finished);

        // 無制限以外の指定があれば在庫に0を設定
        if (self::UNLIMITED != $stockType) {
            /** @var \Eccube\Entity\ProductClass[] $ProductClasses */
            $ProductClasses = $Product->getProductClasses();
            foreach ($ProductClasses as $key => $ProductClass) {
                // 一部在庫0で先頭以外ならループを抜ける
                if (self::PARTLY == $stockType && 0 != $key) {
                    break;
                }

                // 在庫0を適用
                $ProductClass
                    ->setStockUnlimited(false)
                    ->setStock(0);
                $ProductStock = $ProductClass->getProductStock();
                $ProductStock->setStock(0);
            }
        }
        $this->entityManager->flush();
        return $Product;
    }

    /**
     * ヘッダからボタン置き換え用のjsを取得します。
     * @param Product $Product
     * @param Crawler $crawler
     * @throws \Exception
     * @return string
     */
    private function getReplaceJs(Product $Product, $crawler)
    {
        $url = $this->generateUrl(
            'product_detail',
            ['id' => $Product->getId()],
            UrlGeneratorInterface::ABSOLUTE_URL
        );

        $head    = $crawler->filter('head')->html();
        $pattern = '~\$\(\'li\.ec-shelfGrid__item a\[href="' . $url . '"\]\'\)(.*?);~s';
        $matches = [];
        $result  = preg_match($pattern, $head, $matches);
        if (false === $result) {
            throw new \Exception('preg_match error.');
        }
        return empty($matches[0]) ? '' : $matches[0];
    }

}
