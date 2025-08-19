<?php

namespace App\Controller;

use Stripe\Stripe;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Stripe\Exception\SignatureVerifcationException;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;

final class StripeController extends AbstractController
{
    #[Route('/pay/success', name: 'app_stripe_success')]
    public function success(): Response
    {
        return $this->render('stripe/success.html.twig', [
            'controller_name' => 'StripeController',
        ]);
    }


    #[Route('/pay/cancel', name: 'app_stripe_cancel')]
    public function cancel(): Response
    {
        return $this->render('stripe/cancel.html.twig', [
            'controller_name' => 'StripeController',
        ]);
    }


     #[Route('/stripe/notify', name: 'app_stripe_notify')]

    public function stripeNotify(Request $request): Response
    {

        Stripe::setApiKey($_SERVER['STRIPE_SECRET_KEY']);

        $endpoint_secret = $_SERVER['ENDPOINT_KEY'];

        $payload = $request->getContent();

        $sigHeader = $request->headers->get('Stripe-Signature');

        $event = null;

        try {

            $event = \Stripe\Webhook::constructEvent(
                $payload, $sigHeader, $endpoint_secret
            );

        } catch (\UnexpectedValueException $e) {

            return new Response('Invalid payload', 4000);

        } catch (\Stripe\Exception\SignatureVerificationException $e) {

            return new Response('Invalid Signature', 400);
        }

        switch ($event->type) {
            case 'payment_intent.succeded':

                $paymentIntent = $event->data->object;

                $fileName = 'stripe-detail-'.uniqid().'txt';
                file_put_contents($fileName, $paymentIntent);

                break;
            case 'payment_method.attached' :    

                $paymentMethod = $event->data->object;
                break;
            default :
                    // ne rien faire pour les autres evenements
            break;
        }
            return new Response('Evenement reçu avec succès', 200);
    }

}
