<?php
/**
 * Created by SYSTEM_KD
 * Date: 2018/08/15
 */

namespace Plugin\SimpleMaintenance\Controller\Admin;


use Eccube\Controller\AbstractController;
use Eccube\Util\EntityUtil;
use Plugin\SimpleMaintenance\Entity\SimpleMConfig;
use Plugin\SimpleMaintenance\Repository\SimpleMConfigRepository;
use Plugin\SimpleMaintenance\Form\Type\Admin\ConfigType;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;

class ConfigController extends AbstractController
{

    protected $configRepository;

    public function __construct(SimpleMConfigRepository $configRepository)
    {
        $this->configRepository = $configRepository;
    }

    /**
     *
     * @Route("/%eccube_admin_route%/simple_maintenance/config", name="simple_maintenance_admin_config")
     * @Template("@SimpleMaintenance/admin/config.twig")
     *
     * @param Request $request
     * @return array
     */
    public function index(Request $request)
    {

        /** @var SimpleMConfig $config */
        $config = $this->configRepository->get();

        if (EntityUtil::isEmpty($config)) {
            $config = new SimpleMConfig();
            $config->setMenteMode(false);
            $config->setAdminCloseFlg(false);
        }

        $form = $this->createForm(ConfigType::class, $config);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $config = $form->getData();
            // 設定情報登録
            $this->entityManager->persist($config);
            $this->entityManager->flush();

            $this->addSuccess('simple_maintenance.config_save', 'admin');
        }

        return [
            'form' => $form->createView()
        ];
    }

}
