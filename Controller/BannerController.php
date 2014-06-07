<?php

/**
 * Chicplace Symfony2 Project
 *
 * Chicplace 2014
 */

namespace Chicplace\MobileBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\Security\Core\Authentication\Token\UsernamePasswordToken;

/**
 * Class BannerController
 *
 * @package Chicplace\MobileBundle\Controller
 */
class BannerController extends Controller
{
    /**
     * List banners for a static display. Return first banner for the zone
     *
     * @param Request  $requst
     * @param string   $bannerZoneName
     *
     * @return array
     *
     * @Route("/staticlist/{bannerZoneName}/*", name="banner_static_list")
     * @Template("ChicplaceMobileBundle:Banner:static_list.html.twig")
     */
    public function staticListAction(Request $request, $bannerZoneName)
    {
        $bannerController = $this->get('chicplace.web.controller.banner');
        $bannerController->setContainer($this->container);
        $viewData = $bannerController->staticListAction($request, $bannerZoneName);

        return $viewData;
    }
}
