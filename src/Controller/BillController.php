<?php

namespace App\Controller;

use Dompdf\Options;
use Dompdf\Dompdf;
use App\Repository\OrderRepository;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;

final class BillController extends AbstractController
{
    #[Route('/bill/{id}', name: 'app_bill')]
    public function index($id, OrderRepository $orderRepository): Response
    {
        $order = $orderRepository->find($id);

        $pdfOptions = new Options();
        $pdfOptions->set('defaultFont', 'Arial');
        $dompPdf = new DomPdf();
        $html = $this->renderView('bill/index.html.twig', [
            'order'=>$order,
        ]);
        $dompPdf->loadHtml($html);
        $dompPdf->render();
        $dompPdf->stream('bill-' .$order->getId().'.pdf', [
            'Attachment'=>false,
        ]);

        return new Response('', 200, [
            'Content-Type' => 'application/pdf',
        ]);
    }
}
