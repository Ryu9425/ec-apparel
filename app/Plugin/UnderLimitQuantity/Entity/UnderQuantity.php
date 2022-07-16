<?php
/**
 * Copyright(c) 2019 SYSTEM_KD
 * Date: 2019/10/24
 */

namespace Plugin\UnderLimitQuantity\Entity;


use Doctrine\ORM\Mapping as ORM;

/**
 * Class UnderQuantity
 * @package Plugin\UnderLimitQuantity\Entity
 *
 * @ORM\Table(name="plg_under_quantity")
 * @ORM\Entity(repositoryClass="Plugin\UnderLimitQuantity\Repository\UnderLimitQuantityRepository")
 */
class UnderQuantity
{

    /**
     * @var int
     *
     * @ORM\Column(name="id", type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="IDENTITY")
     */
    private $id;

    /**
     * @var \Eccube\Entity\ProductClass
     *
     * @ORM\OneToOne(targetEntity="Eccube\Entity\ProductClass", inversedBy="UnderQuantity")
     * @ORM\JoinColumns({
     *   @ORM\JoinColumn(name="product_class_id", referencedColumnName="id")
     * })
     */
    private $ProductClass;

    /**
     * @var string|null
     *
     * @ORM\Column(name="quantity", type="decimal", precision=10, scale=0, nullable=true, options={"unsigned":true})
     */
    private $quantity;

    /**
     * Get id.
     *
     * @return int
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * Set productClass.
     *
     * @param \Eccube\Entity\ProductClass|null $productClass
     *
     * @return UnderQuantity
     */
    public function setProductClass(\Eccube\Entity\ProductClass $productClass = null)
    {
        $this->ProductClass = $productClass;

        return $this;
    }

    /**
     * Get productClass.
     *
     * @return \Eccube\Entity\ProductClass|null
     */
    public function getProductClass()
    {
        return $this->ProductClass;
    }

    /**
     * Set under_quantity
     *
     * @param integer $quantity
     * @return UnderQuantity
     */
    public function setQuantity($quantity)
    {
        $this->quantity = $quantity;

        return $this;
    }

    /**
     * Get under_quantity
     *
     * @return integer
     */
    public function getQuantity()
    {
        return $this->quantity;
    }
}
