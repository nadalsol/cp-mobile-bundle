<?php

/**
 * Chicplace Symfony2 Project
 *
 * Chicplace 2013
 */

namespace Chicplace\MobileBundle\Controller;

use BaseEcommerce\Bundles\Core\ProductBundle\Repository\CategoryRepository;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Chicplace\WebBundle\Controller\SearchController as BaseSearchController;

/**
 * Search controller
 */
class SearchController extends BaseSearchController
{
    /**
     * Shows new products
     *
     * @param Request $request
     *
     * @return array
     *
     * @Route("/whats-new", name="newproducts")
     * @Route("/novedades", name="newproducts_LOCALE_es")
     * @Route("/nouveautes", name="newproducts_LOCALE_fr")
     * @Route("/novita", name="newproducts_LOCALE_it")
     *
     * @Template("ChicplaceMobileBundle:Product:list.html.twig")
     */
    public function filterNewProductsAction(Request $request)
    {
        return parent::filterNewProductsAction($request);
    }

    /**
     * Shows products that have discounts
     *
     * @param Request $request
     *
     * @return array
     *
     * @Route("/sales/{page}", name="sales", requirements={"page" = "\d+"}, defaults={"page" = 1})
     * @Route("/rebajas/{page}", name="sales_LOCALE_es", requirements={"page" = "\d+"}, defaults={"page" = 1})
     * @Route("/soldes/{page}", name="sales_LOCALE_fr", requirements={"page" = "\d+"}, defaults={"page" = 1})
     * @Route("/soldi/{page}", name="sales_LOCALE_it", requirements={"page" = "\d+"}, defaults={"page" = 1})
     *
     * @Template("ChicplaceMobileBundle:Product:list.html.twig")
     */
    public function filterOnSaleProductsAction(Request $request)
    {
        return parent::filterOnSaleProductsAction($request);
    }

    /**
     * Shows products that can be customized.
     *
     * @param Request $request
     *
     * @return array
     *
     * @Route("/customizable", name="customizable")
     * @Route("/personalizable", name="customizable_LOCALE_es")
     * @Route("/personnalisable", name="customizable_LOCALE_fr")
     * @Route("/personalizzabile", name="customizable_LOCALE_it")
     *
     * @Template("ChicplaceMobileBundle:Product:list.html.twig")
     */
    public function filterCustomizableProductsAction(Request $request)
    {
        return parent::filterCustomizableProductsAction($request);
    }

    /**
     * Search base page
     *
     * @param Request $request Current request
     *
     * @return array
     *
     * @Route("/search", name="filter")
     * @Template("ChicplaceMobileBundle:Product:list.html.twig")
     */
    public function filterAction(Request $request)
    {
        $currencyManager = $this->get('baseecommerce.core.currency.manager.currency_manager');
        $activeCurrencyCode = $request->getSession()->get('currency', 'EUR');
        $exchangeRatesEur = $currencyManager->getExchangeRateList('EUR');
        $exchangeRatesActive = $exchangeRatesEur;
        if ($activeCurrencyCode != 'EUR') {
            $exchangeRatesActive = $currencyManager->getExchangeRateList($activeCurrencyCode);
        }

        $query = $request->get('query');
        $categoriesIds = array_filter((array) $request->query->get('categories_id', array()));
        $priceRange = $request->query->get('price_range');
        $characteristics = $request->query->get('characteristics', array());
        $page = $request->get('page', 1);
        $priceRangeMin = 0;
        $localMax = 300;
        $priceRangeMax = 0;
        $categoryCollection = array();
        $characteristicsFilter = array();

        if (!($query || $categoriesIds || $priceRange || count($characteristics))) {
            // no search parameter was set, redirect to home page
            return new RedirectResponse($this->generateUrl('homepage'));
        }

        $searchPriceRange = $priceRange;
        if (count($priceRange) && $activeCurrencyCode != 'EUR') {
            $searchPriceRange[0] = intval(
                intval($priceRange[0]) *
                    $exchangeRatesActive[$activeCurrencyCode]['EUR']
            );
            $searchPriceRange[1] = intval(
                intval($priceRange[1]) *
                    $exchangeRatesActive[$activeCurrencyCode]['EUR']
            );
            $localMax = intval(intval($localMax) * $exchangeRatesActive[$activeCurrencyCode]['EUR']);
        }
        //remove max constraint if it equals the max of the filter
        if ($searchPriceRange[1] == $localMax) {
            unset($searchPriceRange[1]);
        }

        $category = null;
        $categoryId = null;
        $categorySlug = null;
        if (!$request->isXmlHttpRequest() && count($categoriesIds) == 1) {
            $entityManager = $this->getDoctrine()->getManager();
            /** @var $categoryRepo CategoryRepository */
            $categoryRepo = $entityManager->getRepository('BaseEcommerceProductBundle:Category');
            $category = $categoryRepo->find($categoriesIds[0]);
            $categoryId = $category->getId();
            $categorySlug = $category->getSlug();
        }

        // banner types to search for are configured in parameters.yml
        $searcherProduct = $this->container->get('searcher_product');
        $orderBy = $request->query->get('order', 'selection_desc');
        list($searchResults, $facets) =
            $searcherProduct->filterSearch(
                $query,
                $categoriesIds,
                null,
                null,
                $searchPriceRange,
                $characteristics,
                $page,
                6,
                $orderBy
            );

        if ($request->isXmlHttpRequest()) {
            return array('products' => $searchResults);
        }

        if (count($facets) && count($categoriesIds)) {
            $characteristicsFilter = array();
            $characteristicsFilter['new'] = isset($facets['new']['T']) ? $facets['new']['T']['count'] : 0;
            $characteristicsFilter['customizable'] =
                isset($facets['customizable']['T']) ?
                    $facets['customizable']['T']['count'] : 0;
            $characteristicsFilter['onsale'] = isset($facets['onsale']['T']) ? $facets['onsale']['T']['count'] : 0;
        }

        $pinsId = array();
        foreach ($searchResults as $searchResult) {
            $pinsId[] =$searchResult['id'];
        }

        //set price range
        if (count($priceRange)) {
            $priceRangeMin = $priceRange[0];
            $priceRangeMax = $priceRange[1];
        }
        $bricksUrl = $this->get('router')->generate('filter', array(
            'categories_id' => $categoryId,
            'slug' => $categorySlug
        ));

        $viewData =  array(
            'category' => $category,
            'categories' => $categoryCollection,
            'characteristics_filter' => $characteristicsFilter,
            'bricks_url' => $bricksUrl,
            'price_range_products_max' => $localMax,
            'price_range_min' => $priceRangeMin,
            'price_range_max' => $priceRangeMax,
            'global_max_amount' => $localMax,
            'global_min_amount' => 0.0,
            'characteristics' => json_encode($characteristics),
            'query' => $query,
            'page' => $page + 1,
            'products' => $searchResults
        );

        return $viewData;
    }
}