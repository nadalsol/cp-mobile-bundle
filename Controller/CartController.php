<?php

/**
 * Chicplace Symfony2 Project
 *
 * Chicplace 2014
 */

namespace Chicplace\MobileBundle\Controller;

use BaseEcommerce\Bundles\Core\CoreBundle\Entity\Language;
use BaseEcommerce\Bundles\Core\GeoBundle\Entity\Address;
use BaseEcommerce\Bundles\Core\GeoBundle\Entity\Country;
use BaseEcommerce\Bundles\Core\PurchaseBundle\Entity\Order;
use BaseEcommerce\Bundles\Core\UserBundle\Entity\Customer;
use BaseEcommerce\Bundles\Core\UserBundle\Wrapper\CustomerWrapper;
use Chicplace\CoreBundle\Controller\Abstracts\AbstractCustomerActionsController;
use Chicplace\WebBundle\Form\Type\RegisterType;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\ParamConverter;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Security\Core\Authentication\Token\UsernamePasswordToken;
use Chicplace\LiveStatsBundle\Annotation\LogStatCounter;
use BaseEcommerce\Bundles\Core\ProductBundle\Entity\Item;
use BaseEcommerce\Bundles\Core\PurchaseBundle\Entity\CartLine;
use BaseEcommerce\Bundles\Core\PurchaseBundle\Exception\CartLineOutOfStockException;
use BaseEcommerce\Bundles\Core\PurchaseBundle\Exception\CartLineNotEnoughQuantityException;
use BaseEcommerce\Bundles\Core\PurchaseBundle\Exception\CartLineItemUnavailableException;
use Chicplace\LiveStatsBundle\Event\EntityStatsEvent;
use Chicplace\CoreBundle\ChicplaceCoreEvents;
use Exception;
use JMS\SecurityExtraBundle\Annotation\Secure;

/**
 * Class HomeController
 *
 * @package Chicplace\MobileBundle\Controller
 */
class CartController extends AbstractCustomerActionsController
{
    /**
     * Cart view page
     *
     * @param Request $request the current request
     *
     * @return array
     *
     * @Route("/cart", name="cart_view")
     *
     * @Template()
     * @Method("GET")
     */
    public function cartAction(Request $request)
    {
        // we must find related products
        /** @var Cart $cart */
        $cart = $this->get('baseecommerce.core.purchase.services.cart_manager')->getCart();

        if ($cart->getLines()->isEmpty()) {
            return $this->redirect($this->generateUrl('homepage'));
        }

        $discountRepo = $this->getDoctrine()->getManager()->getRepository('ChicplaceCouponBundle:Discount');
        $discounts = $discountRepo->getCartDiscounts($cart->getId());

        /** @var Product $product */
        $product = $cart->getLines()->first()->getProduct();
        $this->get('selligent.notifications')->updateCart($cart);

        $renderData = array(
            'id_default_item' => $product->getItems()->first()->getId(),
            'customizations' => $this->get('baseecommerce.core.purchase.services.cart_manager')->getReadableCustomizations($cart),
            'cart'  =>  $cart,
            'discounts'  =>  $discounts,
        );

        $statsEvent = new EntityStatsEvent();
        $statsEvent->setAffectedEntity($cart);
        $this->get('event_dispatcher')->dispatch(ChicplaceCoreEvents::CART_VIEWED, $statsEvent);

        //save cart extra data
        $this->get('baseecommerce.core.purchase.services.cart_manager')
            ->setCartExtraData(
                $cart,
                $request->getSession()->get('currency', 'EUR'),
                $this->container->getParameter(
                    'baseecommerce.core.currency.default_currency'
                )
            );

        return $renderData;
    }


