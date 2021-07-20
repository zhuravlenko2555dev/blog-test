<?php

namespace App\Controller;

use App\Entity\Post;
use App\Form\PostType;
use App\Repository\CategoryRepository;
use App\Repository\PostRepository;
use App\Repository\UserRepository;
use App\Security\Voter\PostVoter;
use App\Utils\CsvUtils;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Security\Core\Security;

/**
 * @Route("/post")
 */
class PostController extends AbstractController
{
    private UrlGeneratorInterface $urlGenerator;

    public function __construct(UrlGeneratorInterface $urlGenerator)
    {
        $this->urlGenerator = $urlGenerator;
    }

    /**
     * @Route("/", name="post_index", methods={"GET"})
     */
    public function index(Request $request, PostRepository $postRepository): Response
    {
        $q = $request->query->get('q');

        if ($q) {
            $posts = $postRepository->findBySearch($q);
        } else {
            $posts = $postRepository->findAll();
        }

        return $this->render('post/index.html.twig', [
            'posts' => $posts,
            'q' => $q,
        ]);
    }

    /**
     * @Route("/new", name="post_new", methods={"GET","POST"})
     */
    public function new(Request $request, Security $security): Response
    {
        $post = new Post();
        $form = $this->createForm(PostType::class, $post);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $post->setUser($security->getUser());
            $entityManager = $this->getDoctrine()->getManager();
            $entityManager->persist($post);
            $entityManager->flush();

            return $this->redirectToRoute('post_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->renderForm('post/new.html.twig', [
            'post' => $post,
            'form' => $form,
        ]);
    }

    /**
     * @Route("/{id}", name="post_show", methods={"GET"}, requirements={"id"="\d+"})
     */
    public function show(Post $post): Response
    {
        return $this->render('post/show.html.twig', [
            'post' => $post,
        ]);
    }

    /**
     * @Route("/{id}/edit", name="post_edit", methods={"GET","POST"}, requirements={"id"="\d+"})
     */
    public function edit(Request $request, Post $post): Response
    {
        if (!$this->isGranted(PostVoter::EDIT, $post)) {
            return new RedirectResponse($this->urlGenerator->generate('post_index'));
        }

        $form = $this->createForm(PostType::class, $post);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $this->getDoctrine()->getManager()->flush();

            return $this->redirectToRoute('post_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->renderForm('post/edit.html.twig', [
            'post' => $post,
            'form' => $form,
        ]);
    }

    /**
     * @Route("/{id}", name="post_delete", methods={"POST"}, requirements={"id"="\d+"})
     */
    public function delete(Request $request, Post $post): Response
    {
        if (!$this->isGranted(PostVoter::DELETE, $post)) {
            return new RedirectResponse($this->urlGenerator->generate('post_index'));
        }

        if ($this->isCsrfTokenValid('delete'.$post->getId(), $request->request->get('_token'))) {
            $entityManager = $this->getDoctrine()->getManager();
            $entityManager->remove($post);
            $entityManager->flush();
        }

        return $this->redirectToRoute('post_index', [], Response::HTTP_SEE_OTHER);
    }

    /**
     * @Route("/export", name="post_export", methods={"GET"})
     */
    public function export(PostRepository $postRepository): Response
    {
        $csvUtils = new CsvUtils();
        $postsCsvData = $csvUtils->exportPosts($postRepository);

        $response = new Response();
        $response->headers->set('Cache-Control', 'private');
        $response->headers->set('Content-Type', 'text/csv; charset=utf-8');
        $response->headers->set('Content-Disposition', 'attachment; filename="posts.csv";');
        $response->headers->set('Content-length',  strlen($postsCsvData));

        $response->sendHeaders();
        $response->setContent($postsCsvData);

        return $response;
    }

    /**
     * @Route("/import", name="post_import", methods={"POST"})
     */
    public function import(Request $request, CategoryRepository $categoryRepository, UserRepository $userRepository): Response
    {
        $csvUtils = new CsvUtils();

        $postsFile = $request->files->get('posts');
        $csvUtils->importPosts($postsFile->getPathname(), $this->getDoctrine()->getManager(), $categoryRepository, $userRepository);

        return new RedirectResponse($this->urlGenerator->generate('post_index'));
    }
}
