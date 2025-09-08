<?php

namespace App\Controller;

use App\Entity\City;
use App\Entity\User;
use App\Entity\Order;
use App\Service\Cart;
use App\Entity\Product;
use App\Form\OrderType;
use App\Entity\OrderProducts;
use App\Manager\OrderManager;
use App\Service\StripePayment;
use Symfony\Component\Mime\Email;
use App\Repository\OrderRepository;
use App\Repository\ProductRepository;
use Doctrine\ORM\EntityManagerInterface;
use Knp\Component\Pager\PaginatorInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Session\SessionInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;

/**
 * Contrôleur pour la gestion des commandes.
 */
final class OrderController extends AbstractController
{
    /**
     * Injection du service de mailer via le constructeur.
     */
    public function __construct(private MailerInterface $mailer) {}

    #region order

    /**
     * Page de commande principale.
     * Affiche le formulaire de commande, traite la soumission, gère le paiement et l'envoi d'email.
     */
    #[Route('/order', name: 'app_order')]
    public function index(
        ProductRepository $productRepository,
        Request $request,
        SessionInterface $session,
        EntityManagerInterface $entityManager,
        Cart $cart,
        OrderRepository $orderRepo,
        OrderManager $om
    ): Response {

        $user = $this->getUser();
        // dd($user);

        // Récupère les données du panier depuis la session utilisateur
        $data = $cart->getCart($session);

        // Crée une nouvelle instance de commande
        $order = new Order();

        // Crée le formulaire de commande lié à l'entité Order
        $form = $this->createForm(OrderType::class, $order);
        $form->handleRequest($request);

        // Vérifie si le formulaire a été soumis et est valide
        if ($form->isSubmitted() && $form->isValid()) {

            // Vérifie que le panier n'est pas vide
            if (!empty($data['total'])) {
                // Calcule le prix total (produits + frais de livraison)
                $totalPrice = $data['total'] + $order->getCity()->getShippingCost();
                // dd($this->getUser());
                $order->setUser($user);
                $order->setTotalPrice($totalPrice);
                $order->setCreatedAt(new \DateTimeImmutable());
                $order->setIsPaymentCompleted(0); // Paiement non effectué par défaut

                // Persiste la commande en base de données
                $entityManager->persist($order);
                $entityManager->flush();

                // Pour chaque produit dans le panier, crée une entité OrderProducts associée à la commande
                foreach ($data['cart'] as $value) {
                    $orderProduct = new OrderProducts();
                    $orderProduct->setOrder($order);
                    $orderProduct->setProduct($value['product']);
                    $orderProduct->setQuantity($value['quantity']);
                    $entityManager->persist($orderProduct);
                    $entityManager->flush();
                }

                // Si le client choisit le paiement à la livraison
                if ($order->isPayOnDelivery()) {
                    // Vide le panier dans la session
                    $session->set('cart', []);

                    // Prépare le contenu HTML de l'email de confirmation de commande
                    $html = $this->renderView('mail/orderConfirm.html.twig', [
                        'order' => $order
                    ]);

                    // Crée et envoie l'email de confirmation au client
                    $email = (new Email())
                        ->from('SoinDeSoi@pm.me')
                        ->to($order->getEmail())
                        ->subject('Confirmation de réception de commande')
                        ->html($html);
                    $this->mailer->send($email);

                    // Met à jour le stock des produits commandés
                    $om->stockUpdate($order);

                    // Sauvegarde les modifications en base de données
                    $entityManager->flush();

                    // Redirige vers la page de confirmation de commande
                    return $this->redirectToRoute('order_message');
                }

                // Si paiement par Stripe (en ligne)
                $paymentStripe = new StripePayment();
                $shippingCost = $order->getCity()->getShippingCost();

                // Lance le processus de paiement Stripe avec les données du panier
                $paymentStripe->startPayment($data, $shippingCost, $order->getId());

                // Récupère l'URL de redirection Stripe
                $stripeRedirectUrl = $paymentStripe->getStripeRedirectUrl();

                // Redirige l'utilisateur vers la page de paiement Stripe
                return $this->redirect($stripeRedirectUrl);
            }
        }

        // Affiche la page de commande avec le formulaire et le total du panier
        return $this->render('order/index.html.twig', [
            'form' => $form->createView(),
            'total' => $data['total'],
        ]);
    }
    #endregion

    #region order message

    /**
     * Affiche la page de confirmation après une commande réussie.
     */
    #[Route('/order_message', name: 'order_message')]
    public function orderMessage(): Response
    {
        // Rend la vue de message de confirmation de commande
        return $this->render('order/order_message.html.twig');
    }
    #endregion

