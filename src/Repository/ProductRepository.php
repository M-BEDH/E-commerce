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
        parent::__construct($registry, Product::class);
    }

    //    /**
    //     * @return Product[] Returns an array of Product objects
    //     */

    //    public function findByIdUp($value): array
    //    {
    //        return $this->createQueryBuilder('p')
    //            ->andWhere('p.id > :val')
    //            ->setParameter('val', $value)
    //            ->orderBy('p.id', 'ASC')
    //            ->setMaxResults(10)
    //            ->getQuery()
    //            ->getResult()
    //        ;
    //    }

    //    public function findOneBySomeField($value): ?Product
    //    {
    //        return $this->createQueryBuilder('p')
    //            ->andWhere('p.exampleField = :val')
    //            ->setParameter('val', $value)
    //            ->getQuery()
    //            ->getOneOrNullResult()
    //        ;
    //    }

          public function searchEngine(string $query){
            //crée un objet de la requete qui permet de construire la requete de recherche
            return $this->createQueryBuilder('p')
            //recherche les elements dont le nom contient la requete de la recherche
                ->where('p.name LIKE :query')
                // OU recherche les elements dont la description contient la requete de recherche
                ->orWhere('p.description LIKE :query')
                //defini la valeur de la variable "query" pour la requete
                ->setParameter('query', '%' . $query . '%')
                //execute la requete et recupere les resultats
                ->getQuery()
                ->getResult();
        }
}
