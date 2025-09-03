<?php

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

#[Route('/review')]
final class ReviewController extends AbstractController
{
    #[Route('/product/{slug}/reviews', name: 'app_review_index')]
public function productReviews(ProductRepository $productRepository, ReviewRepository $reviewRepository, string $slug): Response
{
    $product = $productRepository->findOneBy(['slug' => $slug]);
    if (!$product) {
        throw $this->createNotFoundException('Produit non trouvé');
    }
     $reviews = $reviewRepository->findBy(['product' => $product]);

    return $this->render('review/index.html.twig', [
        'reviews' => $reviews,
        'product' => $product,
    ]);
    }

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
        // Si $review est null, on est en création
        if (!$review) {
            $product = $productRepository->findOneBy(['slug' => $slug]);
            if (!$product) {
                throw $this->createNotFoundException('Produit non trouvé');
            }
            $review = new Review();
            $review->setProduct($product);
            $status = 'ajouté';
            $message = "Ajouter un nouveau commentaire";
        } else {
            // Sinon, on est en édition
            $status = 'modifié';
            $message = "Modifier le commentaire : " . $review->getId();
        }

        $form = $this->createForm(ReviewType::class, $review);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $review->setUser($user);
            $entityManager->persist($review);
            $entityManager->flush();

            $this->addFlash('success', 'Commentaire ' . $status . ' !');
            return $this->redirectToRoute('app_review_index', [
                'slug' => $review->getProduct()->getSlug()
            ], Response::HTTP_SEE_OTHER);
        }

        return $this->render('review/new.html.twig', [
            'review' => $review,
            'form' => $form,
            'message' => $message,
        ]);
    }


    #[Route('/{id}', name: 'app_review_show', methods: ['GET'])]
    public function show(#[CurrentUser] User $user, Review $review): Response
    {

        return $this->render('review/show.html.twig', [
            'id' => $review->getId(),
            'review' => $review,
            'user' => $user,
        ]);
    }

    
    #[Route('/{id}', name: 'app_review_delete', methods: ['POST'])]
    public function delete(Request $request, Review $review, EntityManagerInterface $entityManager): Response
    {
        $slug = $review->getProduct()->getSlug();

        if ($this->isCsrfTokenValid('delete'.$review->getId(), $request->getPayload()->getString('_token'))) {
            $entityManager->remove($review);
            $entityManager->flush();
        }

        return $this->redirectToRoute('app_review_index', ['slug' => $slug], Response::HTTP_SEE_OTHER);
    }
}
