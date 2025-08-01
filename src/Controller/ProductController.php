<?php

namespace App\Controller;

use DateTimeImmutable;
use App\Entity\Product;
use App\Form\ProductType;
use App\Entity\AddProductHistory;
use App\Repository\ProductRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\String\Slugger\SluggerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\File\Exception\FileException;

#[Route('/editor/product')]
final class ProductController extends AbstractController
{
    #[Route(name: 'app_product_index', methods: ['GET'])]
    public function index(ProductRepository $productRepository): Response
    {
        return $this->render('product/index.html.twig', [
            'products' => $productRepository->findAll(),
        ]);
    }


#region add
    #[Route('/new', name: 'app_product_new', methods: ['GET', 'POST'])]
    public function new(Request $request, EntityManagerInterface $entityManager, SluggerInterface $slugger): Response
    {
        $product = new Product();
        $form = $this->createForm(ProductType::class, $product);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {

            // premet de recuperer l'image unploadée
            $image = $form->get('image')->getData();
            
            //on verifie que l'image existe
            if($image) {
                // on recupere le nom sans l'extention jpg, png ......
                $originalImageName = pathinfo($image->getClientOriginalName(), PATHINFO_FILENAME);

                 // on slugg le nom pour remplacer accent, espaces, ... par un tiret (-)
                $saveImageName = $slugger->slug($originalImageName);
            
                // ajoute un id unique et donc l'extention
                $newFileImageName = $saveImageName.'_'.uniqid().'.'.$image->guessExtension();

                try { //  on deplace l'image dans le dossier defini dans le parametres im_ages directory qui est dans yaml
                    // le parametre images_directory est defini dans config/services.yaml
                    $image->move(
                        $this->getParameter('images_directory'),
                        $newFileImageName);

                       
                } catch (FileException $exception) {
                    
                     // messages d'erreurs si besoin
                } 

                 // on on sauvegarde le nom du fichier dans l'entity
                    $product->setImage($newFileImageName);
                
            }

            $entityManager->persist($product);
            $entityManager->flush();
            
            // On crée un hisorique de l'ajout des produits
            $stockHistory = new AddProductHistory();
            $stockHistory->setQuantity($product->getStock());
            $stockHistory->setProduct($product);
            $stockHistory->setCreatedAt(new DateTimeImmutable());
            $entityManager->persist($stockHistory);
            $entityManager->flush();

            $this->addFlash('success', 'Produit créé avec succès !');
            return $this->redirectToRoute('app_product_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->render('product/new.html.twig', [
            'product' => $product,
            'form' => $form,
        ]);
    }
#endregion


#region show
    #[Route('/{id}', name: 'app_product_show', methods: ['GET'])]
    public function show(Product $product): Response
    {
        return $this->render('product/show.html.twig', [
            'product' => $product,
        ]);
    }

    #[Route('/{id}/edit', name: 'app_product_edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, Product $product, EntityManagerInterface $entityManager): Response
    {
        $form = $this->createForm(ProductType::class, $product);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->flush();

             $this->addFlash('success', 'Produit modifié avec succès !');
            return $this->redirectToRoute('app_product_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->render('product/edit.html.twig', [
            'product' => $product,
            'form' => $form,
        ]);
    }
#endregion


#region delete
    #[Route('/{id}', name: 'app_product_delete', methods: ['POST'])]
    public function delete(Request $request, Product $product, EntityManagerInterface $entityManager): Response
    {
        if ($this->isCsrfTokenValid('delete'.$product->getId(), $request->getPayload()->getString('_token'))) {
            $entityManager->remove($product);
            $entityManager->flush();
        }

         $this->addFlash('danger', 'Produit supprimé avec succès !');
        return $this->redirectToRoute('app_product_index', [], Response::HTTP_SEE_OTHER);
    }
#endregion

#region addProductHistory

#[Route('add/product/{id}', name: 'app_add_product_stock_history', methods: ['GET', 'POST'])]
    public function stockAdd(Request $request, EntityManagerInterface $entityManager) : Response 
    {
        $stockAdd = new AddProductHistory();
        $form = $this->createForm(AddProductHistoryType::class, $stockAdd);
        $form->handleRequest($request); 

        return $this->render('product/add_product_history.html.twig', [
            'form' => $form->createView(),
        ]);
    }
    #endregion

}
