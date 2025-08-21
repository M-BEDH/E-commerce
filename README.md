
# Projet E-Commerce

Projet d'application e-commerce développée avec Symfony (backend) et intégrant :  
- Stripe pour la gestion des paiements en ligne. 
- Mailtrap pour la gestion et les tests d’envoi d’e-mails.
- Paginator 
- PHP my admin

Requiert un compte mailtrap : https://mailtrap.io/fr/ en un compte stripe https://stripe.com/fr

Installation :

1 - Cloner le Projet 

2 - Installer les dépendances PHP - ligne de commande terminal IDE

    * composer Install

3 - Configurer le fichier .env en ajoutant la config mailtrap - la cle secrete stripe et son endpoint ansi que l'url de la database (pour obtenir l'endpoint il faut lancer l'ecoute stripe cf : 8 et recupérer le whsec_xxx)


    * MAILER_DSN=smtp: "xxxx"
    * STRIPE_SECRET_KEY="sk-test_xxxx"
    * ENDPOINT_KEY="whsec_xxxxx"
    * DATABASE_URL="mysql://app:!ChangeMe!@127.0.0.1:3306/E-commerce?serverVersion=8.0.32&charset=utf8mb4"

    -- ATTENTION -- si versionning git -> Créer un fichier .env.local et y copier le .env en retirant du fichier .env ces infos critiques.

4 - Créer la base de données :

    * php bin/console doctrine:database:create

5 - Lancer les migrations :

    * symfony.exe console make:migration
    * symfony.exe console doctrine:migrations:migrate 

6 - Lancer le server : symfony server:start ou php -S 127.0.0.1:8000 -t public

7 - Lancer l'envois des mails : php bin/console messenger:consume async -vv

8 - Lancer l'ecoute evenement stripe : stripe listen --forward-to http://127.0.0.1:8000/stripe/notify

9 - Ouvrir le projet http://localhost:8000


