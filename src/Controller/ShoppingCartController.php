<?php

namespace App\Controller;

use App\Entity\User;
use App\Repository\ProductRepository;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\HttpFoundation\Session\SessionInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;

final class ShoppingCartController extends AbstractController
{

     public function __construct(private readonly ProductRepository $productRepository) //private = accessible à l'interieur de la classe
    {

    }

    #[Route('/shopping/cart', name: 'app_shopping_cart', methods: ['GET'])]
    public function index(SessionInterface $session): Response
    {   // recupere les données du panier de la session actuelle
        $cart = $session->get('cart', []);
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

        //rendu de la vue
        return $this->render('shopping_cart/index.html.twig', [
            'item' => $cartWhithData,// retourne sezs deux variables afin de les recuperer dans la vue
            'total' => $total,
        ]);
    }

   

}
