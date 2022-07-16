<?php
namespace Plugin\JsysSalesPeriod\Tests\Web\Admin;

use Faker\Generator;
use Eccube\Common\Constant;
use Eccube\Repository\ProductRepository;
use Eccube\Tests\Web\Admin\AbstractAdminWebTestCase;

class ProductControllerTest extends AbstractAdminWebTestCase
{
    /**
     * @var Generator
     */
    protected $faker;

    /**
     * @var ProductRepository
     */
    protected $productRepo;


    /**
     * Setup method.
     * {@inheritDoc}
     * @see \Eccube\Tests\Web\Admin\AbstractAdminWebTestCase::setUp()
     */
    public function setUp()
    {
        parent::setUp();

        $this->faker       = $this->getFaker();
        $this->productRepo = $this->container->get(ProductRepository::class);
    }

    /**
     * 画面表示
     */
    public function testDisplay()
    {
        // 商品を作成し、編集画面へアクセス
        $Product = $this->createProduct($this->faker->word, 0);
        $crawler = $this->client->request('GET', $this->generateUrl(
            'admin_product_product_edit',
            ['id' => $Product->getId()]
        ));
        $this->assertTrue($this->client->getResponse()->isSuccessful());

        // 販売期間エリアが存在するか
        $this->assertContains('jsys_sales_period_columns', $crawler->html());
        $area = $crawler->filter('#jsys_sales_period_columns')->html();

        // 開始日時が存在するか
        $this->assertContains(
            trans('jsys_sales_period.admin.product.edit.sale_start'),
            $area
        );
        $this->assertContains(
            'admin_product_jsys_sales_period_sale_start',
            $area
        );

        // 終了日時が存在するか
        $this->assertContains(
            trans('jsys_sales_period.admin.product.edit.sale_finish'),
            $area
        );
        $this->assertContains(
            'admin_product_jsys_sales_period_sale_finish',
            $area
        );

        // 販売前表示が存在するか
        $this->assertContains(
            trans('jsys_sales_period.admin.product.edit.display_before_sale'),
            $area
        );
        $this->assertContains(
            'admin_product_jsys_sales_period_display_before_sale',
            $area
        );

        // 終了後表示が存在するか
        $this->assertContains(
            trans('jsys_sales_period.admin.product.edit.display_finished'),
            $area
        );
        $this->assertContains(
            'admin_product_jsys_sales_period_display_finished',
            $area
        );
    }

    /**
     * 日付の検証
     */
    public function testValidationDate()
    {
        // フォームデータを作成
        $formData = array_merge($this->createFormData(), [
            'jsys_sales_period_sale_start'          => '1970/0',
            'jsys_sales_period_sale_finish'         => $this->faker->word,
            'jsys_sales_period_display_before_sale' => false,
            'jsys_sales_period_display_finished'    => false,
        ]);

        // 新規登録
        $crawler = $this->client->request(
            'POST',
            $this->generateUrl('admin_product_product_new'),
            ['admin_product' => $formData]
        );
        $this->assertTrue($this->client->getResponse()->isSuccessful());

        // 販売開始・終了日時にエラーが表示されているか
        $class  = 'span.invalid-feedback .form-error-message';
        $start  = "#admin_product_jsys_sales_period_sale_start + {$class}";
        $finish = "#admin_product_jsys_sales_period_sale_finish + {$class}";

        $this->expected = '有効な値ではありません。';
        $this->actual   = $crawler->filter($start)->text();
        $this->verify();

        $this->expected = '有効な値ではありません。';
        $this->actual   = $crawler->filter($finish)->text();
        $this->verify();
    }

