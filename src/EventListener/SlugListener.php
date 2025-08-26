<?php

namespace App\EventListener;

use Doctrine\ORM\Event\PrePersistEventArgs;
use Doctrine\ORM\Event\PreUpdateEventArgs;
use Symfony\Component\String\Slugger\SluggerInterface;

class SlugListener
{
    private SluggerInterface $slugger;

    public function __construct(SluggerInterface $slugger)
    {
        $this->slugger = $slugger;
    }

    public function prePersist(PrePersistEventArgs $args): void
    {
        $entity = $args->getObject();
        $this->handleSlug($entity);
    }

    public function preUpdate(PreUpdateEventArgs $args): void
    {
        $entity = $args->getObject();
        $this->handleSlug($entity);

        // Ne fait setNewValue que si le champ slug existe dans les changements Doctrine
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

    private function handleSlug(object $entity): void
    {
        if (
            ($entity instanceof \App\Entity\Product ||
             $entity instanceof \App\Entity\Categories ||
             $entity instanceof \App\Entity\SubCategory)
            && method_exists($entity, 'getName')
            && method_exists($entity, 'setSlug')
        ) {
            $slug = strtolower($this->slugger->slug($entity->getName()));
            $entity->setSlug($slug);
        }
    }
}