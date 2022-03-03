<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class IndexController extends AbstractController
{
    #[Route('/', name: 'index')]
    public function index(): Response
    {
              // code execution continues immediately; it doesn't wait to receive the response

        return $this->render('index/index.html.twig', [
            'controller_name' => 'IndexController'
        ]);
    }
}