    /**
     * 古すぎる日付の検証
     */
    public function testValidationPastDate()
    {
        // フォームデータを作成
        $formData = array_merge($this->createFormData(), [
            'jsys_sales_period_sale_start'          => '0123/01/01 10:00:20',
            'jsys_sales_period_sale_finish'         => '1969/12/31 23:59:59',
            'jsys_sales_period_display_before_sale' => false,
            'jsys_sales_period_display_finished'    => false,
        ]);

        // 新規登録
        $crawler = $this->client->request(
            'POST',
            $this->generateUrl('admin_product_product_new'),
            ['admin_product' => $formData]
        );
        $this->assertTrue($this->client->getResponse()->isSuccessful());

        // 販売開始・終了日時にエラーが表示されているか
        $class  = 'span.invalid-feedback .form-error-message';
        $start  = "#admin_product_jsys_sales_period_sale_start + {$class}";
        $finish = "#admin_product_jsys_sales_period_sale_finish + {$class}";

        $this->expected = trans('jsys_sales_period.admin.product.edit.error.date_too_old');
        $this->actual   = $crawler->filter($start)->text();
        $this->verify();

        $this->expected = trans('jsys_sales_period.admin.product.edit.error.date_too_old');
        $this->actual   = $crawler->filter($finish)->text();
        $this->verify();
    }

    /**
     * 矛盾した日付の検証
     */
    public function testValidationReversalDate()
    {
        // フォームデータを作成
        $formData = array_merge($this->createFormData(), [
            'jsys_sales_period_sale_start'          => '2021/06/10 10:00:01',
            'jsys_sales_period_sale_finish'         => '2021/06/10 10:00:00',
            'jsys_sales_period_display_before_sale' => false,
            'jsys_sales_period_display_finished'    => false,
        ]);

        // 新規登録
        $crawler = $this->client->request(
            'POST',
            $this->generateUrl('admin_product_product_new'),
            ['admin_product' => $formData]
        );
        $this->assertTrue($this->client->getResponse()->isSuccessful());

        // 販売開始・終了日時にエラーが表示されているか
        $finish  = '#admin_product_jsys_sales_period_sale_finish'
                 . ' + span.invalid-feedback .form-error-message';

        $this->expected = trans('jsys_sales_period.admin.product.edit.error.date_reversal');
        $this->actual   = $crawler->filter($finish)->text();
        $this->verify();
    }

    /**
     * 販売前・終了後表示の検証
     */
    public function testValidationEmptyDateDisplayEnable()
    {
        // フォームデータを作成
        $formData = array_merge($this->createFormData(), [
            'jsys_sales_period_sale_start'          => null,
            'jsys_sales_period_sale_finish'         => null,
            'jsys_sales_period_display_before_sale' => true,
            'jsys_sales_period_display_finished'    => true,
        ]);

        // 新規登録
        $crawler = $this->client->request(
            'POST',
            $this->generateUrl('admin_product_product_new'),
            ['admin_product' => $formData]
        );
        $this->assertTrue($this->client->getResponse()->isSuccessful());

        // 販売前・終了後表示にエラーが表示されているか
        $card     = '#jsys_sales_period_columns__contents .card-body';
        $class    = 'span.invalid-feedback .form-error-message';
        $before   = "{$card} .row:nth-child(3) {$class}";
        $finished = "{$card} .row:nth-child(4) {$class}";
        $error    = 'jsys_sales_period.admin.product.edit.error';

        $this->expected = trans("{$error}.sale_start_empty");
        $this->actual   = $crawler->filter($before)->text();
        $this->verify();

        $this->expected = trans("{$error}.sale_finish_empty");
        $this->actual   = $crawler->filter($finished)->text();
        $this->verify();
    }

