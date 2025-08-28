<?php

namespace App\Controller;

use App\Entity\Review;
use App\Form\ReviewType;
use App\Repository\ReviewRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\CurrentUser;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;

#[Route('/review')]
final class ReviewController extends AbstractController
{
    // #[Route(name: 'app_review_index', methods: ['GET'])]
    // public function index(ReviewRepository $reviewRepository): Response
    // {
    //     return $this->render('review/index.html.twig', [
    //         'reviews' => $reviewRepository->findAll(),
    //     ]);
    // }

    #[IsGtanted('ROLE_USER')]
    #[Route('/{id}/edit', name: 'app_review_edit', methods: ['GET', 'POST'])]
    #[Route('/{sulg}/new', name: 'app_review_new', methods: ['GET', 'POST'])]
    public function new(#[CurrentUser] User $user, Review $review, Request $request, EntityManagerInterface $entityManager): Response
    {
        $status = isset($project) ? "modifié" : "ajouté";

        $review = isset($review) ?"Modifier le commentaire : ".$review->getId() : "Ajouter un nouveau commentaire";
        $review = $review ?? new Review();

        $form = $this->createForm(ReviewType::class, $review);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->persist($review);
            $entityManager->flush();

            
            $this->addFlash('success', 'Review '. $status . ' !');
            return $this->redirectToRoute('app_review_index', ['id' => $review->getId()]);
        }

        return $this->render('review/new.html.twig', [
            'review' => $review,
            'form' => $form,
        ]);
    }

    // #[Route('/{id}', name: 'app_review_show', methods: ['GET'])]
    // public function show(Review $review): Response
    // {
    //     return $this->render('review/show.html.twig', [
    //         'review' => $review,
    //     ]);
    // }

    #[Route('/{id}/edit', name: 'app_review_edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, Review $review, EntityManagerInterface $entityManager): Response
    {
        $form = $this->createForm(ReviewType::class, $review);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->flush();

            return $this->redirectToRoute('app_review_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->render('review/edit.html.twig', [
            'review' => $review,
            'form' => $form,
        ]);
    }

    #[Route('/{id}', name: 'app_review_delete', methods: ['POST'])]
    public function delete(Request $request, Review $review, EntityManagerInterface $entityManager): Response
    {
        if ($this->isCsrfTokenValid('delete'.$review->getId(), $request->getPayload()->getString('_token'))) {
            $entityManager->remove($review);
            $entityManager->flush();
        }

        return $this->redirectToRoute('app_review_index', [], Response::HTTP_SEE_OTHER);
    }
}
