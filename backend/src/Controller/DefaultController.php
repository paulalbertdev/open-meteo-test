<?php

declare(strict_types=1);

namespace App\Controller;

use App\Entity\FavoriteSearch;
use App\Repository\FavoriteSearchRepository;
use App\Service\OpenMeteoClient;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class DefaultController extends AbstractController
{
    #[Route('/')]
    public function index(): Response
    {
        return new Response('Not Found', Response::HTTP_NOT_FOUND);
    }
}
