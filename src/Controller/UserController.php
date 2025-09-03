<?php

namespace App\Controller;

use App\Entity\User;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;


// Contrôleur pour la gestion des utilisateurs
final class UserController extends AbstractController
{
    // Affiche la liste de tous les utilisateurs
    #[Route('/admin/user', name: 'app_user')]
    public function user(UserRepository $userRepo): Response
    {
        // Récupère tous les utilisateurs depuis la base de données et les transmet à la vue
        return $this->render('user/user.html.twig', [
            'users' => $userRepo->findAll()
        ]);
    }

     
    // Méthode pour éditer un utilisateur (commentée)
    // #[Route('/user/{id}/update', name: 'app_user_update')]
    // public function edituser(Request $request, User $user, EntityManagerInterface $entityManager): Response
    // {
    //     // Création du formulaire d'édition de l'utilisateur
    //     $form = $this->createForm(RegistrationFromType::class, $user);

    //     $form->handleRequest($request);     

    //     // Vérifie si le formulaire a été soumis et est valide
    //     if ($form->isSubmitted() && $form->isValid()) {
    //         // Sauvegarde les modifications en base de données
    //         $entityManager->flush();

    //         // Message de succès pour l'utilisateur
    //         $this->addFlash('success', 'Modification réussie!');

    //         // Redirige vers la liste des utilisateurs
    //         return $this->redirectToRoute('app_user');
    //     }

    //     // Affiche le formulaire d'édition
    //     return $this->render('user/updateuser.html.twig', [
    //         'form' => $form->createView(),
    //     ]);
    // }



    #region editRole

    // Attribue le rôle "ROLE_EDITOR" à un utilisateur
    #[Route('admin/user/{id}/editRole', name: 'app_user_edit_role')]
    public function updateRole(User $user, EntityManagerInterface $entityManager): Response
    {
        // Définit le rôle de l'utilisateur
        $user->setRoles(['ROLE_EDITOR']);
        $entityManager->flush();

        // Message de succès pour l'utilisateur
        $this->addFlash('success', 'Rôle de l’utilisateur ajouté.');

        // Redirige vers la liste des utilisateurs
        return $this->redirectToRoute('app_user');
    }


    // Supprime tous les rôles de l'utilisateur
    #[Route('admin/user/{id}/deleteRole', name: 'app_user_delete_role')]
    public function removeRole(User $user, EntityManagerInterface $entityManager): Response
    {
        // Supprime les rôles de l'utilisateur
        $user->setRoles(['']);
        $entityManager->flush();

        // Message de succès pour l'utilisateur
        $this->addFlash('success', 'Rôle de l’utilisateur supprimé.');

        // Redirige vers la liste des utilisateurs
        return $this->redirectToRoute('app_user');
    }
    #endregion



    // Supprime un utilisateur de la base de données
    #[Route('/user/delete/{id}', name: 'app_user_delete_user')]
    public function deleteuser(User $user, EntityManagerInterface $entityManager): Response
    {
        // Supprime l'utilisateur
        $entityManager->remove($user);
        $entityManager->flush(); 
     
        // Message d'information pour l'utilisateur
        $this->addFlash('danger','Utilisateur supprimé !');
            
        // Redirige vers la liste des utilisateurs
        return $this->redirectToRoute('app_user');
    }


}