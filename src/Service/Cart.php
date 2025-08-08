<?php

namespace App\Service;

use App\Repository\ProductRepository;

class Cart {
    public function __construct(private readonly ProductRepository $productRepository) {

    }

    public function getCart($session):array{

        $cart = $session->get('cart',[]);

        //initialisation tableau pour stoker les données du panier
        $cartWhithData = [];
        // boucle sur les elements du panier pour recuperer les infos du produit
        foreach ($cart as $id => $quantity) {

            //recupere le produit correspondant à l'id et à la quantite
            $cartWhithData[] = [
                'product' => $this->productRepository->find($id), //recupere le produit via l'id
                'quantity' =>$quantity // quantite correspondante
            ];
        }
        // calcul total du panier
        $total = array_sum(array_map(function ($item) {
            // pour chaque element du panier , multipli le prix par la quantite
            return $item['product']->getPrice() * $item['quantity'];

        }, $cartWhithData));

        return [
            'cart' => $cartWhithData,// retourne ses deux variables afin de les recuperer dans la vue
            'total' => $total,
        ];
    }


}