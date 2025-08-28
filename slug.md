- composer require cocur/slugify
- php bin/console App:generate-slugs  --->  App = nom de l'entity --> permet de générer automatiquement les slug à la creation de la /categorie/subCategorie/produit

- Vérifier la BDD -> Ajout du champ slug


cf: CategoryController - ProductContoller - SubCategoryController = twig

- new Slug à la création -> puis à ajouter à la route du new + PENSER AUX ROUTES du twig qui attendra {id} et {slug}

- Créer un dossier dans src : EventListener puis fichier : SlugListener qui va permettre d'écouter et de créer directement les slugs
