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
 * Static controller
 */
class StaticController extends Controller
{

    /**
     * Static About Us page
     *
     * @return array
     *
     * @Route(path = "/aboutus", name = "aboutus")
     * @Route("/acerca-de", name="aboutus_LOCALE_es")
     * @Route("/au-sujet-de", name="aboutus_LOCALE_fr")
     * @Route("/circa", name="aboutus_LOCALE_it")
     *
     * @Template("ChicplaceMobileBundle:Static:about_us.html.twig")
     */
    public function aboutusAction()
    {
        return array();
    }


    /**
     * Static Contact Us page
     *
     * @return array
     *
     * @Route("/contactus", name = "contactus")
     * @Route("/contactus/partners", name="contactus_partners", defaults={"subject" = "_Partners_email"})
     * @Route("/contacto", name="contactus_LOCALE_es", defaults={"subject" = null})
     * @Route("/contacto/partners", name="contactus_partners_LOCALE_es", defaults={"subject" = "_Partners_email"})
     * @Route("/contactez-nous", name="contactus_fr", defaults={"subject" = null})
     * @Route("/contactez-nous/partenaires", name="contactus_partners_LOCALE_fr", defaults={"subject" = "_Partners_email"})
     * @Route("/contatto", name="contactus_it", defaults={"subject" = null})
     * @Route("/contatto/partner", name="contactus_partners_LOCALE_it", defaults={"subject" = "_Partners_email"})
     *
     * @Template("ChicplaceMobileBundle:Static:contact_us.html.twig")
     */
    public function contactusAction()
    {
        return array();
    }
    

    /**
     * Static Terms of Use page
     *
     * @return array
     *
     * @Route(path = "/termsofuse", name = "termsofuse")
     * @Route("/condiciones-de-uso", name="termsofuse_LOCALE_es")
     * @Route("/mentions-legales", name="termsofuse_LOCALE_fr")
     * @Route("/termini-e-condizione", name="termsofuse_LOCALE_it")
     *
     * @Template("ChicplaceMobileBundle:Static:terms_of_use.html.twig")
     */
    public function termsofuseAction()
    {
        return array();
    }


    /**
     * Static Delivery page
     *
     * @return array
     *
     * @Route("/delivery", name = "delivery")
     * @Route("/entrega", name="delivery_LOCALE_es")
     * @Route("/livraison", name="delivery_LOCALE_fr")
     * @Route("/spedizione", name="delivery_LOCALE_it")
     * @Template()
     */
    public function deliveryAction()
    {
        return array();
    }


    /**
     * Static Secure Payment page
     *
     * @return array
     *
     * @Route("/securepayment", name="securepayment")
     * @Route("/pago-seguro", name="securepayment_LOCALE_es")
     * @Route("/paiement-securise", name="securepayment_LOCALE_fr")
     * @Route("/pagamento-sicuro", name="securepayment_LOCALE_it")
     *
     * @Template("ChicplaceMobileBundle:Static:secure_payment.html.twig")
     */
    public function securepaymentAction()
    {
        return array();
    }


    /**
     * Static Privacy Policy page
     *
     * @return array
     *
     * @Route("/privacypolicy", name="privacypolicy")
     * @Route("/privacidad", name="privacypolicy_LOCALE_es")
     * @Route("/confidentialite", name="privacypolicy_LOCALE_fr")
     * @Route("/riservatezza", name="privacypolicy_LOCALE_it")
     *
     * @Template("ChicplaceMobileBundle:Static:privacy_policy.html.twig")
     */
    public function privacypolicyAction()
    {
        return array();
    }

}