<?php

namespace App\Controller;

use App\Entity\Product;
use App\Entity\SubCategory;
use App\Repository\ProductRepository;
use App\Repository\CategoriesRepository;
use App\Repository\SubCategoryRepository;
use Knp\Component\Pager\PaginatorInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;

final class HomeController extends AbstractController
{
    #[Route('/', name: 'app_home', methods: ['GET'])]
    public function index(ProductRepository $productRepository, CategoriesRepository $categoriesRepository, Request $request, PaginatorInterface $paginator): Response
    {
        $data = $productRepository->findby([],['id'=>"DESC"]);
        $products = $paginator->paginate(
            $data,
            $request->query->getInt('page', 1), //met en place la pagination
            4 // je choisi d'afficher 4 produits par page
            
        );

        return $this->render('home/home.html.twig', [
             'products' => $products,
             'categories' => $categoriesRepository->findAll()
        ]);
    }


    
    #[Route('/product/{id}/show', name: 'app_home_product_show', methods: ['GET'])]
    public function showProduct(Product $product, ProductRepository $productRepository): Response
    {
        $lastProductAdd = $productRepository->findBy([], ['id' => 'DESC'],5);

        return $this->render('home/showProduct.html.twig', [
             'product' => $product,
             'products' => $lastProductAdd,
        ]);
    }


    #[Route('/product/subCategory/{id}/filter', name: 'app_home_product_filter', methods: ['GET'])]
    public function filter($id, SubCategoryRepository $subCategoryRepository, CategoriesRepository $categoriesRepository): Response
    {

        $product = $subCategoryRepository->find($id)->getProducts();

        $subCategory = $subCategoryRepository->find($id);

        return $this->render('home/filter.html.twig', [
             'products' => $product,
             'subCategory' => $subCategory,
             'categories' => $categoriesRepository->findAll()
        ]);
    }



}
