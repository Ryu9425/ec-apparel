<?php
/**
 * Created by SYSTEM_KD
 * Date: 2018/08/15
 */

namespace Plugin\SimpleMaintenance\Repository;


use Eccube\Repository\AbstractRepository;
use Plugin\SimpleMaintenance\Entity\SimpleMConfig;
use Symfony\Bridge\Doctrine\ManagerRegistry;

class SimpleMConfigRepository extends AbstractRepository
{

    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, SimpleMConfig::class);
    }

    public function get()
    {
        return $this->find(1);
    }
}
