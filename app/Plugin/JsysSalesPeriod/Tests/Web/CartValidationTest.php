<?php
namespace Plugin\JsysSalesPeriod\Tests\Web;

use Faker\Generator;
use Eccube\Entity\Customer;
use Eccube\Entity\Product;
use Eccube\Entity\ProductClass;
use Eccube\Tests\Web\AbstractWebTestCase;
use Plugin\JsysSalesPeriod\Repository\ConfigRepository;
use Plugin\JsysSalesPeriod\Entity\Config;

class CartValidationTest extends AbstractWebTestCase
{
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
     * 販売期間内商品のカート追加成功を確認
     */
    public function testCartIn()
    {
        // 設定・商品・会員を作成
        $this->createConfig();
        $Product  = $this->createProduct();
        $Customer = $this->createCustomer();

        // 商品詳細ページへアクセス
        $this->client->request('GET', $this->generateUrl('product_detail', [
            'id' => $Product->getId(),
        ]));
        $this->assertTrue($this->client->getResponse()->isSuccessful());

        // 商品の販売期間が期間内になるように更新
        $now    = new \DateTimeImmutable();
        $start  = $now->modify('-1 day');
        $finish = $now->modify('+1 day');
        $this->changeSalesPeriod($Product, $start, $finish, true, true);

        // カートへ追加
        $this->scenarioCartIn($Customer, $Product->getProductClasses()->first());
        $crawler = $this->client->followRedirect();
        $this->assertTrue($this->client->getResponse()->isSuccessful());

        // カート画面に商品が追加されているか
        $this->assertContains(
            'ショッピングカート',
            $crawler->filter('.ec-pageHeader h1')->text()
        );
        $this->assertContains(
            $Product->getName(),
            $crawler->filter('.ec-cartTable')->text()
        );
    }

    /**
     * 販売期間外で期間外表示をしない商品のカート追加失敗を確認
     */
    public function testCartInWithOutNotDisplay()
    {
        // 設定・商品・会員を作成
        $this->createConfig();
        $Product  = $this->createProduct();
        $Customer = $this->createCustomer();

        // 商品詳細ページへアクセス
        $this->client->request('GET', $this->generateUrl('product_detail', [
            'id' => $Product->getId(),
        ]));
        $this->assertTrue($this->client->getResponse()->isSuccessful());

        // 商品の販売期間が期間外、期間外表示無効になるように更新
        $now    = new \DateTimeImmutable();
        $start  = $now->modify('+1 day');
        $finish = $now->modify('+2 day');
        $this->changeSalesPeriod($Product, $start, $finish, false, false);

        // カートへ追加、失敗するか
        $this->scenarioCartIn($Customer, $Product->getProductClasses()->first());
        $this->assertFalse($this->client->getResponse()->isSuccessful());
        $this->assertSame(404, $this->client->getResponse()->getStatusCode());
    }

    /**
     * 販売期間外で期間外表示をする商品のカート追加失敗を確認
     */
    public function testCartInWithOutDisplay()
    {
        // 設定・商品・会員を作成
        $this->createConfig();
        $Product  = $this->createProduct();
        $Customer = $this->createCustomer();

        // 商品詳細ページへアクセス
        $this->client->request('GET', $this->generateUrl('product_detail', [
            'id' => $Product->getId(),
        ]));
        $this->assertTrue($this->client->getResponse()->isSuccessful());

        // 商品の販売期間が期間外、期間外表示無効になるように更新
        $now    = new \DateTimeImmutable();
        $start  = $now->modify('+1 day');
        $finish = $now->modify('+2 day');
        $this->changeSalesPeriod($Product, $start, $finish, true, true);

        // カートへ追加、失敗するか
        $this->scenarioCartIn($Customer, $Product->getProductClasses()->first());
        $this->assertFalse($this->client->getResponse()->isSuccessful());
        $this->assertSame(404, $this->client->getResponse()->getStatusCode());
    }

    /**
     * 販売期間外が矛盾している商品のカート追加失敗を確認
     */
    public function testCartInWithContradiction()
    {
        // 設定・商品・会員を作成
        $this->createConfig();
        $Product  = $this->createProduct();
        $Customer = $this->createCustomer();

        // 商品詳細ページへアクセス
        $this->client->request('GET', $this->generateUrl('product_detail', [
            'id' => $Product->getId(),
        ]));
        $this->assertTrue($this->client->getResponse()->isSuccessful());

        // 商品の販売期間が期間外、期間外表示無効になるように更新
        $now    = new \DateTimeImmutable();
        $start  = $now->modify('+1 day');
        $finish = $now->modify('-1 day');
        $this->changeSalesPeriod($Product, $start, $finish, true, true);

        // カートへ追加、失敗するか
        $this->scenarioCartIn($Customer, $Product->getProductClasses()->first());
        $this->assertFalse($this->client->getResponse()->isSuccessful());
        $this->assertSame(404, $this->client->getResponse()->getStatusCode());
    }

