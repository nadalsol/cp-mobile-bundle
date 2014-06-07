<?php

/**
 * Chicplace Symfony2 Project
 *
 * Chicplace 2014
 */

namespace Chicplace\MobileBundle\Controller;

use BaseEcommerce\Bundles\Core\ProductBundle\Entity\Product;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\Security\Core\Authentication\Token\UsernamePasswordToken;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\ParamConverter;

/**
 * Class ProductController
 *
 * @package Chicplace\MobileBundle\Controller
 */
class ProductController extends Controller
{
    /**
     * View product
     *
     * @param \BaseEcommerce\Bundles\Core\ProductBundle\Entity\Product $product
     *
     * @return array
     *
     * Route for this is defined in routing.yml because of trailing slashes
     *
     * @Template()
     * @Route("/product/{product_id}", requirements={"product_id":"\d+"}, name="product_view")
     *
     * @ParamConverter("product", class="BaseEcommerceProductBundle:Product", options={
     *      "id" = "product_id"
     * })
     * @ParamConverter("cartLine", class="BaseEcommercePurchaseBundle:CartLine", options={
     *      "id" = "cartline_id"
     * }, isOptional = "true")
     */
    public function viewAction(Product $product)
    {
        $activeItem = $product->getItems(true)->filter(function ($item) {
            return $item->getStock() > 0;
        })->first();

        $productRepo = $this->getDoctrine()
            ->getRepository('BaseEcommerceProductBundle:Product');

        $discountRepo = $this->getDoctrine()
            ->getRepository('ChicplaceCouponBundle:Discount');
        $discountsPrice = $discountRepo->getProductDiscountsPrice(
            $product->getId()
        );

        $productSearcher = $this->get('searcher_product');
        $relatedProducts = $productSearcher->getRelatedItems($product);

        return [
            'product' => $product,
            'item' => $activeItem,
            'product_images' =>
                $productRepo->getProductImages($product),
            'product_attributes' =>
                $productRepo->getProductAttributes($product),
            'product_attribute_values' =>
                $productRepo->getProductAttributeValues($product),
            'product_images' => $productRepo->getProductImages($product),
            'discounts_price' => $discountsPrice,
            'related_products' => $relatedProducts,
        ];
    }

    /**
     * List products
     *
     *
     * @return array
     *
     * Route for this is defined in routing.yml because of trailing slashes
     *
     * @Route("/product/list", name="product_list")
     * @Template()
     */
    public function listAction()
    {
        //$productRepo = $this->getDoctrine()->getRepository('BaseEcommerceProductBundle:Product');

        return array('products' => array());
    }
}