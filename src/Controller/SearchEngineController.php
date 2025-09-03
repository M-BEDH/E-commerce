<?php

namespace App\Controller;

use App\Repository\ProductRepository;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\String\Slugger\SluggerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;

final class SearchEngineController extends AbstractController
{
    // Route pour la recherche de produits via le moteur de recherche
    #[Route('/search/engine', name: 'app_search_engine', methods: ['GET','POST'])]
    public function index(Request $request, ProductRepository $productRepo, SluggerInterface $slug): Response
    {
        // Si la requête est de type GET
        if ($request->isMethod('GET')){
            // Récupère toutes les données de la requête (paramètres GET)
            $data = $request->query->all();
            // Récupère le mot-clé de la recherche depuis les paramètres
            $word = $data['word'];
            
            // Appelle la méthode searchEngine du repository pour effectuer la recherche
            $results = $productRepo->searchEngine($word);
            // dd($results); // Débogage éventuel
        }
        // Si aucun résultat n'est trouvé, affiche un message d'avertissement et redirige vers la page d'accueil
        if ($results == []) {  
            $this->addFlash('warning', 'Ce produit n\'existe pas !');
              return $this->redirectToRoute('app_home', [], Response::HTTP_SEE_OTHER);
        }

        // Affiche la page de résultats de recherche avec les produits trouvés et le mot-clé utilisé
        return $this->render('search_engine/index.html.twig', [
            'products' => $results,
            'word' => $word
            
        ]);
    }
}