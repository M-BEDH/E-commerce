<?php

namespace App\Controller;

use DateTimeImmutable;
use App\Entity\Product;
use App\Form\ProductType;
use App\Form\ProductUpdateType;
use App\Entity\AddProductHistory;
use App\Form\AddProductHistoryType;
use App\Repository\ProductRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use App\Repository\AddProductHistoryRepository;
use Symfony\Component\String\Slugger\SluggerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\File\Exception\FileException;

#[Route('/editor/product')]
final class ProductController extends AbstractController
{
    // Affiche la liste des produits
    #[Route(name: 'app_product_index', methods: ['GET'])]
    public function index(ProductRepository $productRepository): Response
    {
        return $this->render('product/index.html.twig', [
            'products' => $productRepository->findAll(),
        ]);
    }

    // Création d'un nouveau produit
    #[Route('/new', name: 'app_product_new', methods: ['GET', 'POST'])]
    public function new(Request $request, EntityManagerInterface $entityManager, SluggerInterface $slugger): Response
    {
        $product = new Product();
        $form = $this->createForm(ProductType::class, $product);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            // Gestion de l'image uploadée
            $image = $form->get('image')->getData();
            if ($image) {
                $originalImageName = pathinfo($image->getClientOriginalName(), PATHINFO_FILENAME);
                $saveImageName = $slugger->slug($originalImageName);
                $newFileImageName = $saveImageName.'_'.uniqid().'.'.$image->guessExtension();

                try {
                    $image->move(
                        $this->getParameter('images_directory'),
                        $newFileImageName
                    );
                } catch (FileException $exception) {
                    // Gestion d'erreur upload image
                }
                $product->setImage($newFileImageName);
            }

            // Persiste le produit
            $entityManager->persist($product);
            $entityManager->flush();

            // Historique d'ajout de stock
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

    // Edition d'un produit existant
    #[Route('/{id}-{slug}/edit', name: 'app_product_edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, Product $product, EntityManagerInterface $entityManager, SluggerInterface $slugger): Response
    {
        $form = $this->createForm(ProductUpdateType::class, $product);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            // Gestion de l'image uploadée
            $image = $form->get('image')->getData();
            if ($image) {
                $originalImageName = pathinfo($image->getClientOriginalName(), PATHINFO_FILENAME);
                $saveImageName = $slugger->slug($originalImageName);
                $newFileImageName = $saveImageName.'_'.uniqid().'.'.$image->guessExtension();
                try {
                    $image->move(
                        $this->getParameter('images_directory'),
                        $newFileImageName
                    );
                } catch (FileException $exception) {
                    // Gestion d'erreur upload image
                }
                $product->setImage($newFileImageName);
            }
            $entityManager->flush();

            $this->addFlash('success', 'Produit modifié avec succès !');
            return $this->redirectToRoute('app_product_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->render('product/edit.html.twig', [
            'product' => $product,
            'form' => $form,
        ]);
    }

    // Suppression d'un produit
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

    // Ajout de stock à un produit (historique)
    #[Route('add/product/{id}', name: 'app_add_product_stock', methods: ['GET', 'POST'])]
    public function stockAdd($id, Request $request, EntityManagerInterface $entityManager, ProductRepository $productRepository): Response
    {
        $stockAdd = new AddProductHistory();
        $form = $this->createForm(AddProductHistoryType::class, $stockAdd);
        $form->handleRequest($request);
        $product = $productRepository->find($id);

        if ($form->isSubmitted() && $form->isValid()) {
            if ($stockAdd->getQuantity() > 0) {
                $newQuantity = $product->getStock() + $stockAdd->getQuantity();
                $product->setStock($newQuantity);

                $stockAdd->setCreatedAt(new DateTimeImmutable());
                $stockAdd->setProduct($product);
                $entityManager->persist($stockAdd);
                $entityManager->flush();

                $this->addFlash('success', 'Stock ajouté avec succès !');
                return $this->redirectToRoute('app_product_index');
            } else {
                $this->addFlash('danger', 'La quantité doit être supérieure à 0 !');
                return $this->redirectToRoute('app_add_product_stock', ['id' => $product->getId()]);
            }
        }

        return $this->render('product/addStock.html.twig', [
            'form' => $form->createView(),
            'product' => $product,
            'stockAdd' => $stockAdd,
        ]);
    }

    // Affiche l'historique d'ajout de stock pour un produit
    #[Route('/add/product/{id}/stock/history', name: 'app_product_stock_add_history', methods: ['GET'])]
    public function showStockHistory($id, ProductRepository $productRepository, AddProductHistoryRepository $addProductHistoryRepository): Response
    {
        $product = $productRepository->find($id);
        $productAddHistory = $addProductHistoryRepository->findBy(['product' => $product], ['id' => 'DESC']);

        return $this->render('product/stockHistory.html.twig', [
            'productsAdded' => $productAddHistory
        ]);
    }

    // Affiche le détail d'un produit
    #[Route('/{id}', name: 'app_product_show', methods: ['GET'])]
    public function show(Product $product): Response
    {
        return $this->render('product/show.html.twig', [
            'product' => $product,
        ]);
    }
}