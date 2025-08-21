<?php

namespace App\Controller;

use Stripe\Stripe;
use Symfony\Component\Mime\Email;
use App\Repository\OrderRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Mailer\Mailer;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\HttpFoundation\Session\Session;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;

final class StripeController extends AbstractController
{
    #[Route('/stripe', name: 'app_stripe')]
    public function index(): Response
    {
        return $this->render('stripe/index.html.twig', [
            'controller_name' => 'StripeController',
        ]);
    }

    #[Route('/pay/success', name: 'app_stripe_success')]
    public function success(Session $session): Response
    {
        $session->set('cart', []);
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

    #[Route('/stripe/notify', name: "app_stripe_notify")]
    public function stripeNotify(Request $request, OrderRepository $orderRepo, MailerInterface $mailer, EntityManagerInterface $entityManager): Response
    {

        Stripe::setApiKey($_SERVER['STRIPE_SECRET_KEY']);

        $endpoint_secret = $_SERVER['ENDPOINT_KEY'];

        $payload = $request->getContent();

        $sigHeader = $request->headers->get('Stripe-Signature');

        $event = null;

        try {

            $event = \Stripe\Webhook::constructEvent(
                $payload,
                $sigHeader,
                $endpoint_secret
            );

        } catch (\UnexpectedValueException $e) {
            return new Response('Invalid payload', 400);
        } catch (\Stripe\Exception\SignatureVerificationException $e) {
            return new Response('Invalid signature', 400);
        }

        switch ($event->type) {
            case 'payment_intent.succeeded':
                $paymentIntent = $event->data->object;

                 $fileName = 'stripe-detail-' . uniqid() . '.txt'; // permet d'avoir un fichier stripe avec details de la commande

                     file_put_contents($fileName, "payment_intent.succeeded : " . $paymentIntent->id);

                $orderId = $paymentIntent->metadata->orderid;
                $order = $orderRepo->find($orderId);
                $order->setIsPaymentCompleted(1);
                $entityManager->flush();
                     file_put_contents($fileName, $orderId);  


                $order = $orderRepo->findOneBy(['id' => $orderId]);
                $html = $this->renderView('mail/orderConfirm.html.twig', [
                    'order' => $order
                ]);
                $email = (new Email())
                    ->from('soinDeSoi.com')
                    ->to($order->getEmail())
                    ->subject('Confirmation de réception de commande')
                    ->html($html);
                $mailer->send($email);

                break;
            case 'payment_method.attached':
                $paymentMethod = $event->data->object;
                break;
            default :
                    // ne rien faire pour les autres evenements

                          // 🔎 log tous les autres événements
                        $fileName = 'stripe-event-' . uniqid() . '.txt';
                        file_put_contents($fileName, "Event reçu : " . $event->type);


            break;
        }
            return new Response('Evenement reçu avec succès', 200);
    }

}
