<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Controller;

use App\Entity\Books;
use App\Entity\User;
use App\Repository\BooksRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;
use App\Form\Type\DateTimePickerType;
use Symfony\Component\Form\SubmitButton;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\CurrentUser;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use App\Form\BookType;
use App\Repository\UserRepository;
use App\Form\BookCommentType;
use App\Entity\BooksComment;
use App\Repository\BooksCommentRepository;

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
#[Route('/books')]
final class BooksController extends AbstractController
{
    #[Route('/list', name: 'books_list', methods: ['GET'])]
    public function index(EntityManagerInterface $em): Response {
        $books = $em->getRepository(Books::class);

        $bookslist =  $books->findBy([], ['id' => 'DESC']);
        return $this->render('books/index.html.twig', ['bookslist' => $bookslist]);
    }

    #[Route('/{id<\d+>}', name: 'view_book', methods: ['GET'])]
    public function viewbook(Request $request, Books $book, EntityManagerInterface $em, BooksCommentRepository $BooksCommentRepository): Response {
    
       $id = $request->attributes->get('id');

       $comments = $BooksCommentRepository->findAll(['book_id' => $id]);

        $form = $this->createFormBuilder()
            ->add('comment', TextType::class)
            ->add('createdAt', HiddenType::class, ['mapped' => false, 'required' => false, 'empty_data' => ''])
            ->add('user', HiddenType::class, ['mapped' => false, 'required' => false, 'empty_data' => ''])
            ->add('book', HiddenType::class, ['mapped' => false, 'required' => false, 'empty_data' => ''])
            ->getForm();
       
        return $this->render('books/show.html.twig', ['book' => $book, 'form' => $form->createView(), 'comments' => $comments]);
    }


    #[Route('/new', name: 'book_new', methods: ['GET', 'POST'])]
    public function new(Request $request,
    Books $Books,  EntityManagerInterface $entityManager,
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
            return $this->redirectToRoute('books_list');
        }

        return $this->render('books/new.html.twig', [
            'Books' => $Books,
            'form' => $form,
        ]);
    }


    #[Route('/{id<\d+>}/edit', name: 'book_edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, Books $book, EntityManagerInterface $entityManager): Response
    {
        $form = $this->createForm(BookType::class, $book);
        $form->handleRequest($request);
       
        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->flush();
            $this->addFlash('success', 'Book updated successfully');

            return $this->redirectToRoute('view_book', ['id' => $book->getId()]);
        }

        return $this->render('books/edit.html.twig', [
            'book' => $book,
            'form' => $form,
        ]);
    }

    #[Route('/{id}/book_delete', name: 'deactive', methods: ['POST'])]
    public function book_delete(Request $request, Books $books, EntityManagerInterface $entityManager): Response
    {
        /** @var string|null $token */
        $token = $request->request->get('token');
        
        if (!$this->isCsrfTokenValid('delete', $token)) {
            $response = new Response(json_encode(array('status' => 'error')));
            $response->headers->set('Content-Type', 'application/json');
            return $response;
        }
    
        $entityManager->remove($books);
        $entityManager->flush();
        
        $response = new Response(json_encode(array('status' => 'success')));
        $response->headers->set('Content-Type', 'application/json');
        return $response;
    }


    #[Route('/{id<\d+>}/add_comment', name: 'add_comment', methods: ['POST'])]
    public function add_comment(Request $request, BooksRepository $BooksRepository, EntityManagerInterface $em): Response {
        
        $id = $request->attributes->get('id');
        $BooksComment = new BooksComment();
        $bookrep = $BooksRepository->find(["id"=>$id]);

        $form = $this->createFormBuilder()
            ->add('comment', TextType::class)
            ->add('createdAt', HiddenType::class, ['mapped' => false, 'required' => false, 'empty_data' => ''])
            ->add('user', HiddenType::class, ['mapped' => false, 'required' => false, 'empty_data' => ''])
            ->add('book', HiddenType::class, ['mapped' => false, 'required' => false, 'empty_data' => ''])
            ->getForm();
       $form->handleRequest($request);

        $user = $this->getUser();
        $userId = $user->getId();
        
        if ($form->isSubmitted() && $form->isValid()) {
           
            $data = $form->getData();
            $BooksComment->setComment($data['comment']);
            $BooksComment->setBook($bookrep);
            $BooksComment->setUser($user);
            $BooksComment->setCreatedAt(date('Y-m-d'));
            $em->persist($BooksComment);
            $em->flush();

            $this->addFlash('success', 'Book is created successfully. Plz wait for Admin Approval.');
            return $this->redirectToRoute('view_book', ['id' => $id]);
        }
       
        return $this->render('books/show.html.twig', ['book' => $book, 'form' => $form->createView()]);
    }

    #[Route('/{id}/comment_delete/{book_id}', name: 'comment_delete', methods: ['GET'])]
    public function comment_delete(Request $request, BooksComment $BooksComment, BooksCommentRepository $BooksCommentRepository, EntityManagerInterface $entityManager): Response
    {
        $comment_id = $request->attributes->get('id');
        $book_id = $request->attributes->get('book_id');
       
        $entityManager->remove($BooksComment);
        $entityManager->flush();
        
        $this->addFlash('success', 'Comment is deleted');
        return $this->redirectToRoute('view_book', ['id' => $book_id]);
    }
}