    /**
     * 販売期間内商品の注文手続き画面遷移成功を確認
     */
    public function testConfirm()
    {
        // 設定・商品・会員を作成
        $this->createConfig();
        $Product  = $this->createProduct();
        $Customer = $this->createCustomer();

        // 商品詳細ページへアクセス
        $this->client->request('GET', $this->generateUrl('product_detail', [
            'id' => $Product->getId(),
        ]));
        $this->assertTrue($this->client->getResponse()->isSuccessful());

        // カートへ追加
        $ProductClass = $Product->getProductClasses()->first();
        $this->scenarioCartIn($Customer, $ProductClass);

        // 商品の販売期間が期間内になるように更新
        $now    = new \DateTimeImmutable();
        $start  = $now->modify('-1 day');
        $finish = $now->modify('+1 day');
        $this->changeSalesPeriod($Product, $start, $finish, true, true);

        // 手続き画面へ遷移
        $this->scenarioConfirm($Customer, $ProductClass);
        $crawler = $this->client->followRedirect();
        $this->assertTrue($this->client->getResponse()->isSuccessful());
        $this->assertContains('ご注文手続き', $crawler->filter('.ec-pageHeader h1')->text());
    }

    /**
     * 販売期間外で期間外表示をしない商品の注文手続き画面遷移失敗を確認
     */
    public function testConfirmWithOutNotDisplay()
    {
        // 設定・商品・会員を作成
        $this->createConfig();
        $Product  = $this->createProduct();
        $Customer = $this->createCustomer();

        // 商品詳細ページへアクセス
        $this->client->request('GET', $this->generateUrl('product_detail', [
            'id' => $Product->getId(),
        ]));
        $this->assertTrue($this->client->getResponse()->isSuccessful());

        // カートへ追加
        $ProductClass = $Product->getProductClasses()->first();
        $this->scenarioCartIn($Customer, $ProductClass);

        // 商品の販売期間が期間外、期間外表示無効になるように更新
        $now    = new \DateTimeImmutable();
        $start  = $now->modify('+1 day');
        $finish = $now->modify('+2 day');
        $this->changeSalesPeriod($Product, $start, $finish, false, false);

        // 手続き画面へ遷移
        $this->scenarioConfirm($Customer, $ProductClass);
        $this->client->followRedirect();
        $crawler = $this->client->followRedirect();

        $this->assertContains(
            'ご注文手続きが正常に完了しませんでした。大変お手数ですが、再度ご注文手続きをお願いします。',
            $crawler->filter('.ec-layoutRole__main')->text()
        );
    }

    /**
     * 販売期間外で期間外表示をする商品の注文手続き画面遷移失敗を確認
     */
    public function testConfirmWithOutDisplay()
    {
        // 設定・商品・会員を作成
        $this->createConfig();
        $Product  = $this->createProduct();
        $Customer = $this->createCustomer();

        // 商品詳細ページへアクセス
        $this->client->request('GET', $this->generateUrl('product_detail', [
            'id' => $Product->getId(),
        ]));
        $this->assertTrue($this->client->getResponse()->isSuccessful());

        // カートへ追加
        $ProductClass = $Product->getProductClasses()->first();
        $this->scenarioCartIn($Customer, $ProductClass);

        // 商品の販売期間が期間外、期間外表示有効になるように更新
        $now    = new \DateTimeImmutable();
        $start  = $now->modify('+1 day');
        $finish = $now->modify('+2 day');
        $this->changeSalesPeriod($Product, $start, $finish, true, true);

        // 手続き画面へ遷移
        $this->scenarioConfirm($Customer, $ProductClass);
        $this->client->followRedirect();
        $crawler = $this->client->followRedirect();

        $this->assertContains(
            'ご注文手続きが正常に完了しませんでした。大変お手数ですが、再度ご注文手続きをお願いします。',
            $crawler->filter('.ec-layoutRole__main')->text()
        );
    }

