<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class StripeController extends AbstractController
{
    #[Route('/stripe', name: 'app_stripe')]
    public function index(): Response
    {
        return $this->render('stripe/index.html.twig', [
            'controller_name' => 'StripeController',
        ]);
    }

    #[Route('/stripe/payment', name: 'app_stripe_payment')]
    public function payment(): Response
    {
        $stripeSecretKey = $this->getParameter('stripe_sk');
        \Stripe\Stripe::setApiKey($stripeSecretKey);

        try {
            // Calcul du panier (parcours des produits du panier et multiplication de la quantité par le prix)
            $total = 1000; // centimes 10€
            // Create a PaymentIntent with amount and currency
            $paymentIntent = \Stripe\PaymentIntent::create([
                'amount' => $total,
                'currency' => 'eur',
                'automatic_payment_methods' => [
                    'enabled' => true,
                ],
            ]);

            $output = [
                'paymentIntent' => $paymentIntent,
                'clientSecret' => $paymentIntent->client_secret,
            ];

            return new JsonResponse($output);
        } catch (\Error $e) {
            http_response_code(500);
            // echo json_encode(['error' => $e->getMessage()]);
            return new JsonResponse(['error' => $e->getMessage()], 500);
        }
    }
}
