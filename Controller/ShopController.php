<?php

/**
 * Chicplace Symfony2 Project
 *
 * Chicplace 2014
 */

namespace Chicplace\MobileBundle\Controller;

use BaseEcommerce\Bundles\Core\ProductBundle\Entity\Shop;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\Security\Core\Authentication\Token\UsernamePasswordToken;

/**
 * Class ShopController
 */
class ShopController extends Controller
{
    /**
     * Display shops list
     * @param Request $request request
     * @param int     $page    number of page
     *
     * @return array
     *
     * @Route("/shops/{page}", name="shop_list", defaults = {"page": 1}, requirements = { "page": "\d+" } )
     * @Route("/tiendas/{page}", name="shop_list_LOCALE_es", defaults = {"page": 1}, requirements = { "page": "\d+" } )
     * @Route("/boutiques/{page}", name="shop_list_LOCALE_fr", defaults = {"page": 1}, requirements = { "page": "\d+" } )
     * @Route("/boutique/{page}", name="shop_list_LOCALE_it", defaults = {"page": 1}, requirements = { "page": "\d+" } )
     *
     * @Template()
     */
    public function listAction(Request $request, $page)
    {
        $shopController = $this->get('chicplace.web.controller.shop');
        $shopController->setContainer($this->container);

        $viewData = $shopController->listAction($request, $page);
        $viewData['bricks_url'] = $this->get('router')->generate('shop_list', array('page' => $page), true);

        return $viewData;
    }

    /**
     * View shop
     *
     * @param Request                                               $request
     * @param \BaseEcommerce\Bundles\Core\ProductBundle\Entity\Shop $shop
     *
     * @return array
     *
     * Route for this is defined in routing.yml because of trailing slashes
     *
     * @Template()
     * @Route("/shop/{id}", requirements={"id":"\d+"}, name="shop_view")
     */
    public function viewAction(Request $request, Shop $shop)
    {
        //check that shop is enabled
        if (!$shop->isEnabled() || $shop->isDeleted()) {
            $shopCity = $shop->getShopAddress()->getCity();
            if (!($shopCity instanceof City)) {
                return new RedirectResponse($this->get('router')->generate('shop_list'));
            }

            $redirectRoute = $this->get('router')->generate('city_shops', array(
                'city' => $shopCity->getSlug()
            ));

            return new RedirectResponse($redirectRoute);
        }

        $page = $request->get('page', 1);
        $searcherProduct = $this->get('searcher_product');
        $products = $searcherProduct->filterSearch(
            "",
            array(),
            array(),
            array($shop->getId()),
            null,
            array(),
            $page,
            6
        );

        $viewData = array(
            'bricks_url' => $this->get('router')->generate('shop_view', array('id' => $shop->getId(), 'page' => $page+1), true),
            'shop_products' => $products[0],
            'shop' => $shop
        );

        return $viewData;
    }
}