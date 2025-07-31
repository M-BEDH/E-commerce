<?php

namespace App\Controller;

use App\Entity\User;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;


final class UserController extends AbstractController
{
    #[Route('/admin/user', name: 'app_user')]
    public function user(UserRepository $userRepo): Response
    {

        return $this->render('user/user.html.twig', [
            'users' => $userRepo->findAll()
        ]);
    }

     
    // #[Route('/user/{id}/update', name: 'app_user_update')]
    // public function edituser(Request $request, User $user, EntityManagerInterface $entityManager): Response
    // {
    //     $form = $this->createForm(RegistrationFromType::class, $user);

    //     $form->handleRequest($request);     

    //     if ($form->isSubmitted() && $form->isValid()) {
    //         $entityManager->flush();

    //         $this->addFlash('success', 'Modification réussie!');

    //         return $this->redirectToRoute('app_user');
    //     }

    //     return $this->render('user/updateuser.html.twig', [
    //         'form' => $form->createView(),
    //     ]);
    // }



       #[Route('admin/user/{id}/editRole', name: 'app_user_edit_role')]
    public function updateRole(User $user, EntityManagerInterface $entityManager): Response
    {
        $user->setRoles(['ROLE_EDITOR']);
        $entityManager->flush();

        $this->addFlash('success', 'Rôle de l’utilisateur ajouté.');

        return $this->redirectToRoute('app_user');
    }


      #[Route('admin/user/{id}/deleteRole', name: 'app_user_delete_role')]
    public function removeRole(User $user, EntityManagerInterface $entityManager): Response
    {
        $user->setRoles(['']);
        $entityManager->flush();

        $this->addFlash('success', 'Rôle de l’utilisateur supprimé.');

        return $this->redirectToRoute('app_user');
    }



    #[Route('/user/delete/{id}', name: 'app_user_delete_user')]
    public function deleteuser(User $user, EntityManagerInterface $entityManager): Response
    {
    
        $entityManager->remove($user);
        $entityManager->flush(); 
     
        $this->addFlash('danger','Utilisateur supprimé !');
            
        return $this->redirectToRoute('app_user');
    }


}
