<?php

namespace App\Manager;

use App\Entity\Order;
use Doctrine\ORM\EntityManagerInterface;

class OrderManager
{
    // Propriété pour stocker l'EntityManager
    private $em;

    // Injection de l'EntityManager via le constructeur
    public function __construct(EntityManagerInterface $em){
        $this->em = $em;
    }

    /**
     * Met à jour le stock des produits associés à une commande.
     * Pour chaque produit de la commande, on diminue le stock selon la quantité commandée.
     */
    public function stockUpdate(Order $order){
        // Rafraîchit l'entité Order depuis la base de données pour s'assurer d'avoir les dernières données
        $this->em->refresh($order);

        // Parcourt tous les produits de la commande
        foreach($order->getOrderProducts() as $orderProduct) {
            // Récupère la quantité commandée pour ce produit
            $quantity = $orderProduct->getQuantity();
            // Récupère le produit concerné
            $product = $orderProduct->getProduct();
            // Récupère le stock actuel du produit
            $stock = $product->getStock();

            // Calcule le nouveau stock après la commande
            $updateStock = $stock - $quantity;
            // Met à jour le stock du produit
            $product->setStock($updateStock);
        }
    }
}