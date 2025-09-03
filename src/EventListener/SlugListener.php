<?php

namespace App\EventListener;

use Doctrine\ORM\Event\PrePersistEventArgs;
use Doctrine\ORM\Event\PreUpdateEventArgs;
use Symfony\Component\String\Slugger\SluggerInterface;

class SlugListener
{
    // Propriété pour stocker le service de "slugification"
    private SluggerInterface $slugger;

    // Injection du service SluggerInterface via le constructeur
    public function __construct(SluggerInterface $slugger)
    {
        $this->slugger = $slugger;
    }

    // Méthode appelée automatiquement avant la persistance d'une entité (insertion en base)
    public function prePersist(PrePersistEventArgs $args): void
    {
        // Récupère l'entité concernée par l'événement
        $entity = $args->getObject();
        // Génère et applique le slug si nécessaire
        $this->handleSlug($entity);
    }

    // Méthode appelée automatiquement avant la mise à jour d'une entité (update en base)
    public function preUpdate(PreUpdateEventArgs $args): void
    {
        // Récupère l'entité concernée par l'événement
        $entity = $args->getObject();
        // Génère et applique le slug si nécessaire
        $this->handleSlug($entity);

        // Si l'entité est un Product, Categories ou SubCategory et que le champ slug a changé,
        // on force la nouvelle valeur du slug dans Doctrine pour la prise en compte lors du flush
        if (
            ($entity instanceof \App\Entity\Product ||
             $entity instanceof \App\Entity\Categories ||
             $entity instanceof \App\Entity\SubCategory)
            && method_exists($entity, 'getSlug')
            && $args->hasChangedField('slug')
        ) {
            $args->setNewValue('slug', $entity->getSlug());
        }
    }

    // Génère et applique le slug à l'entité si elle est concernée
    private function handleSlug(object $entity): void
    {
        // Vérifie que l'entité est de type Product, Categories ou SubCategory
        // et qu'elle possède les méthodes getName et setSlug
        if (
            ($entity instanceof \App\Entity\Product ||
             $entity instanceof \App\Entity\Categories ||
             $entity instanceof \App\Entity\SubCategory)
            && method_exists($entity, 'getName')
            && method_exists($entity, 'setSlug')
        ) {
            // Génère un slug à partir du nom de l'entité (en minuscules)
            $slug = strtolower($this->slugger->slug($entity->getName()));
            // Applique le slug à l'entité
            $entity->setSlug($slug);
        }
    }
}