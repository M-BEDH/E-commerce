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
    /**
     * Affiche la liste de tous les produits enregistrés dans la base de données.
     * Cette méthode récupère tous les produits via le repository et les transmet à la vue.
     */
    #[Route(name: 'app_product_index', methods: ['GET'])]
    public function index(ProductRepository $productRepository): Response
    {
        // Récupération de tous les produits
        $products = $productRepository->findAll();

        // Affichage de la vue avec la liste des produits
        return $this->render('product/index.html.twig', [
            'products' => $products,
        ]);
    }

    /**
     * Permet de créer un nouveau produit via un formulaire.
     * Gère également l'upload de l'image et l'enregistrement de l'historique de stock initial.
     */
    #[Route('/new', name: 'app_product_new', methods: ['GET', 'POST'])]
    public function new(Request $request, EntityManagerInterface $entityManager, SluggerInterface $slugger): Response
    {
        // Création d'une nouvelle instance de produit
        $product = new Product();

        // Création du formulaire lié à l'entité Product
        $form = $this->createForm(ProductType::class, $product);
        $form->handleRequest($request);

        // Vérification de la soumission et de la validité du formulaire
        if ($form->isSubmitted() && $form->isValid()) {
            // Récupération de l'image uploadée depuis le formulaire
            $image = $form->get('image')->getData();
            if ($image) {
                // Génération d'un nom de fichier unique et sécurisé pour l'image
                $originalImageName = pathinfo($image->getClientOriginalName(), PATHINFO_FILENAME);
                // Génère une version "slugifiée" (sans espaces, caractères spéciaux, etc.) du nom original
                // de l'image pour garantir un nom de fichier sûr et compatible avec le système de fichiers
                $saveImageName = $slugger->slug($originalImageName);
                $newFileImageName = $saveImageName.'_'.uniqid().'.'.$image->guessExtension();

                try {
                    // Déplacement du fichier image dans le dossier configuré
                    $image->move(
                        $this->getParameter('images_directory'),
                        $newFileImageName
                    );
                } catch (FileException $exception) {
                    // Gestion des erreurs lors de l'upload de l'image
                    // Un message d'erreur peut être affiché à l'utilisateur si besoin
                }
                // Enregistrement du nom de l'image dans l'entité Product
                $product->setImage($newFileImageName);
            }

            // Persistance du nouveau produit en base de données
            $entityManager->persist($product);
            $entityManager->flush();

            // Création d'un historique d'ajout de stock initial pour ce produit
            $stockHistory = new AddProductHistory();
            $stockHistory->setQuantity($product->getStock());
            $stockHistory->setProduct($product);
            $stockHistory->setCreatedAt(new DateTimeImmutable());
            $entityManager->persist($stockHistory);
            $entityManager->flush();

            // Message de succès pour l'utilisateur
            $this->addFlash('success', 'Produit créé avec succès !');
            // Redirection vers la liste des produits
            return $this->redirectToRoute('app_product_index', [], Response::HTTP_SEE_OTHER);
        }

        // Affichage du formulaire de création de produit
        return $this->render('product/new.html.twig', [
            'product' => $product,
            'form' => $form,
        ]);
    }

    /**
     * Permet de modifier un produit existant.
     * Gère également l'upload d'une nouvelle image si elle est fournie.
     */
    #[Route('/{slug}/edit', name: 'app_product_edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, ProductRepository $productRepo, EntityManagerInterface $entityManager, SluggerInterface $slugger): Response
    {
        // Recherche du produit à éditer par son slug
        $product = $productRepo->findOneBy(['slug' => $request->get('slug')]);

        // Création du formulaire de modification
        $form = $this->createForm(ProductUpdateType::class, $product);
        $form->handleRequest($request);

        // Vérification de la soumission et de la validité du formulaire
        if ($form->isSubmitted() && $form->isValid()) {
            // Gestion de l'upload d'une nouvelle image si présente
            $image = $form->get('image')->getData();
            if ($image) {
                // Génération d'un nom de fichier unique et sécurisé pour la nouvelle image
                $originalImageName = pathinfo($image->getClientOriginalName(), PATHINFO_FILENAME);
                // Génère une version "slugifiée" (sans espaces, caractères spéciaux, etc.) du nom original
                $saveImageName = $slugger->slug($originalImageName);
                $newFileImageName = $saveImageName.'_'.uniqid().'.'.$image->guessExtension();
                try {
                    // Déplacement du fichier image dans le dossier configuré
                    $image->move(
                        $this->getParameter('images_directory'),
                        $newFileImageName
                    );
                } catch (FileException $exception) {
                    // Gestion des erreurs lors de l'upload de l'image
                }
                // Mise à jour du nom de l'image dans l'entité Product
                $product->setImage($newFileImageName);
            }
            // Sauvegarde des modifications en base de données
            $entityManager->flush();

            // Message de succès pour l'utilisateur
            $this->addFlash('success', 'Produit modifié avec succès !');
            // Redirection vers la liste des produits
            return $this->redirectToRoute('app_product_index', [], Response::HTTP_SEE_OTHER);
        }

        // Affichage du formulaire de modification de produit
        return $this->render('product/edit.html.twig', [
            'product' => $product,
            'form' => $form,
        ]);
    }

    /**
     * Supprime un produit de la base de données.
     * Cette action est irréversible.
     */
    #[Route('/delete/{slug:product}', name: 'app_product_delete', methods: ['GET','POST'])]
    public function delete(Request $request, Product $product, EntityManagerInterface $entityManager): Response
    {
        // Récupération de l'identifiant du produit (peut servir pour d'autres traitements)
        $id = $product->getId();

        // Suppression du produit de la base de données
        $entityManager->remove($product);
        $entityManager->flush();

        // Message d'information pour l'utilisateur
        $this->addFlash('danger', 'Produit supprimé avec succès !');
        // Redirection vers la liste des produits
        return $this->redirectToRoute('app_product_index', [], Response::HTTP_SEE_OTHER);
    }

    /**
     * Permet d'ajouter du stock à un produit existant.
     * Enregistre chaque ajout dans l'historique des stocks.
     */
    #[Route('add/product/{slug:product}', name: 'app_add_product_stock', methods: ['GET', 'POST'])]
    public function stockAdd(Product $product, Request $request, EntityManagerInterface $entityManager, ProductRepository $productRepository): Response
    {
        // Récupération de l'identifiant du produit
        $id = $product->getId();

        // Création d'une nouvelle instance d'historique d'ajout de stock
        $stockAdd = new AddProductHistory();

        // Création du formulaire pour ajouter du stock
        $form = $this->createForm(AddProductHistoryType::class, $stockAdd);
        $form->handleRequest($request);

        // Récupération du produit depuis la base (pour éviter les problèmes de proxy)
        $product = $productRepository->find($id);

        // Vérification de la soumission et de la validité du formulaire
        if ($form->isSubmitted() && $form->isValid()) {
            // Vérification que la quantité ajoutée est positive
            if ($stockAdd->getQuantity() > 0) {
                // Mise à jour du stock du produit
                $newQuantity = $product->getStock() + $stockAdd->getQuantity();
                $product->setStock($newQuantity);

                // Enregistrement de l'opération dans l'historique
                $stockAdd->setCreatedAt(new DateTimeImmutable());
                $stockAdd->setProduct($product);
                $entityManager->persist($stockAdd);
                $entityManager->flush();

                // Message de succès pour l'utilisateur
                $this->addFlash('success', 'Stock ajouté avec succès !');
                // Redirection vers la liste des produits
                return $this->redirectToRoute('app_product_index');
            } else {
                // Affiche un message d’erreur si la quantité saisie est négative ou nulle
                $this->addFlash('danger', 'La quantité doit être supérieure à 0 !');
                // Redirection vers le formulaire d'ajout de stock
                return $this->redirectToRoute('app_add_product_stock', ['id' => $product->getId()]);
            }
        }

        // Affichage du formulaire d'ajout de stock
        return $this->render('product/addStock.html.twig', [
            'form' => $form->createView(),
            'product' => $product,
            'stockAdd' => $stockAdd,
        ]);
    }

    /**
     * Affiche l'historique des ajouts de stock pour un produit donné.
     * Permet de visualiser toutes les opérations d'ajout de stock effectuées sur ce produit.
     */
    #[Route('/add/product/{slug:product}/stock/history', name: 'app_product_stock_add_history', methods: ['GET'])]
    public function showStockHistory(Product $product, AddProductHistoryRepository $addProductHistoryRepository): Response
    {
        // Récupération de l'historique des ajouts de stock pour le produit, trié du plus récent au plus ancien
        $productAddHistory = $addProductHistoryRepository->findBy(['product' => $product], ['id' => 'DESC']);

        // Affichage de la vue avec l'historique
        return $this->render('product/stockHistory.html.twig', [
            'productsAdded' => $productAddHistory
        ]);
    }

    /**
     * Affiche la fiche détaillée d’un produit.
     * Permet de consulter toutes les informations d’un produit spécifique.
     */
    #[Route('/{slug:product}', name: 'app_product_show', methods: ['GET'])]
    public function show(Product $product, Request $request, ProductRepository $productRepo): Response
    {
        // Affichage de la vue détaillée du produit
        return $this->render('product/show.html.twig', [
            'product' => $product,
        ]);
    }
}