    /**
     * 販売期間を設定せずに新規登録
     */
    public function testNewNotSetSalesPeriod()
    {
        // フォームデータを作成
        $start    = null;
        $finish   = null;
        $before   = false;
        $finished = false;
        $formData = array_merge($this->createFormData(), [
            'jsys_sales_period_sale_start'          => $start,
            'jsys_sales_period_sale_finish'         => $finish,
            'jsys_sales_period_display_before_sale' => $before,
            'jsys_sales_period_display_finished'    => $finished,
        ]);

        // 新規登録
        $this->client->request(
            'POST',
            $this->generateUrl('admin_product_product_new'),
            ['admin_product' => $formData]
        );
        $this->assertTrue($this->client->getResponse()->isRedirection());

        // URLから商品IDを取得
        $exp = explode('/', $this->client->getResponse()->getTargetUrl());
        $id  = $exp[count($exp) - 2];

        // 登録済みメッセージが表示されているか
        $crawler = $this->client->followRedirect();
        $this->assertContains('保存しました', $crawler->html());

        // 商品を取得
        $Product = $this->productRepo->find($id);
        $this->assertNotEmpty($Product);

        // 各種項目の値は正しいか
        $this->assertSame($start, $Product->getJsysSalesPeriodSaleStart());
        $this->assertSame($finish, $Product->getJsysSalesPeriodSaleFinish());
        $this->assertSame($before, $Product->getJsysSalesPeriodDisplayBeforeSale());
        $this->assertSame($finished, $Product->getJsysSalesPeriodDisplayFinished());
    }

    /**
     * 販売期間を設定して新規登録
     */
    public function testNewSalesPeriod()
    {
        // フォームデータを作成
        $start    = '2021/06/20 10:00:00';
        $finish   = '2021/06/30 20:00:00';
        $before   = true;
        $finished = true;
        $formData = array_merge($this->createFormData(), [
            'jsys_sales_period_sale_start'          => $start,
            'jsys_sales_period_sale_finish'         => $finish,
            'jsys_sales_period_display_before_sale' => $before,
            'jsys_sales_period_display_finished'    => $finished,
        ]);

        // 新規登録
        $this->client->request(
            'POST',
            $this->generateUrl('admin_product_product_new'),
            ['admin_product' => $formData]
        );
        $this->assertTrue($this->client->getResponse()->isRedirection());

        // URLから商品IDを取得
        $exp = explode('/', $this->client->getResponse()->getTargetUrl());
        $id  = $exp[count($exp) - 2];

        // 登録済みメッセージが表示されているか
        $crawler = $this->client->followRedirect();
        $this->assertContains('保存しました', $crawler->html());

        // 商品を取得
        $Product = $this->productRepo->find($id);
        $this->assertNotEmpty($Product);

        // 各種項目の値は正しいか
        $fmt = 'Y/m/d H:i:s';
        $this->assertSame($start, $Product->getJsysSalesPeriodSaleStart()->format($fmt));
        $this->assertSame($finish, $Product->getJsysSalesPeriodSaleFinish()->format($fmt));
        $this->assertSame($before, $Product->getJsysSalesPeriodDisplayBeforeSale());
        $this->assertSame($finished, $Product->getJsysSalesPeriodDisplayFinished());
    }

    /**
     * 販売期間を設定せずに編集
     */
    public function testUpdateNotSetSalesPeriod()
    {
        // 商品とフォームデータを作成
        $Product  = $this->createProduct($this->faker->word, 0);
        $id       = $Product->getId();
        $start    = null;
        $finish   = null;
        $before   = false;
        $finished = false;
        $formData = array_merge($this->createFormData(), [
            'jsys_sales_period_sale_start'          => $start,
            'jsys_sales_period_sale_finish'         => $finish,
            'jsys_sales_period_display_before_sale' => $before,
            'jsys_sales_period_display_finished'    => $finished,
        ]);

        // 商品更新
        $url = $this->generateUrl('admin_product_product_edit', ['id' => $id]);
        $this->client->request('POST', $url, ['admin_product' => $formData]);
        $this->assertTrue($this->client->getResponse()->isRedirect($url));

        // 登録済みメッセージが表示されているか
        $crawler = $this->client->followRedirect();
        $this->assertContains('保存しました', $crawler->html());

        // 商品を取得
        $Product = $this->productRepo->find($id);
        $this->assertNotEmpty($Product);

        // 各種項目の値は正しいか
        $this->assertSame($start, $Product->getJsysSalesPeriodSaleStart());
        $this->assertSame($finish, $Product->getJsysSalesPeriodSaleFinish());
        $this->assertSame($before, $Product->getJsysSalesPeriodDisplayBeforeSale());
        $this->assertSame($finished, $Product->getJsysSalesPeriodDisplayFinished());
    }

