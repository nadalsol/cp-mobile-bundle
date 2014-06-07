<?php

/**
 * Chicplace Symfony2 Project
 *
 * Chicplace 2013
 */

namespace Chicplace\MobileBundle\Controller;

use BaseEcommerce\Bundles\Core\ProductBundle\Entity\Product;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\RedirectResponse;

/**
 * PageNotFoundController
 */
class PageNotFoundController extends Controller
{
    /**
     * Not found view
     *
     * @param Request $request
     *
     * @return array
     *
     */
    public function viewAction(Request $request)
    {
        //try redirect
        if ($response = $this->tryRedirect($request)) {

            return $response;
        }

        return new Response(
            $this->renderView('ChicplaceMobileBundle:PageNotFound:view.html.twig'),
            404
        );
    }



    private function tryRedirect(Request $request)
    {
        $manager = $this->getDoctrine()->getManager();
        //first get locale, then uri, then queryString if necessary
        $parts = array();
        preg_match('/^.*\/(es|en|fr|it)\/(.*)/', $request->getUri(), $parts);
        if (count($parts) < 2) {
            return null;
        }
        $locale = $parts[1];
        $uri = $parts[2];
        $queryString = "";
        if (strpos($uri, '?') !== false) {
            $uriParts = explode('?', $uri);
            $uri = $uriParts[0];
            $queryString = $uriParts[1];
        }

        //PRODUCTS: {category:/}{id}-{rewrite}.html
        $matches = array();
        if (preg_match('/^[_a-zA-Z0-9-\pL]+\/([0-9]+)-[_a-zA-Z0-9-\pL]*\.html/', $uri, $matches)) {
            $prod = $manager->getRepository('BaseEcommerceProductBundle:Product')->find($matches[1]);
            if ($prod instanceof Product) {
                $prod->translate($locale);
                //this is a prod
                $realUri = $this->get('router')->generate('product_view', array(
                        '_locale' => $locale,
                        'product_id' => $prod->getId(),
                        'slug' => $prod->translate($locale)->getSlug()
                    )
                );
                //change reponse
                return new RedirectResponse($realUri, 301);
            }
        }

        //CATEGORIES
        $matches = array();
        if (preg_match('/^([0-9]+)-[_a-zA-Z0-9-\pL]*/', $uri, $matches)) {
            //this is a category
            $category = $manager->getRepository('BaseEcommerceProductBundle:Category')->find($matches[1]);
            if (is_null($category) && ($shop = $manager->getRepository('BaseEcommerceProductBundle:Shop')->find($matches[1]))) {
                //this is a shop
                $realUri = $this->get('router')->generate('shop_view', array(
                        '_locale' => $locale,
                        'shop_id' => $shop->getId(),
                        'slug' => $shop->getSlug()
                    )
                );
            } elseif ($category) {
                $realUri = $this->get('router')->generate('category', array(
                        '_locale' => $locale,
                        'category_id' => $category->getId(),
                        'slug' => $category->translate($locale)->getSlug()
                    )
                );
            }

            //change reponse
            if (isset($realUri)) {

                return new RedirectResponse($realUri, 301);
            }
        }

        //SEARCHES
        if (in_array($uri, array('buscar', 'recherche', 'cerca', 'search'))) {
            $query = $request->get('search_query');
            if (empty($query)) {
                //if no query was given, 302 to homepage
                return new RedirectResponse("/{$locale}", 302);
            }

            $realUri = $this->get('router')->generate('filter', array(
                    '_locale' => $locale,
                    'query' => $query
                )
            );
            //change reponse. 302 because these pages have queryStrings
            return new RedirectResponse($realUri, 302);
        }

        return null;
    }
}