    /**
     * 販売期間外が矛盾している商品の注文手続き画面遷移失敗を確認
     */
    public function testConfirmInWithContradiction()
    {
        // 設定・商品・会員を作成
        $this->createConfig();
        $Product  = $this->createProduct();
        $Customer = $this->createCustomer();

        // 商品詳細ページへアクセス
        $this->client->request('GET', $this->generateUrl('product_detail', [
            'id' => $Product->getId(),
        ]));
        $this->assertTrue($this->client->getResponse()->isSuccessful());

        // カートへ追加
        $ProductClass = $Product->getProductClasses()->first();
        $this->scenarioCartIn($Customer, $ProductClass);

        // 商品の販売期間が矛盾、期間外表示有効になるように更新
        $now    = new \DateTimeImmutable();
        $start  = $now->modify('+1 day');
        $finish = $now->modify('-1 day');
        $this->changeSalesPeriod($Product, $start, $finish, true, true);

        // 手続き画面へ遷移
        $this->scenarioConfirm($Customer, $ProductClass);
        $this->client->followRedirect();
        $crawler = $this->client->followRedirect();

        $this->assertContains(
            'ご注文手続きが正常に完了しませんでした。大変お手数ですが、再度ご注文手続きをお願いします。',
            $crawler->filter('.ec-layoutRole__main')->text()
        );
    }

    /**
     * 販売期間内商品の注文確認画面遷移成功を確認
     */
    public function testComplete()
    {
        // 設定・商品・会員を作成
        $this->createConfig();
        $Product  = $this->createProduct();
        $Customer = $this->createCustomer();

        // 商品詳細ページへアクセス
        $this->client->request('GET', $this->generateUrl('product_detail', [
            'id' => $Product->getId(),
        ]));
        $this->assertTrue($this->client->getResponse()->isSuccessful());

        // カートへ追加
        $ProductClass = $Product->getProductClasses()->first();
        $this->scenarioCartIn($Customer, $ProductClass);

        // 手続き画面へ遷移
        $this->scenarioConfirm($Customer, $ProductClass);
        $this->client->followRedirect();

        // 商品の販売期間が期間内になるように更新
        $now    = new \DateTimeImmutable();
        $start  = $now->modify('-1 day');
        $finish = $now->modify('+1 day');
        $this->changeSalesPeriod($Product, $start, $finish, true, true);

        // 完了画面へ遷移
        $crawler = $this->scenarioComplete($Customer);
        $this->assertTrue($this->client->getResponse()->isSuccessful());
        $this->assertContains(
            'ご注文内容のご確認',
            $crawler->filter('.ec-pageHeader h1')->text()
        );
    }

    /**
     * 販売期間外で期間外表示をしない商品の注文確認画面遷移失敗を確認
     */
    public function testCompleteWithOutNotDisplay()
    {
        // 設定・商品・会員を作成
        $this->createConfig();
        $Product  = $this->createProduct();
        $Customer = $this->createCustomer();

        // 商品詳細ページへアクセス
        $this->client->request('GET', $this->generateUrl('product_detail', [
            'id' => $Product->getId(),
        ]));
        $this->assertTrue($this->client->getResponse()->isSuccessful());

        // カートへ追加
        $ProductClass = $Product->getProductClasses()->first();
        $this->scenarioCartIn($Customer, $ProductClass);

        // 手続き画面へ遷移
        $this->scenarioConfirm($Customer, $ProductClass);
        $this->client->followRedirect();

        // 商品の販売期間が期間外、期間外表示無効になるように更新
        $now    = new \DateTimeImmutable();
        $start  = $now->modify('+1 day');
        $finish = $now->modify('+2 day');
        $this->changeSalesPeriod($Product, $start, $finish, false, false);

        // 完了画面へ遷移
        $this->scenarioComplete($Customer);
        $crawler = $this->client->followRedirect();

        $this->assertContains(
            'ご注文手続きが正常に完了しませんでした。大変お手数ですが、再度ご注文手続きをお願いします。',
            $crawler->filter('.ec-layoutRole__main')->text()
        );
    }

    /**
     * 販売期間外で期間外表示をする商品の注文確認画面遷移失敗を確認
     */
    public function testCompleteWithOutDisplay()
    {
        // 設定・商品・会員を作成
        $this->createConfig();
        $Product  = $this->createProduct();
        $Customer = $this->createCustomer();

        // 商品詳細ページへアクセス
        $this->client->request('GET', $this->generateUrl('product_detail', [
            'id' => $Product->getId(),
        ]));
        $this->assertTrue($this->client->getResponse()->isSuccessful());

        // カートへ追加
        $ProductClass = $Product->getProductClasses()->first();
        $this->scenarioCartIn($Customer, $ProductClass);

        // 手続き画面へ遷移
        $this->scenarioConfirm($Customer, $ProductClass);
        $this->client->followRedirect();

        // 商品の販売期間が期間外、期間外表示有効になるように更新
        $now    = new \DateTimeImmutable();
        $start  = $now->modify('+1 day');
        $finish = $now->modify('+2 day');
        $this->changeSalesPeriod($Product, $start, $finish, true, true);

        // 完了画面へ遷移
        $this->scenarioComplete($Customer);
        $crawler = $this->client->followRedirect();

        $this->assertContains(
            'ご注文手続きが正常に完了しませんでした。大変お手数ですが、再度ご注文手続きをお願いします。',
            $crawler->filter('.ec-layoutRole__main')->text()
        );
    }

