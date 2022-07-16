<?php
/**
 * Copyright(c) 2019 SYSTEM_KD
 * Date: 2019/10/24
 */

namespace Plugin\UnderLimitQuantity\Entity;

use Doctrine\ORM\Mapping as ORM;
use Eccube\Annotation as Eccube;

/**
 * Trait ProductClassTrait
 * @package Plugin\UnderLimitQuantity\Entity
 *
 * @Eccube\EntityExtension("Eccube\Entity\ProductClass")
 */
trait ProductClassTrait
{

    /**
     * @var UnderQuantity
     *
     * @ORM\OneToOne(targetEntity="Plugin\UnderLimitQuantity\Entity\UnderQuantity", mappedBy="ProductClass", cascade={"persist", "remove"})
     */
    private $UnderQuantity;

    /**
     * @return UnderQuantity|null
     */
    public function getUnderQuantity()
    {
        return $this->UnderQuantity;
    }

    /**
     * @param UnderQuantity $UnderQuantity
     * @return $this
     */
    public function setUnderQuantity(?UnderQuantity $UnderQuantity)
    {
        $this->UnderQuantity = $UnderQuantity;
        return $this;
    }

}

