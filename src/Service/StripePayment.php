<?php

namespace App\Service;

use Stripe\Stripe;
use Stripe\Checkout\Session;

class StripePayment
{
    // Propriété pour stocker l'URL de redirection Stripe après création de la session de paiement
    private $redirectUrl;

    // Constructeur : initialise la clé secrète Stripe et la version de l'API
    public function __construct()
    {
        // Définit la clé secrète Stripe à partir des variables d'environnement
        Stripe::setApiKey($_SERVER['STRIPE_SECRET_KEY']);
        // Définit la version de l'API Stripe à utiliser
        Stripe::setApiVersion('2025-07-30.basil');
    }

    /**
     * Démarre une session de paiement Stripe avec le panier, les frais de livraison et l'ID de la commande.
     * @param array $cart Le panier de l'utilisateur (produits et quantités)
     * @param float $shippingCost Le coût de la livraison
     * @param int $orderId L'identifiant de la commande à associer au paiement
     */
    public function startPayment($cart, $shippingCost, $orderId)
    {
        // Récupère les produits du panier
        $cartProducts = $cart['cart'];

        // Initialise le tableau des produits à envoyer à Stripe, en commençant par les frais de livraison
        $products = [
            [
                'qte' => 1,
                'price' => $shippingCost,
                'name' => 'Frais de livraison'
            ]
        ];

        // Ajoute chaque produit du panier au tableau des produits Stripe
        foreach ($cartProducts as $value) {
            $productItem = [];
            $productItem['name'] = $value['product']->getName();
            $productItem['price'] = $value['product']->getPrice();
            $productItem['qte'] = $value['quantity'];

            $products[] = $productItem;
        }

        // Crée une session de paiement Stripe avec tous les produits et les paramètres nécessaires
        $session = Session::create([
            'line_items' => [
                // Pour chaque produit, on prépare les informations attendues par Stripe
                array_map(fn(array $product) => [
                    'quantity' => $product['qte'],
                    'price_data' => [
                        'currency' => 'Eur',
                        'product_data' => [
                            'name' => $product['name']
                        ],
                        // Stripe attend le montant en centimes
                        'unit_amount' => $product['price'] * 100,
                    ],
                ], $products)
            ],
            'mode' => 'payment',
            // URL de redirection en cas d'annulation du paiement
            'cancel_url' => 'http://127.0.0.1:8000/pay/cancel',
            // URL de redirection en cas de succès du paiement
            'success_url' => 'http://127.0.0.1:8000/pay/success',
            // Demande l'adresse de facturation
            'billing_address_collection' => 'required',
            // Limite la livraison à la France
            'shipping_address_collection' => [
                'allowed_countries' => ['FR'],
            ],
            // Ajoute l'ID de la commande dans les métadonnées Stripe pour le suivi
            'payment_intent_data' => [
                'metadata' => [
                 'orderid' => $orderId,
                ]
            ]
        ]);

        // Stocke l'URL de redirection Stripe pour l'utiliser après
        $this->redirectUrl = $session->url;
    }

    // Retourne l'URL de redirection Stripe générée lors de la création de la session de paiement
    public function getStripeRedirectUrl()
    {
        return $this->redirectUrl;
    }
}