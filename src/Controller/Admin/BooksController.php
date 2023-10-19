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

use App\Entity\Books;
use App\Entity\User;
use App\Repository\BooksRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use App\Form\Type\DateTimePickerType;
use Symfony\Component\Form\SubmitButton;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\CurrentUser;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use App\Form\BookType;
use App\Repository\UserRepository;

/**
 * Controller used to manage blog contents in the backend.
 *
 * Please note that the application backend is developed manually for learning
 * purposes. However, in your real Symfony application you should use any of the
 * existing bundles that let you generate ready-to-use backends without effort.
 * See https://symfony.com/bundles
 *
 * @author Ryan Weaver <weaverryan@gmail.com>
 * @author Javier Eguiluz <javier.eguiluz@gmail.com>
 */
#[Route('/admin/books')]
#[IsGranted(User::ROLE_ADMIN)]
final class BooksController extends AbstractController
{
    #[Route('/list', name: 'books_index', methods: ['GET'])]
    public function index(EntityManagerInterface $em): Response {
        $books = $em->getRepository(Books::class);

        $bookslist =  $books->findBy([], ['id' => 'DESC']);
        return $this->render('admin/books/index.html.twig', ['bookslist' => $bookslist]);
    }

  
    #[Route('/new', name: 'admin_book_new', methods: ['GET', 'POST'])]
    public function new(Request $request,
        UserRepository $users,  EntityManagerInterface $entityManager,
    ): Response {
        $Books = new Books(); 

        // See https://symfony.com/doc/current/form/multiple_buttons.html
        $form = $this->createForm(BookType::class, $Books);

        $form->handleRequest($request);

        $user = $this->getUser();
        $username = $user->getFullName();

        if ($form->isSubmitted() && $form->isValid()) {
           
            $data = $form->getData();
            $Books->setAuthor($username);
            $entityManager->persist($data);
            $entityManager->flush();

            $this->addFlash('success', 'Book is created successfully');
            return $this->redirectToRoute('books_index');
        }

        return $this->render('admin/books/new.html.twig', [
            'Books' => $Books,
            'form' => $form,
        ]);
    }


    #[Route('/{id<\d+>}/edit', name: 'admin_book_edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, Books $book, EntityManagerInterface $entityManager): Response
    {
        $form = $this->createForm(BookType::class, $book);
        $form->handleRequest($request);
       
        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->flush();
            $this->addFlash('success', 'book is updated successfully');

            return $this->redirectToRoute('admin_book_edit', ['id' => $book->getId()]);
        }

        return $this->render('admin/books/edit.html.twig', [
            'book' => $book,
            'form' => $form,
        ]);
    }

    /**
     * Deletes a Book entity.
     */
    #[Route('/{id}/delete', name: 'admin_book_delete', methods: ['POST'])]
    public function delete(Request $request, Books $books, EntityManagerInterface $entityManager): Response
    {
        /** @var string|null $token */
        $token = $request->request->get('token');
        
        if (!$this->isCsrfTokenValid('delete', $token)) {
            $response = new Response(json_encode(array('status' => 'error', 'message' => 'Invalid token')));
            $response->headers->set('Content-Type', 'application/json');
            return $response;
        }
    
        $entityManager->remove($books);
        $entityManager->flush();
        
        $response = new Response(json_encode(array('status' => 'success', 'message' => 'Book is deleted')));
        $response->headers->set('Content-Type', 'application/json');
        return $response;
    }


    #[Route('/{id}/publishbook', name: 'publishbook', methods: ['GET'])]
    public function publishbook(Request $request, Books $Books, EntityManagerInterface $entityManager): Response
    {
        $Books->setIsPublished(1);
        $entityManager->persist($Books);
        $entityManager->flush();
        
        $this->addFlash('success', 'Book is published');
        return $this->redirectToRoute('books_index');
    }

    #[Route('/{id}/unpublishbook', name: 'unpublishbook', methods: ['GET'])]
    public function unpublishbook(Request $request, Books $Books, EntityManagerInterface $entityManager): Response
    {
        $Books->setIsPublished(0);
        $entityManager->persist($Books);
        $entityManager->flush();
        
        $this->addFlash('success', 'Book is Unpublished');
        return $this->redirectToRoute('books_index');
    }
}
