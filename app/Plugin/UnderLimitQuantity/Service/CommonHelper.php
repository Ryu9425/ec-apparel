<?php
/**
 * Copyright(c) 2019 SYSTEM_KD
 * Date: 2019/11/02
 */

namespace Plugin\UnderLimitQuantity\Service;


use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\JsonResponse;

class CommonHelper
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
     * Routeが指定したものにマッチするか判定
     *
     * @param $targetRoutes
     * @param null|string $route
     * @return bool
     */
    public function isRoute($targetRoutes, $route = null)
    {
        if (!$route) {
            $route = $this->getRoute();
            if(!$route) {
                return false;
            }
        }

        if (in_array($route, $targetRoutes)) {
            return true;
        }

        return false;
    }

    /**
     * 現在のRoute名称返却
     *
     * @return mixed
     */
    public function getRoute()
    {
        $request = $this->getCurrentRequest();
        if($request) {
            $route = $request->attributes->get('_route');
        } else {
            $route = null;
        }

        return $route;
    }

    /**
     * リクエスト取得
     *
     * @return \Symfony\Component\HttpFoundation\Request|null
     */
    public function getCurrentRequest()
    {
        $request = $this->container->get('request_stack')->getCurrentRequest();
        return $request;
    }

    /**
     * json レスポンス
     *
     * @param $data
     * @param int $status
     * @param array $headers
     * @param array $context
     * @return JsonResponse
     */
    public function getJsonResponse($data, $status = 200, $headers = [], $context = [])
    {
        if ($this->container->has('serializer')) {
            $json = $this->container->get('serializer')->serialize($data, 'json', array_merge([
                'json_encode_options' => JsonResponse::DEFAULT_ENCODING_OPTIONS,
            ], $context));

            return new JsonResponse($json, $status, $headers, true);
        }

        return new JsonResponse($data, $status, $headers);
    }
}
