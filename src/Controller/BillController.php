<?php

namespace App\Controller;

use App\Repository\OrderRepository;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;

final class BillController extends AbstractController
{
    #[Route('/bill/{id}', name: 'app_bill')]
    public function index($id, OrderRepository $orderReposotory): Response
    {
        $order = $orderReposotory->find($id);
        return $this->render('bill/index.html.twig', [
            'order'=>$order,
            'id'=>$id,
        ]);
    }
}
