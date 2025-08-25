<?php

namespace App\Controller;

use App\Repository\ProductRepository;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;

final class SearchEngineController extends AbstractController
{
    #[Route('/search/engine', name: 'app_search_engine', methods: ['GET','POST'])]
    public function index(Request $request, ProductRepository $productRepo): Response
    {
        if ($request->isMethod('GET')){
            //recupere les données de la requete
            $data = $request->query->all();
            //recupere le mot-cle de la recherche
            $word = $data['word'];
            
            //appelle la method searchEngine du repo productRepository
            $results = $productRepo->searchEngine($word);
// dd($results);
            
        }
        if ($results == []) {  
            $this->addFlash('warning', 'Ce produit n\'existe pas !');
              return $this->redirectToRoute('app_home', [], Response::HTTP_SEE_OTHER);
        }

        return $this->render('search_engine/index.html.twig', [
            'products' => $results,
            'word' => $word
            
        ]);
    }
}