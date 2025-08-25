<?php

namespace App\Controller;

use App\Service\Cart;
use App\Entity\Product;
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

    public function index(SessionInterface $session, Cart $cart): Response
    {  
        $data = $cart->getCart($session);

        return $this->render('shopping_cart/index.html.twig', [
            'items' => $data['cart'],
            'total' => $data['total']
        ]);
    
    }



    #[Route('/add/shopping/cart/{id}', name: 'app_shopping_cart_add', methods: ['GET'])]
    public function addProductCart(int $id, SessionInterface $session, Product $product ): Response
    {
        $stock = $product->getStock();
        // dd($stock);

        $cart = $session->get('cart', []);

        if( !empty($cart[$id])) { // si produit dans le panier on incremente la qqt
            $cart[$id]++;
        } else {
            $cart[$id]=1;
        }

      if($cart[$id] > $stock ) {
        $this->addFlash('warning', 'Pas assez de stock');
        return $this->redirectToRoute('app_home');
        
      } 
            $session->set('cart', $cart); // met à jour le panier dans la session

         $this->addFlash('success', 'Produit ajouté');
       
        return $this->redirectToRoute('app_shopping_cart'); // redirige vers la page panier
    }



     #[Route('/delete/shopping/cart/{id}', name: 'app_shopping_cart_delete', methods: ['GET'])]
    public function deleteProductCart(int $id, SessionInterface $session): Response
    {

        $cart = $session->get('cart', []);
        
             if(!empty($cart[$id])) {
                if($cart[$id] > 1 ){
                    $cart[$id] -- ;
                } else  {
                 unset($cart[$id]);
                }
                $session->set('cart', $cart); // met à jour le panier dans la session
        }



        // $session->set('cart', $cart); // met à jour le panier dans la session
        
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