    /**
     * 販売期間を設定して編集
     */
    public function testUpdateSalesPeriod()
    {
        // 商品とフォームデータを作成
        $Product  = $this->createProduct($this->faker->word, 0);
        $id       = $Product->getId();
        $start    = '2021/06/20 10:00:00';
        $finish   = '2021/06/30 20:00:00';
        $before   = true;
        $finished = true;
        $formData = array_merge($this->createFormData(), [
            'jsys_sales_period_sale_start'          => $start,
            'jsys_sales_period_sale_finish'         => $finish,
            'jsys_sales_period_display_before_sale' => $before,
            'jsys_sales_period_display_finished'    => $finished,
        ]);

        // 商品更新
        $url = $this->generateUrl('admin_product_product_edit', ['id' => $id]);
        $this->client->request('POST', $url, ['admin_product' => $formData]);
        $this->assertTrue($this->client->getResponse()->isRedirect($url));

        // 登録済みメッセージが表示されているか
        $crawler = $this->client->followRedirect();
        $this->assertContains('保存しました', $crawler->html());

        // 商品を取得
        $Product = $this->productRepo->find($id);
        $this->assertNotEmpty($Product);

        // 各種項目の値は正しいか
        $fmt = 'Y/m/d H:i:s';
        $this->assertSame($start, $Product->getJsysSalesPeriodSaleStart()->format($fmt));
        $this->assertSame($finish, $Product->getJsysSalesPeriodSaleFinish()->format($fmt));
        $this->assertSame($before, $Product->getJsysSalesPeriodDisplayBeforeSale());
        $this->assertSame($finished, $Product->getJsysSalesPeriodDisplayFinished());
    }

    /**
     * 商品の削除
     */
    public function testDelete()
    {
        // 商品を作成、販売期間を設定して登録
        $Product = $this->createProduct($this->faker->word, 0);
        $Product
            ->setJsysSalesPeriodSaleStart(new \DateTime('2021/06/20 10:00:00'))
            ->setJsysSalesPeriodSaleFinish(new \DateTime('2021/06/30 20:00:00'))
            ->setJsysSalesPeriodDisplayBeforeSale(true)
            ->setJsysSalesPeriodDisplayFinished(true);

        $this->entityManager->persist($Product);
        $this->entityManager->flush($Product);

        // 削除
        $params = ['id' => $Product->getId(), Constant::TOKEN_NAME => 'dummy'];
        $this->client->request(
            'DELETE',
            $this->generateUrl('admin_product_product_delete', $params)
        );
        $url = $this->generateUrl('admin_product_page', ['page_no' => 1]) . '?resume=1';
        $this->assertTrue($this->client->getResponse()->isRedirect($url));

        // 商品が取得できないか
        $this->assertNull($this->productRepo->find($params['id']));
    }


    /**
     * フォームデータを作成します。
     * @return array
     */
    private function createFormData()
    {
        $price01 = $this->faker->randomNumber(5);
        if (mt_rand(0, 1)) {
            $price01 = number_format($price01);
        }

        $price02 = $this->faker->randomNumber(5);
        if (mt_rand(0, 1)) {
            $price02 = number_format($price02);
        }

        return [
            'class'              => [
                'sale_type'         => 1,
                'price01'           => $price01,
                'price02'           => $price02,
                'stock'             => $this->faker->randomNumber(3),
                'stock_unlimited'   => 0,
                'code'              => $this->faker->word,
                'sale_limit'        => null,
                'delivery_duration' => '',
            ],
            'name'               => $this->faker->word,
            'product_image'      => [],
            'description_detail' => $this->faker->realText,
            'description_list'   => $this->faker->paragraph,
            'Category'           => null,
            'Tag'                => 1,
            'search_word'        => $this->faker->word,
            'free_area'          => $this->faker->realText,
            'Status'             => 1,
            'note'               => $this->faker->realText,
            'tags'               => null,
            'images'             => null,
            'add_images'         => null,
            'delete_images'      => null,
            Constant::TOKEN_NAME => 'dummy',
        ];
    }

}
