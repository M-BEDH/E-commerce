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

    // Injection du ProductRepository via le constructeur (utilisé pour accéder aux produits)
    public function __construct(private readonly ProductRepository $productRepository) //private = accessible à l'intérieur de la classe
    {

    }

    // Affiche le contenu du panier
    #[Route('/shopping/cart', name: 'app_shopping_cart', methods: ['GET'])]
    public function index(SessionInterface $session, Cart $cart): Response
    {  
        // Récupère les données du panier depuis la session via le service Cart
        $data = $cart->getCart($session);

        // Affiche la page du panier avec les articles et le total
        return $this->render('shopping_cart/index.html.twig', [
            'items' => $data['cart'],
            'total' => $data['total']
        ]); 
    }

    // Ajoute un produit au panier
    #[Route('/add/shopping/cart/{slug:product}', name: 'app_shopping_cart_add', methods: ['GET'])]
    public function addProductCart(SessionInterface $session, Product $product ): Response
    {
        // Récupère l'ID et le stock du produit à ajouter
        $id = $product->getId();
        $stock = $product->getStock();

        // Récupère le panier actuel depuis la session (ou tableau vide si inexistant)
        $cart = $session->get('cart', []);

        // Si le produit est déjà dans le panier, on incrémente la quantité
        if( !empty($cart[$id])) {
            $cart[$id]++;
        } else {
            // Sinon, on ajoute le produit avec une quantité de 1
            $cart[$id]=1;
        }

        // Vérifie que la quantité demandée ne dépasse pas le stock disponible
        if($cart[$id] > $stock ) {
            $this->addFlash('warning', 'Pas assez de stock');
            return $this->redirectToRoute('app_shopping_cart');
        } 

        // Met à jour le panier dans la session
        $session->set('cart', $cart);

        // Message de succès pour l'utilisateur
        $this->addFlash('success', 'Produit ajouté');
       
        // Redirige vers la page du panier
        return $this->redirectToRoute('app_shopping_cart');
    }

    // Diminue la quantité d'un produit dans le panier ou le retire si quantité = 1
    #[Route('/delete/shopping/cart/{id}', name: 'app_shopping_cart_delete', methods: ['GET'])]
    public function deleteProductCart(int $id, SessionInterface $session): Response
    {
        // Récupère le panier depuis la session
        $cart = $session->get('cart', []);

        // Si le produit existe dans le panier
        if(!empty($cart[$id])) {
            if($cart[$id] > 1 ){
                // Diminue la quantité de 1
                $cart[$id] -- ;
            } else  {
                // Sinon, retire complètement le produit du panier
                unset($cart[$id]);
            }
            // Met à jour le panier dans la session
            $session->set('cart', $cart);
        }

        // Message d'information pour l'utilisateur
        $this->addFlash('danger', 'Produit supprimé !');
        // Redirige vers la page du panier
        return $this->redirectToRoute('app_shopping_cart');
    }

    // Retire complètement un produit du panier (peu importe la quantité)
    #[Route('/delete/cart/{id}', name: 'app_cart_delete_product_id', methods: ['GET'])]
    public function deleteCartProduct(int $id, SessionInterface $session): Response
    {
        // Récupère le panier depuis la session
        $cart = $session->get('cart', []);

        // Si le produit existe dans le panier, on le retire
        if( !empty($cart[$id])) { 
            unset($cart[$id]);
            $session->set('cart', $cart); 
        }
        // Redirige vers la page du panier
        return $this->redirectToRoute('app_shopping_cart');
    }

    // Vide entièrement le panier
    #[Route('/delete/cart', name: 'app_cart_delete', methods: ['GET'])]
    public function deleteCart(SessionInterface $session): Response
    {
        // Réinitialise le panier dans la session à un tableau vide
        $session->set('cart',[]);

        // Message d'information pour l'utilisateur
        $this->addFlash('danger', 'Panier entièrement supprimé !');

        // Redirige vers la page du panier
        return $this->redirectToRoute('app_shopping_cart');
    }

}