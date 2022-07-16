<?php
namespace Plugin\JsysSalesPeriod\Tests\Web\Admin;

use Faker\Generator;
use Eccube\Tests\Web\Admin\AbstractAdminWebTestCase;
use Plugin\JsysSalesPeriod\Repository\ConfigRepository;

class JsysSalesPeriodConfigControllerTest extends AbstractAdminWebTestCase
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
     * @see \Eccube\Tests\Web\Admin\AbstractAdminWebTestCase::setUp()
     */
    public function setUp()
    {
        parent::setUp();

        $this->faker      = $this->getFaker();
        $this->configRepo = $this->container->get(ConfigRepository::class);
    }

    /**
     * ルーティング
     */
    public function testRouting()
    {
        $this->client->request('GET', $this->generateUrl('jsys_sales_period_admin_config'));
        $this->assertTrue($this->client->getResponse()->isSuccessful());
    }

    /**
     * 開始前・終了後カートボタン文字検証
     */
    public function testButtonStringValidate()
    {
        $max = $this->eccubeConfig['eccube_stext_len'];
        $str = str_pad('1', $max + 1, '1');

        // データを作成してPOST
        $crawler = $this->client->request(
            'POST',
            $this->generateUrl('jsys_sales_period_admin_config'),
            ['config' => [
                '_token'             => 'dummy',
                'btnstr_before_sale' => $str,
                'btnstr_finished'    => $str,
            ]]
        );
        $this->assertTrue($this->client->getResponse()->isSuccessful());

        // 検証失敗メッセージが表示されているか
        $before   = '#config_btnstr_before_sale + span.invalid-feedback .form-error-message';
        $finished = '#config_btnstr_finished + span.invalid-feedback .form-error-message';
        $expected = "値が長すぎます。{$max}文字以内でなければなりません。";
        $this->assertSame($expected, $crawler->filter($before)->text());
        $this->assertSame($expected, $crawler->filter($finished)->text());
    }

    /**
     * デフォルト値で更新
     */
    public function testUpdateByDefault()
    {
        // データを作成してPOST
        $crawler = $this->client->request(
            'POST',
            $this->generateUrl('jsys_sales_period_admin_config'),
            ['config' => [
                '_token'             => 'dummy',
                'btnstr_before_sale' => null,
                'btnstr_finished'    => null,
            ]]
        );

        // 設定画面にリダイレクトされたか
        $this->assertTrue($this->client->getResponse()->isRedirect($this->generateUrl(
            'jsys_sales_period_admin_config'
        )));
        $crawler = $this->client->followRedirect();

        // 登録済みメッセージが表示されているか
        $this->assertContains('登録しました。', $crawler->html());

        // 登録された値をチェック
        $Config = $this->configRepo->get();
        $this->assertNull($Config->getBtnstrBeforeSale());
        $this->assertNull($Config->getBtnstrFinished());
    }

    /**
     * 開始前・終了後カートボタン文字列更新
     */
    public function testUpdate()
    {
        $before   = $this->faker->word;
        $finished = $this->faker->word;

        // データを作成してPOST
        $crawler = $this->client->request(
            'POST',
            $this->generateUrl('jsys_sales_period_admin_config'),
            ['config' => [
                '_token'             => 'dummy',
                'btnstr_before_sale' => $before,
                'btnstr_finished'    => $finished,
            ]]
        );

        // 設定画面にリダイレクトされたか
        $this->assertTrue($this->client->getResponse()->isRedirect($this->generateUrl(
            'jsys_sales_period_admin_config'
        )));
        $crawler = $this->client->followRedirect();

        // 登録済みメッセージが表示されているか
        $this->assertContains('登録しました。', $crawler->html());

        // 登録された値をチェック
        $Config = $this->configRepo->get();
        $this->assertSame($before, $Config->getBtnstrBeforeSale());
        $this->assertSame($finished, $Config->getBtnstrFinished());
    }

}
