<?php
namespace Plugin\JsysSalesPeriod;

use Symfony\Component\DependencyInjection\ContainerInterface;
use Doctrine\ORM\EntityManagerInterface;
use Eccube\Plugin\AbstractPluginManager;
use Plugin\JsysSalesPeriod\Entity\Config;

class PluginManager extends AbstractPluginManager
{
    /**
     * {@inheritDoc}
     * @see \Eccube\Plugin\AbstractPluginManager::enable()
     */
    public function enable(array $meta, ContainerInterface $container)
    {
        $em = $container->get('doctrine.orm.entity_manager');

        // プラグイン設定を作成
        $this->createConfig($em);
    }


    /**
     * プラグイン設定を作成します。
     * @param EntityManagerInterface $em
     */
    protected function createConfig(EntityManagerInterface $em)
    {
        if ($this->existsRecord($em, Config::class, 1)) {
            return;
        }

        $Config = new Config();
        $Config
            ->setBtnstrBeforeSale(null)
            ->setBtnstrFinished(null)
            ->setCreateDate(new \DateTime())
            ->setUpdateDate(new \DateTime());

        $em->persist($Config);
        $em->flush($Config);
    }


    /**
     * レコードが存在するか調べます。
     * @param EntityManagerInterface $em
     * @param string $class
     * @param int $id
     * @return boolean
     */
    private function existsRecord(EntityManagerInterface $em, $class, $id)
    {
        $Record = $em->find($class, $id);
        if (!$Record) {
            return false;
        }
        return true;
    }

}
