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
    /**
     * Génère et affiche la facture PDF pour une commande donnée.
     *
     * @param int $id L'identifiant de la commande
     * @param OrderRepository $orderRepository Le repository des commandes
     */
    #[Route('/bill/{id}', name: 'app_bill')]
    public function index($id, OrderRepository $orderRepository): Response
    {
        // Récupère la commande par son identifiant
        $order = $orderRepository->find($id);

        // Configure les options du PDF (police par défaut)
        $pdfOptions = new Options();
        $pdfOptions->set('defaultFont', 'Arial');

        // Crée une nouvelle instance Dompdf
        $dompPdf = new DomPdf();

        // Génère le HTML à partir du template Twig
        $html = $this->renderView('bill/index.html.twig', [
            'order' => $order,
        ]);

        // Charge le HTML dans Dompdf
        $dompPdf->loadHtml($html);

        // Génère le PDF
        $dompPdf->render();

        // Affiche le PDF dans le navigateur sans téléchargement automatique
        $dompPdf->stream('bill-' . $order->getId() . '.pdf', [
            'Attachment' => false,
        ]);

        // Retourne une réponse vide avec le bon type MIME
        return new Response('', 200, [
            'Content-Type' => 'application/pdf',
        ]);
    }
}