    /**
     * 販売期間外が矛盾している商品の注文確認画面遷移失敗を確認
     */
    public function testCompleteInWithContradiction()
    {
        // 設定・商品・会員を作成
        $this->createConfig();
        $Product  = $this->createProduct();
        $Customer = $this->createCustomer();

        // 商品詳細ページへアクセス
        $this->client->request('GET', $this->generateUrl('product_detail', [
            'id' => $Product->getId(),
        ]));
        $this->assertTrue($this->client->getResponse()->isSuccessful());

        // カートへ追加
        $ProductClass = $Product->getProductClasses()->first();
        $this->scenarioCartIn($Customer, $ProductClass);

        // 手続き画面へ遷移
        $this->scenarioConfirm($Customer, $ProductClass);
        $this->client->followRedirect();

        // 商品の販売期間が矛盾、期間外表示有効になるように更新
        $now    = new \DateTimeImmutable();
        $start  = $now->modify('+1 day');
        $finish = $now->modify('-1 day');
        $this->changeSalesPeriod($Product, $start, $finish, true, true);

        // 完了画面へ遷移
        $this->scenarioComplete($Customer);
        $crawler = $this->client->followRedirect();

        $this->assertContains(
            'ご注文手続きが正常に完了しませんでした。大変お手数ですが、再度ご注文手続きをお願いします。',
            $crawler->filter('.ec-layoutRole__main')->text()
        );
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
     * 販売期間を更新します。
     * @param Product $Product
     * @param \DateTime|NULL $start
     * @param \DateTime|NULL $finish
     * @param boolean $before
     * @param boolean $finished
     * @return \Eccube\Entity\Product
     */
    private function changeSalesPeriod(
        Product $Product,
        $start,
        $finish,
        $before = false,
        $finished = false
    ) {
        $Product = $this->entityManager->find(Product::class, $Product->getId());
        $Product
            ->setJsysSalesPeriodSaleStart($start)
            ->setJsysSalesPeriodSaleFinish($finish)
            ->setJsysSalesPeriodDisplayBeforeSale($before)
            ->setJsysSalesPeriodDisplayFinished($finished);

        $this->entityManager->persist($Product);
        $this->entityManager->flush();

        return $Product;
    }

    /**
     * 商品をカートに追加します。
     * @param Customer $Customer
     * @param ProductClass $ProductClass
     * @return \Symfony\Component\DomCrawler\Crawler
     */
    protected function scenarioCartIn(Customer $Customer, ProductClass $ProductClass)
    {
        $this->loginTo($Customer);

        $id = $ProductClass->getProduct()->getId();
        return $this->client->request(
            'POST',
            $this->generateUrl('product_add_cart', ['id' => $id]),
            [
                'ProductClass' => $ProductClass->getId(),
                'quantity'     => 1,
                'product_id'   => $id,
                '_token'       => 'dummy',
            ]
        );
    }

    /**
     * 注文手続き画面へ遷移します。
     * @param Customer $Customer
     * @param ProductClass $ProductClass
     * @return \Symfony\Component\DomCrawler\Crawler
     */
    protected function scenarioConfirm(Customer $Customer, ProductClass $ProductClass)
    {
        $this->loginTo($Customer);

        $cart_key = $Customer->getId() . '_' . $ProductClass->getSaleType()->getId();
        return $this->client->request('GET', $this->generateUrl('cart_buystep', [
            'cart_key' => $cart_key,
        ]));
    }

    /**
     * 注文確認画面へ遷移します。
     * @param Customer $Customer
     * @return \Symfony\Component\DomCrawler\Crawler
     */
    protected function scenarioComplete(Customer $Customer)
    {
        $this->loginTo($Customer);

        $this->client->enableProfiler();

        return $this->client->request(
            'POST',
            $this->generateUrl('shopping_confirm'),
            [
                '_shopping_order' => [
                    'Shippings' => [
                        [
                            'Delivery' => 1,
                            'DeliveryTime' => 1,
                        ],
                    ],
                    'Payment'   => 3,
                    'message'   => $this->faker->realText(),
                    'use_point' => 0,
                    '_token'    => 'dummy',
                ]
            ]
        );
    }

}
