<?php

namespace App\Service;

use Stripe\Stripe;
use Stripe\Checkout\Session;

class StripePayment
{
    private $redirectUrl;


    public function __construct()
    {

        Stripe::setApiKey($_SERVER['STRIPE_SECRET_KEY']);
        Stripe::setApiVersion('2025-07-30.basil');
    }

    public function startPayment($cart, $shippingCost)
    {
        $cartProducts = $cart['cart'];

        $products = [
            [
                'qte'=> 1,
                'price' => $shippingCost,
                'name' => 'Frais de livraison'
            ]
        ];

        foreach ($cartProducts as $value) {
            $productItem = [];

            $productItem = [];

            $productItem['name'] = $value['product']->getName();
            $productItem['price'] = $value['product']->getPrice();
            $productItem['qte'] = $value['quantity'];

            $products[] = $productItem;
        }

        $session = Session::create(
            [

                'line_items' => [
                    array_map(fn(array $products) => [
                        'quantity' => $products['qte'],
                        'price_data' => [
                            'currency' => 'Eur',
                            'product_data' => [
                                'name' => $products['name']
                            ],
                            'unit_amount' => $products['price'] * 100,
                        ],
                    ], $products)
                ],
                'mode' => 'payment',

                'success_url' => 'https://127.0.0.1:8000/pay/success',
                'cancel_url' => 'https://127.0.0.1:8000/pay/cancel',
               
                'billing_address_collection' => 'required',
                'shipping_address_collection' => [
                    'allowed_countries' => ['FR'],
                ],
                'metadata' => [
                    // 'order_id' => $cart->id, // id de la commande 
                ]
            ],
        );
        $this->redirectUrl = $session->url;
    }
    public function getStripeRedirectUrl()
    {
        return $this->redirectUrl;
    }
}