    /**
     * Cart register page
     *
     * @param Request $request the current request
     *
     * @return array
     *
     * @Route("/cart/register", name="cart_register")
     *
     * @Template()
     */
    public function registerAction(Request $request)
    {
        $customerWrapper = $this->get(
            'baseecommerce.core.user.wrapper.customer_wrapper'
        );
        $customer = $customerWrapper->getCustomer();
        $customer->setGender(null);
        $cart = $this->get(
            'baseecommerce.core.purchase.wrapper.cart_wrapper'
        )->getCart();

        if (!$cart->getLines()->count()) {
            return new RedirectResponse(
                $this->get('router')->generate('cart_view')
            );
        }

        //anon user register & delivery data
        $formType = new RegisterType($this->getDoctrine()->getManager());
        $form = $this->createForm(
            $formType,
            $customer,
            array('validation_groups' => array('Register', 'Default'))
        );

        $form->handleRequest($request);
        //if form has not yet been submitted or is wrong, return view data
        if (!$form->isSubmitted() || !$form->isValid()) {
            return [
                'form' => $form->createView(),
                'cart' => $cart
            ];
        }

        $redirectUrl = 'cart_payment';
        $languageRepo = $this->getDoctrine()->getManager()->getRepository('BaseEcommerceCoreBundle:Language');
        $language = $languageRepo->findOneBy(array('iso' => $request->getLocale()));
        if (!($language instanceof Language)) {
            $language = $languageRepo->findOneByIso('en');
        }
        $customer->setLanguage($language);

        //new customer
        $customer = $form->getData();
        $customer->setLimitedLogin(true);

        //the checkbox in the form has it backwards: False means YES to the NL
        $customer->setNewsletter(!$customer->hasNewsletter());

        $locale = strtoupper($language->getIso());

        // check email
        $customerWithEmail = $this
            ->getDoctrine()
            ->getRepository('BaseEcommerceUserBundle:Customer')
            ->findOneByEmail($customer->getEmail());

        if (!($customerWithEmail instanceof Customer)) {
            $this->get('session')->set(
                'analyticsPageTrack',
                $locale.'accountcreation'
            );
            //this means the email is NOT in use, everything clear!
            $this->registerNewCustomer($customer, $customerWrapper);

            return $this->redirect($this->generateUrl($redirectUrl));
        }

        //this means the email is in use, so we assimilte users
        $this->assimilateUsers($customerWrapper, $customer, $customerWithEmail);

        return $this->redirect($this->generateUrl($redirectUrl));
    }

    /**
     * Cart payment page
     *
     * @param Request $request the current request
     *
     * @return array
     *
     * @Route("/cart/payment", name="cart_payment")
     * @Route("/cart/payment/retry", name="cart_payment_error")
     *
     * @Template()
     * @Secure({"ROLE_USER_CART"})
     */
    public function paymentAction(Request $request)
    {
        $activeCart = $this->get(
            'baseecommerce.core.purchase.wrapper.cart_wrapper'
        )->getCart();

        if (!$activeCart->getLines()->count()) {
            return new RedirectResponse(
                $this->get('router')->generate('cart_view')
            );
        }

        $flashMessage = null;
        if ($request->get('_route') == 'cart_payment_error') {
            $flashMessage = '_Order_validation_problem';
        }

        /** @var AbstractCart $cart */
        $cart = $this->get(
            'baseecommerce.core.purchase.services.cart_manager'
        )->getCart();

        $this->get('baseecommerce.core.purchase.services.cart_manager')
            ->setCartExtraData(
                $cart,
                $request->getSession()->get('currency', 'EUR'),
                $this->container->getParameter(
                    'baseecommerce.core.currency.default_currency'
                )
            );

        //CHECK FOR VALID DELIVERY ADDRESS
        $address = null;
        if ($cart->getDeliveryAddress() instanceof Address) {
            $address = $cart->getDeliveryAddress();
        }
        if (is_null($address)) {
            $address = $cart->getUser()->getDeliveryAddress();
        }
        if (is_null($address) || !($address->getCountry() instanceof Country)) {

            return new RedirectResponse(
                $this->get('router')->generate('cart_register')
            );
        }
        $cart->setDeliveryAddress($address);

        if (!$this->get('baseecommerce.core.purchase.services.cart_manager')
            ->isValidDeliveryAddress($cart)
        ) {
            $flashMessage = '_Non_reachable_address_problem';
        }

        $this->get('selligent.notifications')->updateCart($cart);
        $discountRepo = $this->getDoctrine()->getManager()
            ->getRepository('ChicplaceCouponBundle:Discount');
        $discounts = $discountRepo->getCartDiscounts($cart->getId());

        $this->get('baseecommerce.core.purchase.services.cart_manager')
                ->loadPrices($cart);

        $viewData = array(
            'cart'  =>  $cart,
            'paymill_public_key' => $this->container->getParameter('paymill_public_key'),
            'flashMessage' => $flashMessage,
            'discounts'  =>  $discounts,
        );

        $statsEvent = new EntityStatsEvent();
        $statsEvent->setAffectedEntity($cart);
        $this->get('event_dispatcher')->dispatch(ChicplaceCoreEvents::CART_PAYMENT, $statsEvent);

        if ($flashMessage) {
            //if we have to inform of something, do not check for free order
            return $viewData;
        }

        //CHECK FOR FREE ORDER
        if ($cart->getPrice() < 0.01 && !$cart->getLines()->isEmpty()) {
            //this is a free order
            /** @var Order $order */
            $order = $this->get('baseecommerce.core.purchase.services.order_manager')
                            ->createFromCart($cart, 'free_order');

            $confirmationUrl = $this->get('router')->generate('cart_thanks', array('order_id' => $order->getId()));

            //pass order to accepted
            $this->get('baseecommerce.core.purchase.services.order_manager')->toAccepted($order);

            return new RedirectResponse($confirmationUrl);
        }

        return $viewData;
    }

