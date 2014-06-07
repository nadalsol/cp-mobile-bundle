<?php

/**
 * Chicplace Symfony2 Project
 *
 * Chicplace 2014
 */

namespace Chicplace\MobileBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;

/**
 * Style Guide controller
 */
class StyleGuideController extends Controller
{

    /**
     * Style Guide page
     *
     * @return array
     *
     * @Route(
     *      path = "/styleguide",
     *      name = "styleguide"
     * )
     * @Template("ChicplaceMobileBundle:StyleGuide:style_guide.html.twig")
     */
    public function styleguideAction()
    {
        return array();
    }

}