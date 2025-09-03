<?php

namespace App\Repository;

use App\Entity\Product;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Product>
 */
class ProductRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        // Appelle le constructeur parent avec la classe Product
        parent::__construct($registry, Product::class);
    }

    // Exemple de méthode personnalisée générée par Symfony (commentée)
    // /**
    //  * @return Product[] Returns an array of Product objects
    //  */
    // public function findByIdUp($value): array
    // {
    //     return $this->createQueryBuilder('p')
    //         ->andWhere('p.id > :val')
    //         ->setParameter('val', $value)
    //         ->orderBy('p.id', 'ASC')
    //         ->setMaxResults(10)
    //         ->getQuery()
    //         ->getResult()
    //     ;
    // }

    // Exemple de méthode personnalisée générée par Symfony (commentée)
    // public function findOneBySomeField($value): ?Product
    // {
    //     return $this->createQueryBuilder('p')
    //         ->andWhere('p.exampleField = :val')
    //         ->setParameter('val', $value)
    //         ->getQuery()
    //         ->getOneOrNullResult()
    //     ;
    // }

    /**
     * Recherche les produits dont le nom ou la description contient la chaîne passée en paramètre.
     * @param string $query Le mot-clé recherché par l'utilisateur
     * @return Product[] Retourne un tableau d'objets Product correspondant à la recherche
     */
    public function searchEngine(string $query){
        // Crée un QueryBuilder pour construire dynamiquement la requête de recherche
        return $this->createQueryBuilder('p')
            // Recherche les produits dont le nom contient la requête
            ->where('p.name LIKE :query')
            // Ou dont la description contient la requête
            ->orWhere('p.description LIKE :query')
            // Définit la valeur du paramètre "query" avec des % pour la recherche partielle
            ->setParameter('query', '%' . $query . '%')
            // Exécute la requête et retourne les résultats
            ->getQuery()
            ->getResult();
    }
}