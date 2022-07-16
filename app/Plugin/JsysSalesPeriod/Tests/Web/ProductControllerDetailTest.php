<?php
namespace Plugin\JsysSalesPeriod\Tests\Web;

use Faker\Generator;
use Symfony\Component\DomCrawler\Crawler;
use Eccube\Entity\Product;
use Eccube\Tests\Web\AbstractWebTestCase;
use Plugin\JsysSalesPeriod\Entity\Config;
use Plugin\JsysSalesPeriod\Repository\ConfigRepository;

class ProductControllerDetailTest extends AbstractWebTestCase
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
     * 販売期間内の表示を確認
     */
    public function testShow()
    {
        $now    = new \DateTimeImmutable();
        $past   = $now->modify('-1 day');
        $future = $now->modify('+1 day');

        // 設定を作成
        $this->createConfig();
        // 表示されるパターンの商品を作成
        $Products = [
            // 期間未設定
            $this->createTestProduct(),
            // 終了日が未来
            $this->createTestProduct(null, $future),
            // 開始日が過去
            $this->createTestProduct($past),
            // 開始日が過去、終了日が未来
            $this->createTestProduct($past, $future),
        ];

        // 各パターンをテスト
        foreach ($Products as $key => $Product) {
            $message = 'key=>' . $key;

            // 商品詳細へアクセス
            $crawler = $this->client->request('GET', $this->generateUrl('product_detail', [
                'id' => $Product->getId(),
            ]));
            $this->assertTrue($this->client->getResponse()->isSuccessful(), $message);

            // 商品名が存在するか
            $this->assertContains(
                $Product->getName(),
                $crawler->filter('div.ec-productRole__title h2.ec-headingTitle')->text(),
                $message
            );
            // 「カートに入れる」が存在するか
            $this->assertContains(
                'カートに入れる',
                $crawler->filter('#form1')->html(),
                $message
            );
        }
    }

    /**
     * 販売期間外の非表示を確認
     */
    public function testNotFound()
    {
        $now       = new \DateTimeImmutable();
        $past      = $now->modify('-1 day');
        $future    = $now->modify('+1 day');
        $farPast   = $past->modify('-1 day');
        $farFuture = $future->modify('+1 day');

        // 設定を作成
        $this->createConfig();
        // 表示されるパターンの商品を作成
        $Products = [
            // 終了日が過去
            $this->createTestProduct(null, $past),
            // 開始日が未来
            $this->createTestProduct($future),
            // 開始日・終了日が未来
            $this->createTestProduct($future, $farFuture),
            // 開始日・終了日が過去
            $this->createTestProduct($farPast, $past),
        ];

        // 各パターンをテスト
        foreach ($Products as $key => $Product) {
            $message = 'key=>' . $key;

            // 商品詳細へアクセス、404で失敗するか
            $this->client->request('GET', $this->generateUrl('product_detail', [
                'id' => $Product->getId(),
            ]));
            $this->assertFalse($this->client->getResponse()->isSuccessful(), $message);
            $this->assertSame(404, $this->client->getResponse()->getStatusCode(), $message);
        }
    }

    /**
     * 販売前・終了後にも表示する商品の表示とカートボタンの置き換えを確認
     */
    public function testReplacedButton()
    {
        $now       = new \DateTimeImmutable();
        $past      = $now->modify('-1 day');
        $future    = $now->modify('+1 day');
        $farPast   = $past->modify('-1 day');
        $farFuture = $future->modify('+1 day');

        // 設定を作成
        $this->createConfig();
        // 表示されるパターンの商品を作成
        $before   = '.replaceWith($before)';
        $finished = '.replaceWith($finished)';
        $Products = [
            // 終了日が過去：販売終了
            [$this->createTestProduct(null, $past, false, true), $finished],
            // 終了日が未来：販売中
            [$this->createTestProduct(null, $future, false, true), null],
            // 開始日が未来：販売前
            [$this->createTestProduct($future, null, true, false), $before],
            // 開始・終了日が未来：販売前
            [$this->createTestProduct($future, $farFuture, true, true), $before],
            // 開始日が過去：販売中
            [$this->createTestProduct($past, null, true, false), null],
            // 開始・終了日が過去：販売終了
            [$this->createTestProduct($farPast, $past, true, true), $finished],
            // 開始日が過去、終了日が未来：販売中
            [$this->createTestProduct($past, $future, true, true), null],
        ];

        // 各パターンをテスト
        foreach ($Products as $key => $data) {
            $message = 'key=>' . $key;
            $Product = $data[0];
            $button  = $data[1];

            // 商品詳細へアクセス
            $crawler = $this->client->request('GET', $this->generateUrl('product_detail', [
                'id' => $Product->getId(),
            ]));
            $this->assertTrue($this->client->getResponse()->isSuccessful(), $message);

            // 商品名が存在するか
            $this->assertContains(
                $Product->getName(),
                $crawler->filter('div.ec-productRole__title h2.ec-headingTitle')->text(),
                $message
            );
            // ボタン置き換えjsを確認
            $js = $this->getReplaceJs($Product, $crawler);
            if (!$button) {
                $this->assertEmpty($js, $message);
            } else {
                $this->assertContains($button, $js, $message);
            }
        }
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
        // 表示されるパターンの商品を作成
        $before   = '.replaceWith($before)';
        $finished = '.replaceWith($finished)';
        $Products = [
            // 終了日が過去：販売終了
            [$this->createTestProduct(null, $past, false, true, 1, self::ALL), $finished],
            // 終了日が未来：販売中
            [$this->createTestProduct(null, $future, false, true, 1, self::ALL), null],
            // 開始日が未来：販売前
            [$this->createTestProduct($future, null, true, false, 1, self::ALL), $before],
            // 開始・終了日が未来：販売前
            [
                $this->createTestProduct($future, $farFuture, true, true, 1, self::ALL),
                $before,
            ],
            // 開始日が過去：販売中
            [$this->createTestProduct($past, null, true, false, 1, self::ALL), null],
            // 開始・終了日が過去：販売終了
            [$this->createTestProduct($farPast, $past, true, true, 1, self::ALL), $finished],
            // 開始日が過去、終了日が未来：販売中
            [$this->createTestProduct($past, $future, true, true, 1, self::ALL), null],
        ];

        // 各パターンをテスト
        foreach ($Products as $key => $data) {
            $message = 'key=>' . $key;
            $Product = $data[0];
            $button  = $data[1];

            // 商品詳細へアクセス
            $crawler = $this->client->request('GET', $this->generateUrl('product_detail', [
                'id' => $Product->getId(),
            ]));
            $this->assertTrue($this->client->getResponse()->isSuccessful(), $message);

            // 商品名が存在するか
            $this->assertContains(
                $Product->getName(),
                $crawler->filter('div.ec-productRole__title h2.ec-headingTitle')->text(),
                $message
            );
            // ボタン置き換えjsを確認
            $js = $this->getReplaceJs($Product, $crawler);
            if (!$button) {
                $this->assertEmpty($js, $message);
            } else {
                $this->assertContains($button, $js, $message);
            }
        }
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
        // 表示されるパターンの商品を作成
        $before   = '.replaceWith($before)';
        $finished = '.replaceWith($finished)';
        $Products = [
            // 開始日が未来：販売前
            [$this->createTestProduct($future, null, true, false, 3, self::PARTLY), $before],
            // 開始日が過去：販売中
            [$this->createTestProduct($past, null, true, false, 3, self::PARTLY), null],
            // 開始・終了日が過去：販売終了
            [
                $this->createTestProduct($farPast, $past, true, true, 3, self::PARTLY),
                $finished,
            ],
            // 開始・終了日が未来：販売前
            [
                $this->createTestProduct($future, $farFuture, true, true, 3, self::PARTLY),
                $before,
            ],
            // 開始日が過去、終了日が未来：販売中
            [$this->createTestProduct($past, $future, true, true, 3, self::PARTLY), null],
            // 終了日が過去：販売終了
            [$this->createTestProduct(null, $past, false, true, 3, self::PARTLY), $finished],
        ];

        // 各パターンをテスト
        foreach ($Products as $key => $data) {
            $message = 'key=>' . $key;
            $Product = $data[0];
            $button  = $data[1];

            // 商品詳細へアクセス
            $crawler = $this->client->request('GET', $this->generateUrl('product_detail', [
                'id' => $Product->getId(),
            ]));
            $this->assertTrue($this->client->getResponse()->isSuccessful(), $message);

            // 商品名が存在するか
            $this->assertContains(
                $Product->getName(),
                $crawler->filter('div.ec-productRole__title h2.ec-headingTitle')->text(),
                $message
            );
            // ボタン置き換えjsを確認
            $js = $this->getReplaceJs($Product, $crawler);
            if (!$button) {
                $this->assertEmpty($js, $message);
            } else {
                $this->assertContains($button, $js, $message);
            }
        }
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
        // 表示されるパターンの商品を作成
        $before   = '.replaceWith($before)';
        $finished = '.replaceWith($finished)';
        $Products = [
            // 開始日が過去、終了日が開始日より過去：終了後判定
            [$this->createTestProduct($past, $farPast, true, true), $finished],
            // 開始日が終了日より未来、終了日が未来：販売前判定
            [$this->createTestProduct($farFuture, $future, true, true), $before],
            // 開始日が未来、終了日が過去：販売前判定
            [$this->createTestProduct($future, $past, true, true), $before],
        ];

        // 各パターンをテスト
        foreach ($Products as $key => $data) {
            $message = 'key=>' . $key;
            $Product = $data[0];
            $button  = $data[1];

            // 商品詳細へアクセス
            $crawler = $this->client->request('GET', $this->generateUrl('product_detail', [
                'id' => $Product->getId(),
            ]));
            $this->assertTrue($this->client->getResponse()->isSuccessful(), $message);

            // 商品名が存在するか
            $this->assertContains(
                $Product->getName(),
                $crawler->filter('div.ec-productRole__title h2.ec-headingTitle')->text(),
                $message
            );
            // ボタン置き換えjsを確認
            $this->assertContains(
                $button,
                $this->getReplaceJs($Product, $crawler),
                $message
            );
        }
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
        $head    = $crawler->filter('head')->html();
        $pattern = '~\$\(\'#form1 div\.ec-productRole__btn button\[type="(.*?);~s';
        $matches = [];
        $result  = preg_match($pattern, $head, $matches);
        if (false === $result) {
            throw new \Exception('preg_match error.');
        }
        return empty($matches[0]) ? '' : $matches[0];
    }

}
