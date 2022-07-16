<?php

/*
 * This file is part of EC-CUBE
 *
 * Copyright(c) EC-CUBE CO.,LTD. All Rights Reserved.
 *
 * http://www.ec-cube.co.jp/
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Eccube\Repository;

use Eccube\Entity\OrderItem;
use Symfony\Bridge\Doctrine\RegistryInterface;
use Eccube\Util\StringUtil;
use Eccube\Doctrine\Query\Queries;
use Eccube\Entity\Shipping;
use Eccube\Entity\Master\OrderStatus;
use Symfony\Component\Validator\Constraints\NotNull;

/**
 * OrderItemRepository
 *
 * This class was generated by the Doctrine ORM. Add your own custom
 * repository methods below.
 */
class OrderItemRepository extends AbstractRepository
{

     /**
     * @var Queries
     */
    protected $queries;

    /**
     * OrderRepository constructor.
     *
     * @param RegistryInterface $registry
     * @param Queries $queries
     */
    public function __construct(RegistryInterface $registry, Queries $queries)
    {
        parent::__construct($registry, OrderItem::class);
        $this->queries = $queries;
        
    }
    public function getQueryBuilderBySearchDataForAdmin($searchData)
    {
       
        $qb = $this->createQueryBuilder('oi')
            ->select('oi')
            ->leftJoin('oi.Order', 'o')
            ->leftJoin('oi.CusShipping', 'cs')
            ->leftJoin('o.Pref', 'pref')
            ->innerJoin('o.Shippings', 's')
            ->andWhere('oi.OrderItemType = :item_type')->setParameter('item_type', 1)
            ->andWhere('o.OrderStatus != :order_no')->setParameter('order_no', 8);//buying stop in way
        
        // order_no
        if (isset($searchData['order_no']) && StringUtil::isNotBlank($searchData['order_no'])) {
            $qb
                ->andWhere('o.order_no = :order_no')
                ->setParameter('order_no', $searchData['order_no']);
        }

       
        // multi
        if (isset($searchData['multi']) && StringUtil::isNotBlank($searchData['multi'])) {
            $multi = preg_match('/^\d{0,10}$/', $searchData['multi']) ? $searchData['multi'] : null;
            if ($multi && $multi > '2147483647' && $this->isPostgreSQL()) {
                $multi = null;
            }
            $qb
                ->andWhere('oi.id = :multi OR o.name01 LIKE :likemulti OR o.name02 LIKE :likemulti OR '.
                            'o.kana01 LIKE :likemulti OR o.kana02 LIKE :likemulti OR o.company_name LIKE :likemulti OR '.
                            'o.order_no LIKE :likemulti OR o.email LIKE :likemulti OR o.phone_number LIKE :likemulti')
                ->setParameter('multi', $multi)
                ->setParameter('likemulti', '%'.$searchData['multi'].'%');
        }

        

        // email
        if (isset($searchData['email']) && StringUtil::isNotBlank($searchData['email'])) {
            $qb
                ->andWhere('o.email like :email')
                ->setParameter('email', '%'.$searchData['email'].'%');
        }

        // tel
        if (isset($searchData['phone_number']) && StringUtil::isNotBlank($searchData['phone_number'])) {
            $tel = preg_replace('/[^0-9]/ ', '', $searchData['phone_number']);
            $qb
                ->andWhere('o.phone_number LIKE :phone_number')
                ->setParameter('phone_number', '%'.$tel.'%');
        }

        

        // buy_product_name
        if (isset($searchData['buy_product_name']) && StringUtil::isNotBlank($searchData['buy_product_name'])) {
            $qb
                ->andWhere('oi.product_name LIKE :buy_product_name')
                ->setParameter('buy_product_name', '%'.$searchData['buy_product_name'].'%');
        }

        

         // 送り状番号.
         if (!empty($searchData['tracking_number'])) {
            $qb
                ->andWhere('cs.cus_track_number = :tracking_number')
                ->setParameter('tracking_number', $searchData['tracking_number']);
        }

        // status
     
        if (!empty($searchData['status']) && count($searchData['status'])) {
            $qb
                ->andWhere($qb->expr()->in('oi.cus_order_status_id', ':status'))
                ->setParameter('status', $searchData['status']);
           
        }

        // order_id_start
        if (isset($searchData['order_id_start']) && StringUtil::isNotBlank($searchData['order_id_start'])) {
            $qb
                ->andWhere('o.id >= :order_id_start')
                ->setParameter('order_id_start', $searchData['order_id_start']);
        }
        // order_id_end
        if (isset($searchData['order_id_end']) && StringUtil::isNotBlank($searchData['order_id_end'])) {
            $qb
                ->andWhere('o.id <= :order_id_end')
                ->setParameter('order_id_end', $searchData['order_id_end']);
        }

        // company_name
        if (isset($searchData['company_name']) && StringUtil::isNotBlank($searchData['company_name'])) {
            $qb
                ->andWhere('o.company_name LIKE :company_name')
                ->setParameter('company_name', '%'.$searchData['company_name'].'%');
        }

        // name
        if (isset($searchData['name']) && StringUtil::isNotBlank($searchData['name'])) {
            $qb
                ->andWhere('CONCAT(o.name01, o.name02) LIKE :name')
                ->setParameter('name', '%'.$searchData['name'].'%');
        }

        // kana
        if (isset($searchData['kana']) && StringUtil::isNotBlank($searchData['kana'])) {
            $qb
                ->andWhere('CONCAT(o.kana01, o.kana02) LIKE :kana')
                ->setParameter('kana', '%'.$searchData['kana'].'%');
        }

        // sex
        if (!empty($searchData['sex']) && count($searchData['sex']) > 0) {
            $qb
                ->andWhere($qb->expr()->in('o.Sex', ':sex'))
                ->setParameter('sex', $searchData['sex']->toArray());
        }
        
        // payment
        if (!empty($searchData['payment']) && count($searchData['payment'])) {
            $payments = [];
            foreach ($searchData['payment'] as $payment) {
                $payments[] = $payment->getId();
            }
            $qb
                ->leftJoin('o.Payment', 'p')
                ->andWhere($qb->expr()->in('p.id', ':payments'))
                ->setParameter('payments', $payments);
        }

        // oreder_date
        if (!empty($searchData['order_datetime_start']) && $searchData['order_datetime_start']) {
            $date = $searchData['order_datetime_start'];
            $qb
                ->andWhere('o.order_date >= :order_date_start')
                ->setParameter('order_date_start', $date);
        } elseif (!empty($searchData['order_date_start']) && $searchData['order_date_start']) {
            $date = $searchData['order_date_start'];
            $qb
                ->andWhere('o.order_date >= :order_date_start')
                ->setParameter('order_date_start', $date);
        }

        if (!empty($searchData['order_datetime_end']) && $searchData['order_datetime_end']) {
            $date = $searchData['order_datetime_end'];
            $qb
                ->andWhere('o.order_date < :order_date_end')
                ->setParameter('order_date_end', $date);
        } elseif (!empty($searchData['order_date_end']) && $searchData['order_date_end']) {
            $date = clone $searchData['order_date_end'];
            $date = $date
                ->modify('+1 days');
            $qb
                ->andWhere('o.order_date < :order_date_end')
                ->setParameter('order_date_end', $date);
        }
        
        // cus_shipping_date
        if(!empty($searchData['cus_shipping_date_start']) && $searchData['cus_shipping_date_start']){
            $date = $searchData['cus_shipping_date_start'];
            $qb
                ->andWhere('cs.cus_shipping_date >= :cus_shipping_date_start')
                ->setParameter('cus_shipping_date_start', $date);
        }
        if(!empty($searchData['cus_shipping_date_end']) && $searchData['cus_shipping_date_end']){
            $date = $searchData['cus_shipping_date_end'];
            $date_str = $date->format('Y-m-d');
            
            
            $qb
                ->andWhere('cs.cus_shipping_date <= :cus_shipping_date_end')
                ->setParameter('cus_shipping_date_end', date('Y-m-d', strtotime($date_str)));
        }
        
        

        // payment_date
        if (!empty($searchData['payment_datetime_start']) && $searchData['payment_datetime_start']) {
            $date = $searchData['payment_datetime_start'];
            $qb
                ->andWhere('o.payment_date >= :payment_date_start')
                ->setParameter('payment_date_start', $date);
        } elseif (!empty($searchData['payment_date_start']) && $searchData['payment_date_start']) {
            $date = $searchData['payment_date_start'];
            $qb
                ->andWhere('o.payment_date >= :payment_date_start')
                ->setParameter('payment_date_start', $date);
        }

        if (!empty($searchData['payment_datetime_end']) && $searchData['payment_datetime_end']) {
            $date = $searchData['payment_datetime_end'];
            $qb
                ->andWhere('o.payment_date < :payment_date_end')
                ->setParameter('payment_date_end', $date);
        } elseif (!empty($searchData['payment_date_end']) && $searchData['payment_date_end']) {
            $date = clone $searchData['payment_date_end'];
            $date = $date
                ->modify('+1 days');
            $qb
                ->andWhere('o.payment_date < :payment_date_end')
                ->setParameter('payment_date_end', $date);
        }

        // update_date
        if (!empty($searchData['update_datetime_start']) && $searchData['update_datetime_start']) {
            $date = $searchData['update_datetime_start'];
            $qb
                ->andWhere('o.update_date >= :update_date_start')
                ->setParameter('update_date_start', $date);
        } elseif (!empty($searchData['update_date_start']) && $searchData['update_date_start']) {
            $date = $searchData['update_date_start'];
            $qb
                ->andWhere('o.update_date >= :update_date_start')
                ->setParameter('update_date_start', $date);
        }

        if (!empty($searchData['update_datetime_end']) && $searchData['update_datetime_end']) {
            $date = $searchData['update_datetime_end'];
            $qb
                ->andWhere('o.update_date < :update_date_end')
                ->setParameter('update_date_end', $date);
        } elseif (!empty($searchData['update_date_end']) && $searchData['update_date_end']) {
            $date = clone $searchData['update_date_end'];
            $date = $date
                ->modify('+1 days');
            $qb
                ->andWhere('o.update_date < :update_date_end')
                ->setParameter('update_date_end', $date);
        }

         // payment_total
         if (isset($searchData['payment_total_start']) && StringUtil::isNotBlank($searchData['payment_total_start'])) {
            $qb
                ->andWhere('oi.price >= :payment_total_start')
                ->setParameter('payment_total_start', $searchData['payment_total_start']);
        }
        if (isset($searchData['payment_total_end']) && StringUtil::isNotBlank($searchData['payment_total_end'])) {
            $qb
                ->andWhere('oi.price <= :payment_total_end')
                ->setParameter('payment_total_end', $searchData['payment_total_end']);
        }

        // 発送メール送信/未送信.
        if (isset($searchData['shipping_mail']) && $count = count($searchData['shipping_mail'])) {
            // 送信済/未送信両方にチェックされている場合は検索条件に追加しない
            if ($count < 2) {
                $checked = current($searchData['shipping_mail']);
                if ($checked == Shipping::SHIPPING_MAIL_UNSENT) {
                    // 未送信
                    $qb
                        ->andWhere('oi.is_mail_sent IS NULL');
                } elseif ($checked == Shipping::SHIPPING_MAIL_SENT) {
                    // 送信
                    $qb
                        ->andWhere('oi.is_mail_sent IS NOT NULL');
                }
            }
        }

        // お届け予定日(Shipping.delivery_date)
        if (!empty($searchData['shipping_delivery_datetime_start']) && $searchData['shipping_delivery_datetime_start']) {
            $date = $searchData['shipping_delivery_datetime_start'];
            $qb
                ->andWhere('s.shipping_delivery_date >= :shipping_delivery_date_start')
                ->setParameter('shipping_delivery_date_start', $date);
        } elseif (!empty($searchData['shipping_delivery_date_start']) && $searchData['shipping_delivery_date_start']) {
            $date = $searchData['shipping_delivery_date_start'];
            $qb
                ->andWhere('s.shipping_delivery_date >= :shipping_delivery_date_start')
                ->setParameter('shipping_delivery_date_start', $date);
        }

        if (!empty($searchData['shipping_delivery_datetime_end']) && $searchData['shipping_delivery_datetime_end']) {
            $date = $searchData['shipping_delivery_datetime_end'];
            $qb
                ->andWhere('s.shipping_delivery_date < :shipping_delivery_date_end')
                ->setParameter('shipping_delivery_date_end', $date);
        } elseif (!empty($searchData['shipping_delivery_date_end']) && $searchData['shipping_delivery_date_end']) {
            $date = clone $searchData['shipping_delivery_date_end'];
            $date = $date
                ->modify('+1 days');
            $qb
                ->andWhere('s.shipping_delivery_date < :shipping_delivery_date_end')
                ->setParameter('shipping_delivery_date_end', $date);
        }

       

        // Order By
        // $qb->orderBy('o.update_date', 'DESC');
        $qb->addorderBy('oi.id', 'DESC');

        return $this->queries->customize(QueryKey::ORDER_SEARCH_ADMIN, $qb, $searchData);
    }

    /**
     * ステータスごとの受注件数を取得する.
     *
     * @param integer $OrderStatusOrId
     *
     * @return int
     *
     * @throws \Doctrine\ORM\NoResultException
     * @throws \Doctrine\ORM\NonUniqueResultException
     */
    public function countByOrderItemStatus($OrderStatusOrId)
    {
        return (int) $this->createQueryBuilder('oi')
            ->select('COALESCE(COUNT(oi.id), 0)')
            ->leftJoin('oi.Order', 'o')
            ->andWhere('oi.OrderItemType = :item_type')->setParameter('item_type', 1)
            ->andWhere('oi.cus_order_status_id = :OrderStatus')
            ->andWhere('o.OrderStatus != :order_no')->setParameter('order_no', 8)
            ->setParameter('OrderStatus', $OrderStatusOrId)
            ->getQuery()
            ->getSingleScalarResult();
    }
}
