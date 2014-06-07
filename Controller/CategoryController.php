<?php

/**
 * Chicplace Symfony2 Project
 *
 * Chicplace 2013
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
 * Class CategoryController
 *
 * @package Chicplace\MobileBundle\Controller
 */
class CategoryController extends Controller
{
    /**
     * Get categories for menu
     *
     * @return Response
     *
     * @Route("/categories/menu", name="categories_menu")
     * @Template("ChicplaceMobileBundle:Category:blocks/menu.html.twig")
     */
    public function menuAction()
    {
        return array(
            'categories_tree' => $this->get('baseecommerce.core.product.services.category_manager')->getCategoryTree(),
        );
    }

    /**
     * Public category landing page
     *
     * @param Request $request Current request
     *
     * @return array
     *
     * @Route("/{slug}/{category_id}/c/{page}", requirements={"slug" = "[a-zA-Z0-9-]+", "category_id" = "\d+", "page" = "\d+"}, defaults={"page" = 1}, name="category")
     *
     * @Template("ChicplaceMobileBundle:Product:list.html.twig")
     */
    public function categoryAction(Request $request)
    {
        $categoryId = $request->get('category_id');
        if (!$categoryId) {

            // no category, redirect to home page
            return new RedirectResponse($this->generateUrl('homepage'));
        }

        $category = $this->getDoctrine()->getRepository('BaseEcommerceProductBundle:Category')->find($categoryId);
        if ((($uriSlug = $request->get('slug', false)) && ($uriSlug != $category->getSlug()))) {
            return new RedirectResponse(
                $this->get('router')->generate('category', array(
                    'category_id' => $category->getId(),
                    'slug' => $category->getSlug(),
                    'page' => $request->get('page', 1)
                ))
            );
        }

        // A category page is just a search page with 1 parameter and an
        // extra variable passed on to the view, make use of this
        $searchController = $this->get('chicplace.mobile.controller.search');
        $searchController->setContainer($this->container);

        // set the correct 'filter' information in the request (in the form the SearchAction expects)
        $request->query->set('categories_id', array($categoryId));
        $viewData = $searchController->filterAction($request);

        // filterAction can return a whole response sometimes, check for this
        if ($viewData instanceof Response) {
            return $viewData;
        }

        $categoryCollection = $this->get('searcher_category')
            ->getChildrenCategories($categoryId);

        $viewData['categories'] = $categoryCollection;

        // pass data to the view
        return array('category_title' => $category->getName()) + $viewData;
    }

    /**
     * Make well array with categories data for filters
     *
     * @param SearcherCategory $searcher              ElasticSearch service
     * @param array            $categoriesItemsCount  ids
     * @param string           $locale                current language
     * @param array            $selectedCategoriesIds array of selected categories
     *
     * @return array
     */
    protected function prepareCategoryCollection($searcher, $categoriesItemsCount, $locale, $selectedCategoriesIds)
    {
        $categoriesIds = array();
        foreach ($categoriesItemsCount as $category) {
            if ($category['count'] > 0) {
                $categoriesIds[] = (int) $category['value'];
            }
        }
        $resultCategories = $searcher->getCategoriesById($categoriesIds);
        $categoryCollection = array();

        if (!count($resultCategories)) {
            return $categoryCollection;
        }

        foreach ($resultCategories as $category) {
            $categoryData = array();
            $categoryData['id'] = $category->getData()['id'];
            if (is_array($selectedCategoriesIds)) {
                $categoryData['selected'] =
                    (in_array($categoryData['id'], $selectedCategoriesIds)) ? true : false;
            }
            $categoryData['name'] = $category->getData()['name_'.$locale];

            foreach ($categoriesItemsCount as $categoryCount) {
                if ($categoryCount['value'] == $categoryData['id']) {
                    $categoryData['count'] = $categoryCount['count'];
                }
            }

            if (isset($categoryData['name']) && isset($categoryData['count'])) {
                $categoryCollection[] = $categoryData;
            }
        }

        return $categoryCollection;
    }
}
