<?php

namespace App\Controller;

use App\Entity\SubCategory;
use App\Form\SubCategoryType;
use Doctrine\ORM\EntityManagerInterface;
use App\Repository\SubCategoryRepository;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\String\Slugger\SluggerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;

// Contrôleur pour la gestion des sous-catégories
#[Route('/admin/sub/category')]
#[Route('/sub/category')]
final class SubCategoryController extends AbstractController
{
    // Affiche la liste de toutes les sous-catégories
    #[Route(name: 'app_sub_category_subCategory', methods: ['GET'])]
    public function index(SubCategoryRepository $subCategoryRepository): Response
    {
        // Récupère toutes les sous-catégories et les transmet à la vue
        return $this->render('sub_category/subCategory.html.twig', [
            'sub_categories' => $subCategoryRepository->findAll(),
        ]);
    }

    // Permet de créer une nouvelle sous-catégorie via un formulaire
    #[Route('/new', name: 'app_sub_category_new', methods: ['GET', 'POST'])]
    public function new(Request $request, EntityManagerInterface $entityManager, SluggerInterface $slugger): Response
    {
        // Création d'une nouvelle instance de sous-catégorie
        $subCategory = new SubCategory();

        // Création du formulaire lié à l'entité SubCategory
        $form = $this->createForm(SubCategoryType::class, $subCategory);
        $form->handleRequest($request);

        // Vérifie si le formulaire a été soumis et est valide
        if ($form->isSubmitted() && $form->isValid()) {
            // Enregistre la nouvelle sous-catégorie en base de données
            $entityManager->persist($subCategory);
            $entityManager->flush();

            // Message de succès pour l'utilisateur
            $this->addFlash('info', 'Sous catégorie ajoutée avec succès !');
            // Redirige vers la liste des sous-catégories
            return $this->redirectToRoute('app_sub_category_subCategory', [], Response::HTTP_SEE_OTHER);
        }

        // Affiche le formulaire de création de sous-catégorie
        return $this->render('sub_category/new.html.twig', [
            'sub_category' => $subCategory,
            'form' => $form,
        ]);
    }

    // Affiche le détail d'une sous-catégorie spécifique
    #[Route('/{id}', name: 'app_sub_category_show', methods: ['GET'])]
    public function show(SubCategory $subCategory): Response
    {
        // Affiche la vue de détail pour la sous-catégorie sélectionnée
        return $this->render('sub_category/show.html.twig', [
            'sub_category' => $subCategory,
        ]);
    }

    // Permet de modifier une sous-catégorie existante
    #[Route('/{slug}/edit', name: 'app_sub_category_edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, SubCategoryRepository $subCategoryRepo, EntityManagerInterface $entityManager): Response
    {
        // Recherche la sous-catégorie à éditer par son slug
        $subCategory = $subCategoryRepo->findOneBy(['slug' => $request->get('slug')]);
        
        // Création du formulaire de modification
        $form = $this->createForm(SubCategoryType::class, $subCategory);
        $form->handleRequest($request);

        // Vérifie si le formulaire a été soumis et est valide
        if ($form->isSubmitted() && $form->isValid()) {
            // Sauvegarde les modifications en base de données
            $entityManager->flush();

            // Message de succès pour l'utilisateur
            $this->addFlash('info', 'Sous catégorie modifiée avec succès !');
            // Redirige vers la liste des sous-catégories
            return $this->redirectToRoute('app_sub_category_subCategory', [], Response::HTTP_SEE_OTHER);
        }

        // Affiche le formulaire de modification de la sous-catégorie
        return $this->render('sub_category/edit.html.twig', [
            'sub_category' => $subCategory,
            'form' => $form,
        ]);
    }

    // Permet de supprimer une sous-catégorie
    #[Route('/{id}', name: 'app_sub_category_delete', methods: ['POST'])]
    public function delete(Request $request, SubCategory $subCategory, EntityManagerInterface $entityManager): Response
    {
        // Vérifie la validité du token CSRF pour sécuriser la suppression
        if ($this->isCsrfTokenValid('delete'.$subCategory->getId(), $request->getPayload()->getString('_token'))) {
            // Supprime la sous-catégorie de la base de données
            $entityManager->remove($subCategory);
            $entityManager->flush();
        }

        // Message d'information pour l'utilisateur
        $this->addFlash('info', 'Sous catégorie supprimée avec succès !');
        // Redirige vers la liste des sous-catégories
        return $this->redirectToRoute('app_sub_category_subCategory', [], Response::HTTP_SEE_OTHER);
    }
}