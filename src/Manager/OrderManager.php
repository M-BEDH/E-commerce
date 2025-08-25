<?php


namespace App\Manager;

use App\Entity\Order;


class OrderManager
{
    public function stockUpdate(Order $order){

        foreach($order->getOrderProducts() as $orderProduct) {
            $quantity = $orderProduct->getQuantity();
            $product = $orderProduct->getProduct();
            $stock = $product->getStock();

            $updateStock = $stock - $quantity;
            $product->setStock($updateStock);
        };



    }
}