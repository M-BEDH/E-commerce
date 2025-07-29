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
    #[Route('/category', name: 'app_category')]
    public function category(CategoriesRepository $categoryRepo): Response
    {
         $categories = $categoryRepo->findAll();
        
        return $this->render('category/category.html.twig', [
            'categories' => $categories
        ]);
    }


    #[Route('/category/new', name: 'app_category_new')]
    public function newCategory(Request $request, EntityManagerInterface $entityManager): Response
    {

        $category = new Categories();

        $form = $this->createForm(CategoryFromType::class, $category);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->persist($category);
            $entityManager->flush();
         
        $this->addFlash('success', 'Categorie créée avec succes !');
        return $this->redirectToRoute('app_category');

    }

     return $this->render('category/newCategory.html.twig', [
            'form' => $form->createview()

    ]);

    }
     
    #[Route('/category/update/{id}', name: 'app_category_update')]
    public function editCategory(Request $request, $id,  EntityManagerInterface $entityManager): Response
    {
        $category = $entityManager->getRepository(Categories::class)->find($id);
        $form = $this->createForm(CategoryFromType::class, $category);

        $form->handleRequest($request);     

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->flush();
            $this->addFlash('success', 'Categorie modifiée avec succès !');

            return $this->redirectToRoute('app_category');
        }
        return $this->render('category/updateCategory.html.twig', [
            'form' => $form->createView(),
        ]);
    }
       
    #[Route('/category/delete/{id}', name: 'app_category_delete')]
    public function deleteCategory($id, EntityManagerInterface $entityManager): Response
    {
        $category = $entityManager->getRepository(Categories::class)->find($id);    
          $entityManager->remove($category);
            $entityManager->flush();
            
            $this->addFlash('notice','suppression réussie !!');
            
            return $this->redirectToRoute('app_category');
          }
}
