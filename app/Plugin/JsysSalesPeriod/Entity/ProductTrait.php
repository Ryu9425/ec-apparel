<?php
namespace Plugin\JsysSalesPeriod\Entity;

use Doctrine\ORM\Mapping as ORM;
use Eccube\Annotation\EntityExtension;

/**
 * Product entity extension.
 * @author manabe
 *
 * @EntityExtension("Eccube\Entity\Product")
 */
trait ProductTrait
{
    /**
     * 販売開始日時
     * @var \DateTime|NULL
     *
     * @ORM\Column(name="jsys_sales_period_sale_start", type="datetimetz", nullable=true)
     */
    private $jsys_sales_period_sale_start;

    /**
     * 販売終了日時
     * @var \DateTime|NULL
     *
     * @ORM\Column(name="jsys_sales_period_sale_finish", type="datetimetz", nullable=true)
     */
    private $jsys_sales_period_sale_finish;

    /**
     * 販売開始前の一覧・詳細表示
     * @var boolean
     *
     * @ORM\Column(
     *   name="jsys_sales_period_display_before_sale",
     *   type="boolean",
     *   options={"default": false}
     * )
     */
    private $jsys_sales_period_display_before_sale = false;

    /**
     * 販売終了後の一覧・詳細表示
     * @var boolean
     *
     * @ORM\Column(
     *   name="jsys_sales_period_display_finished",
     *   type="boolean",
     *   options={"default": false}
     * )
     */
    private $jsys_sales_period_display_finished = false;


    /**
     * 販売開始日時を設定します。
     * @param \DateTime|NULL $jsysSalesPeriodSaleStart
     * @return \Plugin\JsysSalesPeriod\Entity\ProductTrait
     */
    public function setJsysSalesPeriodSaleStart($jsysSalesPeriodSaleStart)
    {
        $this->jsys_sales_period_sale_start = $jsysSalesPeriodSaleStart;
        return $this;
    }

    /**
     * 販売開始日時を取得します。
     * @return \DateTime|NULL
     */
    public function getJsysSalesPeriodSaleStart()
    {
        return $this->jsys_sales_period_sale_start;
    }

    /**
     * 販売終了日時を設定します。
     * @param \DateTime|NULL $jsysSalesPeriodSaleFinish
     * @return \Plugin\JsysSalesPeriod\Entity\ProductTrait
     */
    public function setJsysSalesPeriodSaleFinish($jsysSalesPeriodSaleFinish)
    {
        $this->jsys_sales_period_sale_finish = $jsysSalesPeriodSaleFinish;
        return $this;
    }

    /**
     * 販売終了日時を取得します。
     * @return \DateTime|NULL
     */
    public function getJsysSalesPeriodSaleFinish()
    {
        return $this->jsys_sales_period_sale_finish;
    }

    /**
     * 販売開始前の一覧・詳細表示を設定します。
     * @param boolean $jsysSalesPeriodDisplayBeforeSale
     * @return \Plugin\JsysSalesPeriod\Entity\ProductTrait
     */
    public function setJsysSalesPeriodDisplayBeforeSale($jsysSalesPeriodDisplayBeforeSale)
    {
        $this->jsys_sales_period_display_before_sale = $jsysSalesPeriodDisplayBeforeSale;
        return $this;
    }

    /**
     * 販売開始前の一覧・詳細表示を取得します。
     * @return boolean
     */
    public function getJsysSalesPeriodDisplayBeforeSale()
    {
        return $this->jsys_sales_period_display_before_sale;
    }

    /**
     * 販売終了後の一覧・詳細表示を設定します。
     * @param boolean $jsysSalesPeriodDisplayFinished
     * @return \Plugin\JsysSalesPeriod\Entity\ProductTrait
     */
    public function setJsysSalesPeriodDisplayFinished($jsysSalesPeriodDisplayFinished)
    {
        $this->jsys_sales_period_display_finished = $jsysSalesPeriodDisplayFinished;
        return $this;
    }

    /**
     * 販売終了後の一覧・詳細表示を取得します。
     * @return boolean
     */
    public function getJsysSalesPeriodDisplayFinished()
    {
        return $this->jsys_sales_period_display_finished;
    }


    /**
     * 販売開始前の商品か調べます。
     * @return boolean
     */
    public function isJsysSalesPeriodBeforeSale()
    {
        // 販売開始日時が未設定なら販売中
        if (!$this->jsys_sales_period_sale_start) {
            return false;
        }
        // 販売開始日時が今以前なら販売中
        $now = new \DateTime();
        if ($this->jsys_sales_period_sale_start <= $now) {
            return false;
        }
        // 販売開始日時に今より後が設定されている場合は開始前
        return true;
    }

    /**
     * 販売終了後の商品か調べます。
     * @return boolean
     */
    public function isJsysSalesPeriodFinishedSale()
    {
        // 販売終了日時が未設定なら販売中
        if (!$this->jsys_sales_period_sale_finish) {
            return false;
        }
        // 販売終了日時が今より後なら販売中
        $now = new \DateTime();
        if ($now < $this->jsys_sales_period_sale_finish) {
            return false;
        }
        // 販売終了日時に今以前が設定されている場合は終了後
        return true;
    }

    /**
     * 販売期間外の商品か調べます。
     * @return boolean
     */
    public function isJsysSalesPeriodNotSale()
    {
        // 販売開始前でも終了後でもなければ期間内、それ以外は期間外
        $before   = $this->isJsysSalesPeriodBeforeSale();
        $finished = $this->isJsysSalesPeriodFinishedSale();
        return !$before && !$finished ? false : true;
    }

}
