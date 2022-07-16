<?php
/**
 * Copyright(c) 2019 SYSTEM_KD
 * Date: 2019/10/26
 */

namespace Plugin\UnderLimitQuantity\Form\Helper;


use Psr\Container\ContainerInterface;
use Symfony\Component\HttpFoundation\Request;

class FormHelper
{

    /** @var ContainerInterface */
    protected $container;

    public function __construct(
        ContainerInterface $container
    )
    {
        $this->container = $container;
    }

    /**
     * Request取得
     *
     * @return Request
     */
    private function getRequest()
    {
        /** @var Request $request */
        $request = $this->container
            ->get('request_stack')
            ->getMasterRequest();

        return $request;
    }

    /**
     * Request の ID取得
     *
     * @return mixed
     */
    public function getActiveId()
    {
        $id = $this->getRequest()
            ->attributes
            ->get('id');

        return $id;
    }
}
