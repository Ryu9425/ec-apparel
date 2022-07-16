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


namespace Plugin\CustomShipping\Controller\Mypage;

use Eccube\Controller\AbstractController;
use Eccube\Entity\Master\CustomerStatus;
use Eccube\Event\EccubeEvents;
use Eccube\Event\EventArgs;
use Eccube\Repository\Master\CustomerStatusRepository;
use Symfony\Component\HttpKernel\Exception as HttpException;
use Eccube\Service\CartService;
use Eccube\Common\EccubeConfig;
use Eccube\Entity\Customer;
use Eccube\Repository\CustomerRepository;
use Eccube\Service\MailService;
use Eccube\Service\OrderHelper;
use Eccube\Util\StringUtil;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorage;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;


class WithdrawController extends AbstractController
{
    /**
     * @var EccubeConfig
     */
    protected $eccubeConfig;

    /**
     * @var MailService
     */
    protected $mailService;

    /**
     * @var CustomerStatusRepository
     */
    protected $customerStatusRepository;

    /**
     * @var TokenStorage
     */
    protected $tokenStorage;

    /**
     * @var CartService
     */
    private $cartService;

    /**
     * @var OrderHelper
     */
    private $orderHelper;

    /**
     * @var string %kernel.project_dir%
     */
    private $projectRoot;

     /**
     * @var CustomerRepository
     */
    protected $customerRepository;

    /**
     * @var Customer
     */
    protected $Customer;

    /**
     * WithdrawController constructor.
     *
     * @param MailService $mailService
     * @param CustomerStatusRepository $customerStatusRepository
     * @param TokenStorageInterface $tokenStorage
     * @param EccubeConfig $eccubeConfig
     * @param CartService $cartService
     * @param OrderHelper $orderHelper
     * @param CustomerRepository $customerRepository
     */
    public function __construct(
        EccubeConfig $eccubeConfig,
        MailService $mailService,
        CustomerStatusRepository $customerStatusRepository,
        TokenStorageInterface $tokenStorage,
        CartService $cartService,
        OrderHelper $orderHelper,
        CustomerRepository $customerRepository
    ) {
        $this->mailService = $mailService;
        $this->customerStatusRepository = $customerStatusRepository;
        $this->tokenStorage = $tokenStorage;
        $this->cartService = $cartService;
        $this->orderHelper = $orderHelper;
        $this->eccubeConfig = $eccubeConfig;
        $this->projectRoot = $eccubeConfig->get('kernel.project_dir');
        $this->customerRepository = $customerRepository;
    }    

    /**
     * 退会完了画面.   
     * @Route("/mypage/withdraw_complete/", name="mypage_withdraw_complete")
     * @Template("Mypage/withdraw_complete.twig")
     */
    public function complete(Request $request)
    {        
        $customer = $request->get('customer');   
        $status = $request->get('status'); 

        /** @var $Customer \Eccube\Entity\Customer */

        $currentStatus="";
        if(!$customer&&!$status){            
            $currentStatus="exitStatus";
            return ['currentStatus'=>$currentStatus];
        }else if ($customer&&!$status){
            $currentStatus="noPayConfirmStatus";
            return ['currentStatus'=>$currentStatus, 'customer'=>$customer];
        }else{

            $criteria = ['id' => $customer];
           
            /** @var $Customer \Eccube\Entity\Customer */
            $Customers = $this->customerRepository->findBy($criteria);
            $readyCustomer = $Customers[0];
            $readyCustomer->setUnsubscribeStatus("2");
            $em = $this->getDoctrine()->getManager();
            $em->persist($readyCustomer);
            $em->flush();
            return $this->redirectToRoute('homepage');           
        }      
    }
}
