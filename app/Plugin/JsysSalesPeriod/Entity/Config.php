<?php

namespace Plugin\JsysSalesPeriod\Entity;

use Doctrine\ORM\Mapping as ORM;

if (!class_exists('\Plugin\JsysSalesPeriod\Entity\Config', false)) {
    /**
     * Config
     *
     * @ORM\Table(name="plg_jsys_sales_period_config")
     * @ORM\Entity(repositoryClass="Plugin\JsysSalesPeriod\Repository\ConfigRepository")
     */
    class Config
    {
        /**
         * ID
         * @var int
         *
         * @ORM\Column(name="id", type="integer", options={"unsigned":true})
         * @ORM\Id
         * @ORM\GeneratedValue(strategy="IDENTITY")
         */
        private $id;

        /**
         * 販売開始前カートボタン文字列
         * @var string|NULL
         *
         * @ORM\Column(name="btnstr_before_sale", type="string", length=255, nullable=true)
         */
        private $btnstr_before_sale;

        /**
         * 販売終了後カートボタン文字列
         * @var string|NULL
         *
         * @ORM\Column(name="btnstr_finished", type="string", length=255, nullable=true)
         */
        private $btnstr_finished;

        /**
         * 登録日
         * @var \DateTime
         *
         * @ORM\Column(name="create_date", type="datetimetz")
         */
        private $create_date;

        /**
         * 更新日
         * @var \DateTime
         *
         * @ORM\Column(name="update_date", type="datetimetz")
         */
        private $update_date;


        /**
         * IDを取得します。
         * @return int
         */
        public function getId()
        {
            return $this->id;
        }

        /**
         * 販売開始前カートボタン文字列を設定します。
         * @param string|NULL $strBeforeStart
         * @return \Plugin\JsysSalesPeriod\Entity\Config
         */
        public function setBtnstrBeforeSale($btnstrBeforeSale)
        {
            $this->btnstr_before_sale = $btnstrBeforeSale;
            return $this;
        }

        /**
         * 販売開始前カートボタン文字列を取得します。
         * @return string|NULL
         */
        public function getBtnstrBeforeSale()
        {
            return $this->btnstr_before_sale;
        }

        /**
         * 販売終了後カートボタン文字列を設定します。
         * @param string|NULL $btnstrFinished
         * @return \Plugin\JsysSalesPeriod\Entity\Config
         */
        public function setBtnstrFinished($btnstrFinished)
        {
            $this->btnstr_finished = $btnstrFinished;
            return $this;
        }

        /**
         * 販売終了後カートボタン文字列を取得します。
         * @return string|NULL
         */
        public function getBtnstrFinished()
        {
            return $this->btnstr_finished;
        }

        /**
         * 登録日を設定します。
         * @param \DateTime $createDate
         * @return \Plugin\JsysSalesPeriod\Entity\Config
         */
        public function setCreateDate($createDate)
        {
            $this->create_date = $createDate;
            return $this;
        }

        /**
         * 登録日を取得します。
         * @return \DateTime
         */
        public function getCreateDate()
        {
            return $this->create_date;
        }

        /**
         * 更新日を設定します。
         * @param \DateTime $updateDate
         * @return \Plugin\JsysSalesPeriod\Entity\Config
         */
        public function setUpdateDate($updateDate)
        {
            $this->update_date = $updateDate;
            return $this;
        }

        /**
         * 更新日を取得します。
         * @return \DateTime
         */
        public function getUpdateDate()
        {
            return $this->update_date;
        }

    }
}
