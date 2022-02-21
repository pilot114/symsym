<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class HomeController extends AbstractController
{
    #[Route('/', name: 'homepage')]
    public function index(): Response
    {
        return new Response('Home page');
    }

    #[Route('/welcome/{name}', name: 'welcome')]
    public function welcome(Request $request, string $name = null): Response
    {
        if ($request->query->get('debug')) {
            dump($request);
        }

        $welcome = $name ? htmlentities($name) : '[username]';

        return $this->json([
            'message' => sprintf('Hello %s!', $welcome),
        ]);
    }
}
