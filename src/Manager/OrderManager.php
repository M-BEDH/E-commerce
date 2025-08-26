<?php


namespace App\Manager;

use App\Entity\Order;
use Doctrine\ORM\EntityManagerInterface;


class OrderManager
{
    private $em;

    public function __construct(EntityManagerInterface $em){
        $this->em = $em;
    }

    public function stockUpdate(Order $order){
        $this->em->refresh($order);

        foreach($order->getOrderProducts() as $orderProduct) {
            $quantity = $orderProduct->getQuantity();
            $product = $orderProduct->getProduct();
            $stock = $product->getStock();

            $updateStock = $stock - $quantity;
            $product->setStock($updateStock);
        };



    }
}