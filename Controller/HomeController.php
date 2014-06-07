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
 * Class HomeController
 *
 * @package Chicplace\MobileBundle\Controller
 */
class HomeController extends Controller
{
    /**
     * Home page
     *
     * If isXmlHttpRequest, this method renders just pins zone, for infinite scroll action
     * Otherwise, home twig is rendered
     * For each page is showing: 20 products and 2 banners; if are less than 10 products -> 1 banner (at the end of products)
     *
     * @return array
     *
     * Route for this is defined in routing.yml because of trailing slashes
     *
     * @Template()
     */
    public function indexAction()
    {
        return [];
    }


    /**
     * Main Redirect Controller
     *
     * @param Request $request
     *
     * @return RedirectResponse
     */
    public function redirectAction(Request $request)
    {
        $language = $request->headers->get('Accept-Language');

        if (!$language) {
            // no header found, skip to default
            $request->setLocale($this->container->getParameter('locale'));

        } else {
            $languages = explode(",", $language);

            // we need to order the languages in order to their optional
            // weight as defined in http://tools.ietf.org/html/rfc2616#section-14.4
            uasort($languages, function ($a, $b) {
                return $this->getAcceptLanguageWeight($a) > $this->getAcceptLanguageWeight($b) ? -1 : 1;
            });

            // get langcode and check that it is a valid langcode, if not, default to english
            $langCode = substr($languages[0], 0, 2);
            if (!(in_array($langCode, array('en', 'es', 'fr', 'it')))) {
                $langCode = 'es';
            }

            // WARNING: we are working with 2 digit ISO639 codes,
            // so we are chopping the language-range string,
            // meaning that "en-gb" will be "en"
            // This must be fixed when we want to work with
            // 5 digit codes
            $request->setLocale($langCode);
        }

        $cookieWarning = $request->getSession()->get('cookie_warning_shown');
        if ($cookieWarning == false) {
            $request->getSession()->remove('cookie_warning_shown');
        }

        return new RedirectResponse($this->generateUrl('homepage', array('_locale' => $request->getLocale())));
    }

    /**
     * Internal comparator function to sort languages
     * from theis weight defined in
     * http://tools.ietf.org/html/rfc2616#section-14.4
     *
     * @param string $val
     *
     * @return float|int
     */
    private function getAcceptLanguageWeight($val)
    {
        $language = explode(";", $val);
        if (sizeof($language) == 1) {
            return 1;
        }

        $q = explode("=", $language[1]);
        if (sizeof($q) == 1) {
            return 1;
        }

        $w = (float) $q[1];

        return $w;
    }
}