    /**
     * Cart thanks page
     *
     * @param Request $request the current request
     * @param Order   $order   The order
     *
     * @return array
     *
     * @Route("/cart/thanks/order/{order_id}", name="cart_thanks")
     * @Template
     *
     * @Secure({"ROLE_USER_CART"})
     *
     * @ParamConverter("order", class="BaseEcommercePurchaseBundle:Order", options={
     *      "id" = "order_id"
     * })
     */
    public function thanksAction(Request $request, Order $order)
    {
        $idOrderUser = $order->getUser()->getId();
        $idCurrentUser = $this->get('baseecommerce.core.user.wrapper.customer_wrapper')->getCustomer()->getId();

        if ($idOrderUser === $idCurrentUser) {

            $statsEvent = new EntityStatsEvent();
            $statsEvent->setAffectedEntity($order->getCart());
            $this->get('event_dispatcher')->dispatch(ChicplaceCoreEvents::CART_PAID, $statsEvent);

            return [
                'order' => $order,
                'trackOrderAnalytics' => true
            ];
        }

        return $this->redirect($this->generateUrl('homepage'));
    }

    /**
     * Adds item into cart
     *
     * @param Request $request  Request object
     * @param Item    $item     Item
     *
     * @return Response Redirect response
     *
     * @Route("/product/{product_id}/add/checkout", name="cart_add_product_checkout", defaults={"checkout": true})
     *
     * @ParamConverter("item", class="BaseEcommerceProductBundle:Item", options={
     *      "id" = "product_id"
     * })
     * @LogStatCounter(
     *      counterName = "'product-added-to-cart-' ~ now.format('Y-m-d')",
     *      counterKey  = "product_id",
     *      increaseBy  = "1",
     *      execute     = LogStatCounter::EXEC_POST
     * )
     */
    public function addProductAction(Request $request, Item $item = null)
    {
        $error = false;
        $trans = $this->get('translator');
        $redirect = $this->generateUrl('cart_view', array('product_added' => 1));
        if (is_null($item)) {
            $this->get('session')->getFlashBag()->add('error', $trans->trans('_Product_add_error'));
            //add an error message and redirect

            return new RedirectResponse($redirect);
        }

        $quantity = $request->get('quantity', 1);
        if (intval($quantity) < 1 ) {
            $quantity = 1;
        }
        $customizables = $request->get('customizables', array()) + $request->files->get('customizables', array());

        try {

            $this
                ->get('baseecommerce.core.purchase.services.cart_manager')
                ->addItem($item, $quantity, $customizables)
                ->persist();

        } catch (CartLineItemUnavailableException $e) {

            $this
                ->get('session')
                ->getFlashBag()
                ->add('error', $trans->trans('_Product_unavailable_error'));
            $error = true;
        } catch (CartLineNotEnoughQuantityException $e) {

            $this
                ->get('session')
                ->getFlashBag()
                ->add('error', $trans->trans('_Product_min_quantity_not_reached', array('%quantity%' => $e->getQuantity())));
            $error = true;
        } catch (\Exception $e) {
            $errorMessage = $this->getQuantityChangeException($e, $item->getStock(), $item->getMinPurchaseQuantity());
            $this
                ->get('session')
                ->getFlashBag()
                ->add('error', $errorMessage);
            $error = true;
        }

        $cart = $this->get('baseecommerce.core.purchase.services.cart_manager')->getCart();
        $user = $this->get('baseecommerce.core.user.wrapper.customer_wrapper')->getCustomer();
        $cart->setUser($user);
        $this->get('doctrine.orm.entity_manager')->persist($user);
        $this->get('doctrine.orm.entity_manager')->persist($cart);
        $this->get('doctrine.orm.entity_manager')->flush(array($user, $cart));
        $this->get('baseecommerce.core.user.wrapper.customer_wrapper')->setCustomer($user);
        $this->get('baseecommerce.core.user.wrapper.customer_wrapper')->persist(true);
        $this->get('baseecommerce.core.purchase.services.cart_manager')->setCart($cart);
        $this->get('baseecommerce.core.purchase.services.cart_manager')->persist(true);

        if ($error) {
            $product = $item->getProduct();
            $parameters = array(
                'product_id' => $product->getId(),
                'slug' => $product->translate($request->getLocale())->getSlug()
            );

            return new RedirectResponse($this->generateUrl('product_view', $parameters, true));
        }

        return new RedirectResponse($redirect);
    }

