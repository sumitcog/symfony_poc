<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Controller\Admin;

use App\Entity\Post;
use App\Entity\User;
use App\Form\RegisterType;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\SubmitButton;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\CurrentUser;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use App\Service\MessageGenerator;
use Psr\Log\LoggerInterface;
use Symfony\Component\HttpFoundation\JsonResponse;

#[Route('/admin/user')]
#[IsGranted(User::ROLE_ADMIN)]
final class UserController extends AbstractController
{
    #[Route('/', name: 'admin_index', methods: ['GET'])]
    #[Route('/', name: 'admin_user_index', methods: ['GET'])]
    public function index(
        #[CurrentUser] User $user,
        UserRepository $users,
    ): Response {
        $users = $users->findAll();
        return $this->render('admin/user/index.html.twig', ['users' => $users]);
    }

   
    #[Route('/new', name: 'admin_user_new', methods: ['GET', 'POST'])]
    public function new(
        #[CurrentUser] User $user,
        Request $request,
        EntityManagerInterface $entityManager,
        UserPasswordHasherInterface $userPasswordHasher
    ): Response {
        $user = new User();
       // $user->setAuthor($user);

        // See https://symfony.com/doc/current/form/multiple_buttons.html
        $form = $this->createForm(RegisterType::class, $user);

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            if (isset($request->get('register')['is_author']) && $request->get('register')['is_author'] === '1') $roles = 'ROLE_AUTHOR'; else $roles = 'ROLE_USER';
            $user->roles = array($roles);
            $user->setPassword(
                $userPasswordHasher->hashPassword(
                       $user,
                       $form->get('password')->getData()
               )
             );

            $entityManager->persist($user);
            $entityManager->flush();

            $this->addFlash('success', 'User is created successfully');

            /** @var SubmitButton $submit */
            $submit = $form->get('saveAndCreateNew');

            if ($submit->isClicked()) {
                return $this->redirectToRoute('admin_user_new');
            }

            return $this->redirectToRoute('admin_user_index');
        }

        return $this->render('admin/user/new.html.twig', [
            'user' => $user,
            'form' => $form,
        ]);
    }

  
    #[Route('/{id<\d+>}/edit', name: 'admin_user_edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, User $user, EntityManagerInterface $entityManager,
    
        UserPasswordHasherInterface $userPasswordHasher
        
        ): Response
    {
        $form = $this->createForm(RegisterType::class, $user);
        $originalPassword = $user->getPassword();
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
          $plainPassword = $form->get('password')->getData();
            if (!empty($plainPassword))  {  
                $user->setPassword(
                    $userPasswordHasher->hashPassword(
                           $user,
                           $form->get('password')->getData()
                   )
                 );           
            }
            else {
                $user->setPassword($originalPassword);
            }

            $entityManager->flush();
            $this->addFlash('success', 'User updated successfully');

            return $this->redirectToRoute('admin_user_edit', ['id' => $user->getId()]);
        }

        return $this->render('admin/user/edit.html.twig', [
            'user' => $user,
            'form' => $form,
        ]);
    }

    /**
     * Deletes a Post entity.
     */
    #[Route('/{id}/delete', name: 'admin_user_delete', methods: ['POST'])]
    public function delete(Request $request, User $user, EntityManagerInterface $entityManager): Response
    {
        /** @var string|null $token */
        $token = $request->request->get('token');
        
        if (!$this->isCsrfTokenValid('delete', $token)) {
            $response = new Response(json_encode(array('status' => 'error')));
            $response->headers->set('Content-Type', 'application/json');
            return $response;
        }
    
        $entityManager->remove($user);
        $entityManager->flush();
        
        $response = new Response(json_encode(array('status' => 'success')));
        $response->headers->set('Content-Type', 'application/json');
        return $response;
    }
}
