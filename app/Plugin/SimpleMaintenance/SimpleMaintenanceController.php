<?php
/**
 * Created by SYSTEM_KD
 * Date: 2018/08/15
 */

namespace Plugin\SimpleMaintenance;

use Eccube\Common\EccubeConfig;
use Eccube\Util\EntityUtil;
use Plugin\SimpleMaintenance\Entity\SimpleMConfig;
use Plugin\SimpleMaintenance\Repository\SimpleMConfigRepository;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpKernel\Event\FilterResponseEvent;
use Symfony\Component\HttpKernel\KernelEvents;

/**
 * Class SimpleMaintenanceController
 * @package Plugin\SimpleMaintenance\Controller
 */
class SimpleMaintenanceController implements EventSubscriberInterface
{

    /** @var EccubeConfig  */
    private $eccubeService;

    /** @var SimpleMConfigRepository  */
    private $configRepository;

    public function __construct(
        EccubeConfig $eccubeConfig,
        SimpleMConfigRepository $configRepository
    )
    {
        $this->eccubeService = $eccubeConfig;
        $this->configRepository = $configRepository;
    }

    /**
     * レスポンスフック
     *
     * @param FilterResponseEvent $event
     */
    public function onKernelResponse(FilterResponseEvent $event)
    {

        if (!$event->isMasterRequest()) {
            return;
        }

        $request = $event->getRequest();

        if($request->isXmlHttpRequest()) {
            return;
        }

        $path = $request->getPathInfo();
        $adminRoot = $this->eccubeService->get('eccube_admin_route');

        if (strpos($path, '/' . trim($adminRoot, '/')) === 0) {
            // admin_route はメンテナンスページから除外
            return;
        }

        // 設定情報取得
        /** @var SimpleMConfig $config */
        $config = $this->configRepository->get();

        if (EntityUtil::isNotEmpty($config)) {
            if ($config->isMenteMode()) {

                $session = $request->getSession();
                $is_admin = $session->has('_security_admin');

                if($config->isAdminCloseFlg() && $is_admin) {
                    // 管理者でかつ、管理者での閲覧有効時の場合は除外
                    return;
                }

                // メンテナンスモード有効
                $html = $config->getPageHtml();

                $response = $event->getResponse();
                $response->setContent($html);
            }
        }

    }

    /**
     * Returns an array of event names this subscriber wants to listen to.
     *
     * The array keys are event names and the value can be:
     *
     *  * The method name to call (priority defaults to 0)
     *  * An array composed of the method name to call and the priority
     *  * An array of arrays composed of the method names to call and respective
     *    priorities, or 0 if unset
     *
     * For instance:
     *
     *  * array('eventName' => 'methodName')
     *  * array('eventName' => array('methodName', $priority))
     *  * array('eventName' => array(array('methodName1', $priority), array('methodName2')))
     *
     * @return array The event names to listen to
     */
    public static function getSubscribedEvents()
    {
        return [
            KernelEvents::RESPONSE => ['onKernelResponse', 0],
        ];
    }
}
