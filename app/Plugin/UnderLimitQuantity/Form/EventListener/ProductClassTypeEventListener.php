<?php
/**
 * Copyright(c) 2019 SYSTEM_KD
 * Date: 2019/10/26
 */

namespace Plugin\UnderLimitQuantity\Form\EventListener;


use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\ORMException;
use Eccube\Entity\Product;
use Eccube\Entity\ProductClass;
use Plugin\UnderLimitQuantity\Form\Helper\FormHelper;
use Symfony\Component\Form\FormEvent;

class ProductClassTypeEventListener
{
    /** @var EntityManagerInterface */
    protected $entityManager;

    /** @var FormHelper */
    protected $formHelper;

    public function __construct(
        EntityManagerInterface $entityManager,
        FormHelper $formHelper
    )
    {
        $this->entityManager = $entityManager;
        $this->formHelper = $formHelper;
    }

    public function postSubmit(FormEvent $event)
    {
        $form = $event->getForm();

        /** @var ProductClass $data */
        $data = $event->getData();

        /** @var ProductClass $productClass */
        $productClass = $form->getData();

        $underQuantityForm = $form->get('UnderQuantity');

        $quantity = $underQuantityForm->get('quantity')->getData();

        if (empty($quantity)) {

            $UnderQuantity = $underQuantityForm->getData();

            if ($UnderQuantity) {
                $this->entityManager->remove($UnderQuantity);
            }

            $productClass->setUnderQuantity(null);

        } else {
            $productClass->getUnderQuantity()
                ->setProductClass($productClass);
        }

        $this->resetProductClass($productClass);
    }

    /**
     * 規格初期化時
     *
     * @param ProductClass $data
     * @throws ORMException
     */
    private function resetProductClass(ProductClass $data)
    {

        if ($data->getId()) {
            return;
        }

        $productId = $this->formHelper->getActiveId();
        if (empty($productId)) {
            return;
        }
        $product = $this->entityManager->getRepository(Product::class)->find($productId);

        /** @var ProductClass $ExistsProductClass */
        $ExistsProductClass = $this->entityManager->getRepository(ProductClass::class)->findOneBy([
            'Product' => $product,
            'ClassCategory1' => $data->getClassCategory1(),
            'ClassCategory2' => $data->getClassCategory2(),
        ]);

        // 過去の登録情報があればその情報を復旧する.
        if ($ExistsProductClass) {

            // 現状の情報を復元
            $this->entityManager->refresh($ExistsProductClass);

            if ($data->getUnderQuantity()
                && $data->getUnderQuantity()->getQuantity()) {

                if ($ExistsProductClass->getUnderQuantity()) {
                    $ExistsProductClass
                        ->getUnderQuantity()
                        ->setQuantity($data->getUnderQuantity()->getQuantity());

                    $data->setUnderQuantity($ExistsProductClass->getUnderQuantity());
                } else {
                    $data->getUnderQuantity()
                        ->setQuantity($data->getUnderQuantity()->getQuantity())
                        ->setProductClass($ExistsProductClass);
                }


            } else {
                $underQuantity = $ExistsProductClass->getUnderQuantity();
                if ($underQuantity) {
                    $this->entityManager->remove($underQuantity);
                }
            }
        }
    }
}
