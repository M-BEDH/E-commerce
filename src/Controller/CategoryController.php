<?php

namespace App\Controller;

use App\Entity\Categories;
use App\Form\CategoryFromType;
use App\Repository\CategoriesRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;

final class CategoryController extends AbstractController
{
    // Route pour afficher toutes les catégories
    #[Route('/admin/category', name: 'app_category')]
    public function category(CategoriesRepository $categoryRepo): Response
    {
        // Récupération de toutes les catégories depuis le repository
        $categories = $categoryRepo->findAll();
        
        // Envoi des catégories à la vue Twig
        return $this->render('category/category.html.twig', [
            'categories' => $categories
        ]);
    }


    // Route pour créer une nouvelle catégorie
    #[Route('/category/new', name: 'app_category_new')]
    public function newCategory(Request $request, EntityManagerInterface $entityManager): Response
    {
        // Création d'une nouvelle instance de Categories
        $category = new Categories();

        // Création du formulaire associé à l'entité
        $form = $this->createForm(CategoryFromType::class, $category);
        $form->handleRequest($request);

        // Si le formulaire est soumis et valide
        if ($form->isSubmitted() && $form->isValid()) {
            // Enregistre la nouvelle catégorie en BDD
            $entityManager->persist($category);
            $entityManager->flush();
         
            // Message flash de confirmation
            $this->addFlash('info', 'Categorie créée avec succès !');

            // Redirection vers la liste des catégories
            return $this->redirectToRoute('app_category');
        }

        // Affichage du formulaire dans la vue
        return $this->render('category/newCategory.html.twig', [
            'form' => $form->createView()
        ]);
    }
     
    // Route pour modifier une catégorie existante
    #[Route('/category/{id}/update', name: 'app_category_update')]
    public function editCategory(Request $request, Categories $category, EntityManagerInterface $entityManager): Response
    {
        // $category = $entityManager->getRepository(Categories::class)->find($id); 
        // (Cette ligne est inutile ici car Symfony injecte déjà l'objet Categories correspondant à l'id)  --> grâce à param Converter

        // Création du formulaire pré-rempli avec la catégorie existante
        $form = $this->createForm(CategoryFromType::class, $category);

        // Traitement de la requête
        $form->handleRequest($request);     

        // Si le formulaire est soumis et valide, on enregistre les modifications
        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->flush();

            // Message flash de confirmation
            $this->addFlash('success', 'Modification réussie!');

            // Redirection vers la liste
            return $this->redirectToRoute('app_category');
        }

        // Affichage du formulaire
        return $this->render('category/updateCategory.html.twig', [
            'form' => $form->createView(),
        ]);
    }

   
    // Route pour supprimer une catégorie
    #[Route('/category/delete/{id}', name: 'app_category_delete')]
    public function deleteCategory(Categories $category, EntityManagerInterface $entityManager): Response
    {
        // $category = $entityManager->getRepository(Categories::class)->find($id);    
        // (idem, Symfony récupère déjà la catégorie par son id)

        // Suppression de la catégorie
        $entityManager->remove($category);
        $entityManager->flush();
            
        // Message flash pour confirmer la suppression
        $this->addFlash('danger','Suppression réussie !');
            
        // Redirection vers la liste
        return $this->redirectToRoute('app_category');
    }
}
