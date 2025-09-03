<?php

namespace App\Controller;

use App\Entity\User;
use App\Form\RegistrationFormType;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Attribute\Route;

class RegistrationController extends AbstractController
{
    #[Route('/register', name: 'app_register')]
    public function register(Request $request, UserPasswordHasherInterface $userPasswordHasher, Security $security, EntityManagerInterface $entityManager): Response
    {
        // Création d'une nouvelle instance d'utilisateur
        $user = new User();

        // Création du formulaire d'inscription lié à l'entité User
        $form = $this->createForm(RegistrationFormType::class, $user);
        $form->handleRequest($request);

        // Vérifie si le formulaire a été soumis et est valide
        if ($form->isSubmitted() && $form->isValid()) {
            /** @var string $plainPassword */
            // Récupère le mot de passe en clair saisi par l'utilisateur dans le formulaire
            $plainPassword = $form->get('plainPassword')->getData();

            // Encode le mot de passe en clair et le définit sur l'utilisateur
            $user->setPassword($userPasswordHasher->hashPassword($user, $plainPassword));

            // Prépare l'utilisateur pour l'enregistrement en base de données
            $entityManager->persist($user);
            $entityManager->flush();

            // Ici, vous pouvez ajouter d'autres actions, comme l'envoi d'un email de bienvenue

            // Redirige l'utilisateur vers la page de connexion après inscription
            // (vous pouvez aussi connecter automatiquement l'utilisateur ici si besoin)
            // return $security->login($user, 'form_login', 'main');
            return $this->redirectToRoute('app_login');
        }

        // Affiche le formulaire d'inscription à l'utilisateur
        return $this->render('registration/register.html.twig', [
            'registrationForm' => $form,
        ]);
    }
}