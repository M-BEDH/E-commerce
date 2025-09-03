<?php

namespace App\Controller;

use Stripe\Stripe;
use App\Manager\OrderManager;
use Symfony\Component\Mime\Email;
use App\Repository\OrderRepository;
use Symfony\Component\Mailer\Mailer;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\HttpFoundation\Session\Session;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;

final class StripeController extends AbstractController
{
    // Affiche la page d'accueil Stripe (peut servir de test ou de point d'entrée)
    #[Route('/stripe', name: 'app_stripe')]
    public function index(): Response
    {
        return $this->render('stripe/index.html.twig', [
            'controller_name' => 'StripeController',
        ]);
    }

    // Affiche la page de succès après un paiement Stripe réussi
    #[Route('/pay/success', name: 'app_stripe_success')]
    public function success(Session $session): Response
    {
        // Vide le panier dans la session après paiement réussi
        $session->set('cart', []);
        return $this->render('stripe/success.html.twig', [
            'controller_name' => 'StripeController',
        ]);
    }

    // Affiche la page d'annulation si le paiement Stripe est annulé
    #[Route('/pay/cancel', name: 'app_stripe_cancel')]
    public function cancel(): Response
    {
        return $this->render('stripe/cancel.html.twig', [
            'controller_name' => 'StripeController',
        ]);
    }

    // Point d'entrée pour les notifications Stripe (webhook)
    #[Route('/stripe/notify', name: "app_stripe_notify")]
    public function stripeNotify(Request $request, OrderRepository $orderRepo, MailerInterface $mailer, EntityManagerInterface $entityManager, OrderManager $om): Response
    {
        // Initialise la clé secrète Stripe à partir des variables d'environnement
        Stripe::setApiKey($_SERVER['STRIPE_SECRET_KEY']);

        // Récupère la clé secrète du endpoint pour vérifier la signature du webhook
        $endpoint_secret = $_SERVER['ENDPOINT_KEY'];

        // Récupère le contenu brut de la requête (payload Stripe)
        $payload = $request->getContent();

        // Récupère la signature Stripe envoyée dans les headers
        $sigHeader = $request->headers->get('Stripe-Signature');

        $event = null;

        try {
            // Vérifie la validité de la requête Stripe (signature et payload)
            $event = \Stripe\Webhook::constructEvent(
                $payload,
                $sigHeader,
                $endpoint_secret
            );
        } catch (\UnexpectedValueException $e) {
            // Payload invalide
            return new Response('Invalid payload', 400);
        } catch (\Stripe\Exception\SignatureVerificationException $e) {
            // Signature invalide
            return new Response('Invalid signature', 400);
        }

        // Traite les différents types d'événements Stripe
        switch ($event->type) {
            case 'payment_intent.succeeded':
                // Paiement réussi
                $paymentIntent = $event->data->object;

                // Récupère l'ID de la commande depuis les métadonnées Stripe
                $orderId = $paymentIntent->metadata->orderid;
                $order = $orderRepo->find($orderId);

                // Marque la commande comme payée
                $order->setIsPaymentCompleted(1);

                // Met à jour le stock des produits commandés
                $om->stockUpdate($order);

                // Sauvegarde les modifications en base de données
                $entityManager->flush();

                // Récupère à nouveau la commande pour l'email de confirmation
                $order = $orderRepo->findOneBy(['id' => $orderId]);
                $html = $this->renderView('mail/orderConfirm.html.twig', [
                    'order' => $order
                ]);
                // Prépare et envoie l'email de confirmation de commande
                $email = (new Email())
                    ->from('soinDeSoi.com')
                    ->to($order->getEmail())
                    ->subject('Confirmation de réception de commande')
                    ->html($html);
                $mailer->send($email);

                break;
            case 'payment_method.attached':
                // Un moyen de paiement a été attaché à un client (peut être ignoré ici)
                $paymentMethod = $event->data->object;
                break;
            default :
                // Pour tous les autres événements Stripe, on peut éventuellement les logger
                // Exemple : file_put_contents('stripe-event.txt', "Event reçu : " . $event->type);
            break;
        }
        // Répond à Stripe que l'événement a bien été reçu et traité
        return new Response('Evenement reçu avec succès', 200);
    }

}