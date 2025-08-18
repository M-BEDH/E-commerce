<?php

namespace App\Controller;

use App\Entity\City;
use App\Entity\Order;
use App\Service\Cart;
use App\Form\OrderType;
use App\Entity\OrderProducts;
use App\Repository\OrderRepository;
use App\Repository\ProductRepository;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\EntityManagerInterface;
use Knp\Component\Pager\PaginatorInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\HttpFoundation\Session\SessionInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;



final class OrderController extends AbstractController
{

    #[Route('/order', name: 'app_order')]
    public function index(
        Request $request,
        SessionInterface $session,
        EntityManagerInterface $entityManager,
        Cart $cart
    ): Response {

        $data = $cart->getCart($session);

        $order = new Order();
        $form = $this->createForm(OrderType::class, $order);
        $form->handleRequest($request);


        if ($form->isSubmitted() && $form->isValid()) {
            if ($order->isPayOnDelivery()) {

                if (!empty($data['total'])) {

                    $order->setTotalPrice(($data['total']));
                    $order->setCreatedAt(new \DateTimeImmutable());
                    $entityManager->persist($order);
                    $entityManager->flush();
                    // dd($data['cart']); pour afficher le console log du panier une fois la commande validé
                    foreach ($data['cart'] as $value) {
                        $orderProduct = new OrderProducts();
                        $orderProduct->setOrder($order);
                        $orderProduct->setProduct($value['product']);
                        $orderProduct->setQuantity($value['quantity']);

                        $entityManager->persist($orderProduct);
                        $entityManager->flush();
                    }
                    $session->set('cart', []); //mise à jour du contenu du panier
                    //redirection vers la page panier
                    return $this->redirectToRoute('order_message');
                }
            }
        }

        return $this->render('order/index.html.twig', [
            'form' => $form->createView(),
            'total' => $data['total'],
        ]);
    }



    #[Route('order/message', name: 'order_message')]
    public function orderMessage(): Response
    {
        return $this->render('order/order_message.html.twig');
    }



    #[Route('/city/{id}/shipping/cost', name: 'app_city_shippng/cost', methods: ['GET', 'POST'])]
    public function cityShippingCost(City $city): Response
    {
        $cityShippingPrice = $city->getShippingCost();
        return new Response(json_encode(['status' => 200, "message" => 'on', 'content' => $cityShippingPrice]));
    }



    #[Route('/editor/order/show', name: 'app_orders_show')]
    public function getAllOrder(OrderRepository $orderRepository, PaginatorInterface $paginator, Request $request): Response
    {

        $orders = $orderRepository->findBy([], ['id' => 'DESC'] );
        $orders = $paginator->paginate(
            $orders,
            $request->query->getInt('page', 1), //met en place la pagination
            4 // je choisi d'afficher 6 commandes par page
        );

        return $this->render('order/orders.html.twig', [
            'orders' => $orders
        ]);
    }

    

    #[Route('/editor/order/{id}/isCompleted/update', name: 'app_orders_is-completed-udapte')]
    public function isCompleted($id, OrderRepository $orderRepository, EntityManagerInterface $entityManager)
    {
        $order = $orderRepository->find($id);
        $order->setIsCompleted(true);
        $entityManager->flush();
        $this->addFlash('success', 'Commande livrée');
        return $this->redirectToRoute('app_orders_show');
    }



    #[Route('/editor/order/{id}/isCompleted/delete', name: 'app_orders_delete')]
    public function deleteOrder($id, OrderRepository $orderRepository, EntityManagerInterface $entityManager)
    {
        $order = $orderRepository->find($id);
        $entityManager->remove($order);
        $entityManager->flush();
        $this->addFlash('danger', 'Commande supprimée');
        return $this->redirectToRoute('app_orders_show');
    }
}
