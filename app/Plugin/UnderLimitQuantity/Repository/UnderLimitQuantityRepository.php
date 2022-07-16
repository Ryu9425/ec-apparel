<?php
/**
 * Copyright(c) 2019 SYSTEM_KD
 * Date: 2019/10/24
 */

namespace Plugin\UnderLimitQuantity\Repository;


use Doctrine\Common\Persistence\ManagerRegistry;
use Eccube\Repository\AbstractRepository;
use Plugin\UnderLimitQuantity\Entity\UnderQuantity;

class UnderLimitQuantityRepository extends AbstractRepository
{

    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, UnderQuantity::class);
    }
}
