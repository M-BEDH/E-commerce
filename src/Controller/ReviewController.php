<?php
// Contrôleur pour la gestion des avis (reviews) sur les produits

namespace App\Controller;

use App\Entity\User;
use App\Entity\Review;
use App\Entity\Product;
use App\Form\ReviewType;
use App\Repository\ReviewRepository;
use App\Repository\ProductRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\CurrentUser;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;

// Définition de la route principale pour les avis
#[Route('/review')]
final class ReviewController extends AbstractController
{
    // Affiche tous les avis d'un produit donné par son slug
    #[Route('/product/{slug}/reviews', name: 'app_review_index')]
    public function productReviews(ProductRepository $productRepository, ReviewRepository $reviewRepository, string $slug): Response
    {
        // Recherche du produit par son slug
        $product = $productRepository->findOneBy(['slug' => $slug]);
        if (!$product) {
            // Gestion du cas où le produit n'existe pas
            throw $this->createNotFoundException('Produit non trouvé');
        }
        // Récupération des avis liés au produit
        $reviews = $reviewRepository->findBy(['product' => $product]);

        // Affichage de la vue avec les avis et le produit
        return $this->render('review/index.html.twig', [
            'reviews' => $reviews,
            'product' => $product,
        ]);
    }

    // Route pour créer un nouvel avis ou éditer un avis existant
    #[Route('/product/{slug}/new', name: 'app_review_new', methods: ['GET', 'POST'])] #[IsGranted('ROLE_USER')]
    #[Route('/{id}/edit', name: 'app_review_edit', methods: ['GET', 'POST'])]
    #[Route('/product/{slug}/new', name: 'app_review_new', methods: ['GET', 'POST'])]
    public function newOrEdit(
        #[CurrentUser] User $user,
        Request $request,
        EntityManagerInterface $entityManager,
        ProductRepository $productRepository,
        ReviewRepository $reviewRepository,
        string $slug = null,
        Review $review = null
    ): Response {
        // Si $review est null, on est en création d'avis
        if (!$review) {
            // Recherche du produit concerné
            $product = $productRepository->findOneBy(['slug' => $slug]);
            if (!$product) {
                // Gestion du cas où le produit n'existe pas
                throw $this->createNotFoundException('Produit non trouvé');
            }
            // Création d'un nouvel avis lié au produit
            $review = new Review();
            $review->setProduct($product);
            $status = 'ajouté';
            $message = "Ajouter un nouveau commentaire";
        } else {
            // Sinon, on est en édition d'un avis existant
            $status = 'modifié';
            $message = "Modifier le commentaire : " . $review->getId();
        }

        // Création et gestion du formulaire d'avis
        $form = $this->createForm(ReviewType::class, $review);
        $form->handleRequest($request);

        // Si le formulaire est soumis et valide
        if ($form->isSubmitted() && $form->isValid()) {
            // Attribution de l'utilisateur à l'avis
            $review->setUser($user);
            // Sauvegarde de l'avis en base de données
            $entityManager->persist($review);
            $entityManager->flush();

            // Message de succès
            $this->addFlash('success', 'Commentaire ' . $status . ' !');
            // Redirection vers la liste des avis du produit
            return $this->redirectToRoute('app_review_index', [
                'slug' => $review->getProduct()->getSlug()
            ], Response::HTTP_SEE_OTHER);
        }

        // Affichage du formulaire (création ou édition)
        return $this->render('review/new.html.twig', [
            'review' => $review,
            'form' => $form,
            'message' => $message,
        ]);
    }

    // Affiche le détail d'un avis
    #[Route('/{id}', name: 'app_review_show', methods: ['GET'])]
    public function show(#[CurrentUser] User $user, Review $review): Response
    {
        // Affichage de la vue avec les détails de l'avis
        return $this->render('review/show.html.twig', [
            'id' => $review->getId(),
            'review' => $review,
            'user' => $user,
        ]);
    }

    // Supprime un avis
    #[Route('/{id}', name: 'app_review_delete', methods: ['POST'])]
    public function delete(Request $request, Review $review, EntityManagerInterface $entityManager): Response
    {
        // Récupération du slug du produit pour la redirection
        $slug = $review->getProduct()->getSlug();

        // Vérification du token CSRF pour la sécurité
        if ($this->isCsrfTokenValid('delete'.$review->getId(), $request->getPayload()->getString('_token'))) {
            // Suppression de l'avis
            $entityManager->remove($review);
            $entityManager->flush();
        }

        // Redirection vers la liste des avis du produit
        return $this->redirectToRoute('app_review_index', ['slug' => $slug], Response::HTTP_SEE_OTHER);
    }
}