<?php

namespace App\Controller;

use App\Entity\Product;
use App\Repository\ReviewRepository;
use App\Repository\ProductRepository;
use App\Repository\CategoriesRepository;
use App\Repository\SubCategoryRepository;
use Knp\Component\Pager\PaginatorInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;

// Contrôleur principal pour la page d'accueil et les produits
final class HomeController extends AbstractController
{
    // Route pour la page d'accueil
    #[Route('/', name: 'app_home')]
    public function index(
        ProductRepository $productRepository,
        CategoriesRepository $categoriesRepository,
        ReviewRepository $reviewRepository,
        Request $request,
        PaginatorInterface $paginator
    ): Response
    {
        // Récupère tous les produits, triés par ID décroissant (plus récents d'abord)
        $data = $productRepository->findby([], ['id' => "DESC"]);

        // Calcul de la note moyenne pour chaque produit
        foreach ($data as $product) {
            // Récupère les avis associés à ce produit
            $reviews = $reviewRepository->findBy(['product' => $product]);
            if (count($reviews) > 0) {
                $sum = 0;
                // Additionne toutes les notes
                foreach ($reviews as $review) {
                    $sum += $review->getNote();
                }
                // Calcule la moyenne et l'arrondit à 1 décimale
                $average = $sum / count($reviews);
                $product->averageRating = round($average, 1); // propriété dynamique ajoutée à l'objet
            } else {
                // Si aucun avis, la note moyenne est nulle
                $product->averageRating = null;
            }
        }

        // Paginer les produits (4 par page)
        $products = $paginator->paginate(
            $data,
            $request->query->getInt('page', 1), // Numéro de page depuis l'URL
            4 // Nombre d'éléments par page
        );

        // Affiche la vue avec les produits paginés et toutes les catégories
        return $this->render('home/home.html.twig', [
            'products' => $products,
            'categories' => $categoriesRepository->findAll()
        ]);
    }

    // Route pour afficher le détail d'un produit
    #[Route('/product/{slug:product}/show', name: 'app_home_product_show', methods: ['GET'])]
    public function showProduct(
        Product $product,
        ProductRepository $productRepository,
        CategoriesRepository $categoriesRepository,
        ReviewRepository $reviewRepository
    ): Response
    {
           // Calcule la note moyenne pour CE produit uniquement
         $reviews = $reviewRepository->findBy(['product' => $product]);
             if (count($reviews) > 0) {
               $sum = 0;
             foreach ($reviews as $review) {
              $sum += $review->getNote();
             }
               $average = $sum / count($reviews);
              $product->averageRating = round($average, 1);
            } else {
              $product->averageRating = null;
            }

        // Récupère les 5 derniers produits ajoutés (pour suggestions ou autres)
        $lastProductAdd = $productRepository->findBy([], ['id' => 'DESC'], 5);

        // Calcul de la note moyenne pour chaque produit de la liste
        foreach ($lastProductAdd as $prod) {
             $reviews = $reviewRepository->findBy(['product' => $prod]);
                 if (count($reviews) > 0) {
               $sum = 0;
        foreach ($reviews as $review) {
            $sum += $review->getNote();
            }
             $average = $sum / count($reviews);
              $prod->averageRating = round($average, 1);
            } else {
              $prod->averageRating = null;
            }
        }


        // Affiche la vue du produit avec suggestions et catégories
        return $this->render('home/showProduct.html.twig', [
            'product' => $product,
            'products' => $lastProductAdd,
            'categories' => $categoriesRepository->findAll()
        ]);
    }

    // Route pour filtrer les produits par sous-catégorie
    #[Route('/product/subCategory/{id}/filter', name: 'app_home_product_filter', methods: ['GET'])]
    public function filter(
        $id,
        SubCategoryRepository $subCategoryRepository,
        CategoriesRepository $categoriesRepository
    ): Response
    {
        // Récupère les produits associés à la sous-catégorie sélectionnée
        $product = $subCategoryRepository->find($id)->getProducts();

        // Récupère la sous-catégorie elle-même
        $subCategory = $subCategoryRepository->find($id);

        // Affiche la vue filtrée avec les produits, la sous-catégorie et toutes les catégories
        return $this->render('home/filter.html.twig', [
            'products' => $product,
            'subCategory' => $subCategory,
            'categories' => $categoriesRepository->findAll()
             ]);
    }
}