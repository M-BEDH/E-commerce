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

    

// ......
  //dd($cartWhithData); // log info
// ......

        //rendu de la vue
        return $this->render('shopping_cart/index.html.twig', [
            'items' => $cartWhithData,// retourne sezs deux variables afin de les recuperer dans la vue
            'total' => $total,
        ]);
    }

    #[Route('/add/shopping/cart/{id}', name: 'app_shopping_cart_add', methods: ['GET'])]
    public function addProductCart(int $id, SessionInterface $session): Response
    {

        $cart = $session->get('cart', []);

        if( !empty($cart[$id])) { // si produit dans le panier on incremente la qqt
            $cart[$id]++;
        } else {
            $cart[$id]=1;
        }
        $session->set('cart', $cart); // met à jour le panier dans la session

        $this->addFlash('success', 'Produit ajouté');
        return $this->redirectToRoute('app_shopping_cart'); // redirige vers la page panier
    }


     #[Route('/delete/shopping/cart/{id}', name: 'app_shopping_cart_delete', methods: ['GET'])]
    public function deleteProductCart(int $id, SessionInterface $session): Response
    {

        $cart = $session->get('cart', []);

        if( !empty($cart[$id])) { 
            unset($cart[$id]);
        }
        $session->set('cart', $cart); // met à jour le panier dans la session
        
         $this->addFlash('danger', 'Produit supprimé !');
        return $this->redirectToRoute('app_shopping_cart'); // 

    }


    #[Route('/delete/cart', name: 'app_cart_delete', methods: ['GET'])]
public function deleteCart(SessionInterface $session): Response
{
    $session->set('cart',[]);

    $this->addFlash('danger', 'Panier entièrement supprimé !');

    return $this->redirectToRoute('app_shopping_cart');
}

}