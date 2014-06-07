<?php

/**
 * Chicplace Symfony2 Project
 *
 * Chicplace 2013
 */

namespace Chicplace\MobileBundle\Controller;

use Sensio\Bundle\FrameworkExtraBundle\Configuration\ParamConverter;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Request;

use BaseEcommerce\Bundles\Core\CoreBundle\Entity\Language;

/**
 * Class LanguageController
 *
 * @package Chicplace\WebBundle\Controller
 *
 * @Route("/language")
 */
class LanguageController extends Controller
{

    /**
     * Show list of languages
     *
     * @return array with view data
     *
     * @Route("/list", name="language_list")
     * @Template()
     */
    public function listAction(Request $request)
    {
        $languages = $this->get('baseecommerce.core.core.services.locale_manager')->getActiveLanguages();

        return array(
            'master_route' => $request->get('master_route'),
            'master_params' => $request->get('master_params'),
            'languages' => $languages
        );
    }
}