    /**
     * Sets CartLine quantity value
     *
     * @param Request  $request  Request object
     * @param CartLine $cartLine CartLine
     *
     * @return Response Redirect response
     *
     * @Route("/line/{cartline_id}/quantity/set", name="cartline_set_quantity")
     *
     * @ParamConverter("cartLine", class="BaseEcommercePurchaseBundle:CartLine", options={
     *      "id" = "cartline_id"
     * })
     */
    public function setCartLineQuantityAction(Request $request, CartLine $cartLine = null)
    {
        $error = false;
        $trans = $this->get('translator');
        if (is_null($cartLine)) {
            //if CartLine was not found...
            $this->get('session')->getFlashBag()->add('error', $trans->trans('_Product_add_error'));

            return new JsonResponse(array(
                'url' => $this->generateUrl('cart_view', array('product_added' => 1), true)
            ));
        }

        $quantity = $request->get('quantity', 1);
        try {
            $this
                ->get('baseecommerce.core.purchase.services.cart_manager')
                ->setCartLineQuantity($cartLine, $quantity)
                ->persist();

        } catch (Exception $e) {
            $errorMessage = $this->getQuantityChangeException($e, $cartLine->getItem()->getStock(), $cartLine->getItem()->getMinPurchaseQuantity());
            $this
                ->get('session')
                ->getFlashBag()
                ->add('error', $errorMessage);
            $error = true;
        }

        if ($error) {
            $product = $cartLine->getProduct();
            $parameters = array(
                'product_id' => $product->getId(),
                'slug' => $product->translate($request->getLocale())->getSlug()
            );

            return new JsonResponse(array(
                'url' => $this->generateUrl('product_view', $parameters, true)
            ));
        }

        return new JsonResponse(array(
            'url' => $this->generateUrl('cart_view', array('product_added' => 1), true)
        ));
    }

    /**
     * Cart view for POSTs
     *
     * @param Request $request
     *
     * @return RedirectResponse
     *
     * @Route("/cart", name="cart_post")
     * @Method("POST")
     */
    public function submitCartAction(Request $request)
    {
        $couponCode = $request->request->get('cart-coupon');
        /** @var CartManager $cartManager */
        $cartManager = $this->get('baseecommerce.core.purchase.services.cart_manager');
        $comment = $request->request->get('cart-comment', '');
        $giftWrap = false;
        if ($request->request->get('gift-wrap') == 'on') {
            $giftWrap = true;
        }

        $cartManager
            ->getCart()
            ->setComment($comment)
            ->setGiftWrap($giftWrap);

        $cartManager->persist();

        if (!empty($couponCode)) {

            return $this->redirect($this->generateUrl('cart_add_coupon', array(
                'couponCode' => $couponCode,
            )));
        }

        //return $this->redirect($this->generateUrl('cart_login'));

        return $this->redirect($this->generateUrl('cart_register'));
    }

    /**
     * Adds coupon into cart
     *
     * @param Coupon  $couponCode Coupon
     *
     * @return Response Redirect response
     *
     * @Route("/coupon/{couponCode}/add", name="cart_add_coupon")
     */
    public function addCouponAction($couponCode)
    {
        $this->get('chicplace.coupon.coupon_manager')->applyCoupon($couponCode);
        $redirect = $this->generateUrl('cart_view');

        return new RedirectResponse($redirect);
    }

    /**
     * Cart snippet for top bar
     *
     * @return array
     *
     * @Route("/snippet/topbar", name="cart_view_topbar")
     * @Template("ChicplaceMobileBundle:Cart:blocks/cart_number_of_items.html.twig")
     */
    public function snippettopbarAction()
    {
        $cart = $this->get('baseecommerce.core.purchase.services.cart_manager')->getCart();

        return array(
            'nbItems'  =>  $cart->getNbItems()
        );
    }

    /**
     * Deletes CartLine
     *
     * @param Request  $request  Request
     * @param CartLine $cartLine CartLine
     * @param Boolean  $checkout Go to checkout page
     *
     * @return RedirectResponse
     *
     * @Route("/line/{cartline_id}/delete", name="cartline_remove", defaults={"checkout": false})
     *
     * @ParamConverter("cartLine", class="BaseEcommercePurchaseBundle:CartLine", options={
     *      "id" = "cartline_id"
     * })
     */
    public function removeCartLineAction(Request $request, $checkout, CartLine $cartLine = null)
    {
        $redirectResponse = new RedirectResponse($this->generateUrl('cart_view'));
        if (is_null($cartLine)) {
            //if CartLine was not found...
            $this->get('session')->getFlashBag()->add('error', $this->get('translator')->trans('_Product_add_error'));

            return $redirectResponse;
        }

        $this
            ->get('baseecommerce.core.purchase.services.cart_manager')
            ->deleteCartLine($cartLine)
            ->persist();

        return $redirectResponse;
    }
}