    #region shipping cost

    /**
     * Retourne le coût de livraison pour une ville donnée (appel AJAX).
     */
    #[Route('/city/{id}/shipping/cost', name: 'app_city_shipping_cost')]
    public function cityShippingCost(City $city): Response
    {
        // Récupère le coût de livraison de la ville
        $cityShippingPrice = $city->getShippingCost();

        // Retourne la réponse JSON avec le coût de livraison
        return new Response(json_encode(['status' => 200, "message" => "on", 'content' => $cityShippingPrice]));
    }
    #endregion

    #region order show

    /**
     * Affiche la liste des commandes selon le filtre choisi (toutes, livrées, en attente, etc.).
     * Utilise la pagination pour lister les commandes.
     */
    #[  Route('/editor/order', name: 'app_orders_show_all'),
        Route('/editor/order/{type}', name: 'app_orders_show')
    ]
    public function getAllOrder(?string $type, OrderRepository $orderRepository, PaginatorInterface $paginator, Request $request, Product $product,): Response
    {
        // Filtrage des commandes selon le type passé en paramètre d'URL
        if ($type == 'is-completed') {
            // Commandes livrées
            $orders = $orderRepository->findBy(['isCompleted' => 1], ['id' => "DESC"]);
        } else if ($type == 'pay-on-stripe-not-delivered') {
            // Commandes payées en ligne mais non livrées
            $orders = $orderRepository->findBy(['isCompleted' => null, 'payOnDelivery' => 0, 'isPaymentCompleted' => 1], ['id' => 'DESC']);
        } else if ($type == 'pay-on-stripe-is-delivered') {
            // Commandes payées en ligne et livrées
            $orders = $orderRepository->findBy(['isCompleted' => 1, 'payOnDelivery' => 0, 'isPaymentCompleted' => 1], ['id' => 'DESC']);
        } else if ($type == 'no_delivery') {
            // Commandes non livrées et non payées en ligne
            $orders = $orderRepository->findBy(['isCompleted' => null, 'payOnDelivery' => 1, 'isPaymentCompleted' => 0], ['id' => 'DESC']);
        } else {
            // Toutes les commandes
            $orders = $orderRepository->findAll();
        }


        // Paginer les résultats (12 commandes par page)
        $orders = $paginator->paginate(
            $orders,
            $request->query->getInt('page', 1),
            12
        );

        //    // Récupère les produits (pour affichage dans la vue)
        // $products = [];
        // foreach ($orders as $order) {
        //     foreach ($order->getOrderProducts() as $orderProduct) {
        //         $products[] = $orderProduct->getProduct()->getName();
        //     }
        // }

        // Affiche la vue avec la liste paginée des commandes
        return $this->render('order/orders.html.twig', [
            "orders" => $orders,
            "type" => $type,
            // "products" => $products
        ]);
    }
    #endregion

    #region is completed order

    /**
     * Marque une commande comme livrée (isCompleted = true).
     */
    #[Route('/editor/order/{id}/is-completed/update', name: 'app_orders_is-completed-update')]
    public function isCompletedUpdate(Request $request, $id, OrderRepository $orderRepository, EntityManagerInterface $entityManager)
    {
        // Récupère la commande par son ID
        $order = $orderRepository->find($id);

        // Met à jour le statut de livraison
        $order->setIsCompleted(true);

        // Sauvegarde la modification
        $entityManager->flush();

        // Ajoute un message flash de succès
        $this->addFlash('success', 'Modification effectuée');

        // Redirige vers la liste des commandes livrées
        return $this->redirectToRoute('app_orders_show_all', ["type" =>"is-completed"]);
    }

    /**
     * Supprime une commande par son ID.
     * Redirige selon le type de filtre en cours.
     */
    #[
        Route('/editor/order/{id}/delete', name: 'app_orders_delete'),
        Route('/editor/order/{id}/delete/{type}', name: 'app_orders_delete_type')
    ]
    public function deleteOrder(?string  $type, $id, OrderRepository $orderRepository, EntityManagerInterface $entityManager)
    {
        // Récupère la commande à supprimer
        $order = $orderRepository->find($id);

        // Supprime la commande de la base de données
        $entityManager->remove($order);
        $entityManager->flush();

        // Ajoute un message flash d'information
        $this->addFlash('danger', 'Commande Supprimée');

        // Redirige vers la bonne liste selon le filtre en cours
        if (isset($type)) {
            return $this->redirectToRoute('app_orders_show', ["type" => $type]);
        }
        return $this->redirectToRoute('app_orders_show_all');
    }
}