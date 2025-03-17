<?php

namespace App\Controller;

use App\Entity\Artist;
use App\Entity\Card;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Request;
use OpenApi\Attributes as OA;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/api/card', name: 'api_card_')]
#[OA\Tag(name: 'Card', description: 'Routes for all about cards')]
class ApiCardController extends AbstractController
{
    public function __construct(
        private readonly EntityManagerInterface $entityManager,
        private readonly LoggerInterface $logger
    ) {
    }
    #[Route('/all', name: 'List all cards', methods: ['GET'])]
    #[OA\Put(description: 'Return all cards in the database')]
    #[OA\Response(response: 200, description: 'List all cards')]
    public function cardAll(Request $request): Response
    {
        $page = $request->query->getInt('page', 1);
        $start = microtime(true);

        $this->logger->info('API call: Getting cards page {page}', [
            'page' => $page,
            'method' => 'GET',
            'route' => '/api/card/all'
        ]);

        $offset = ($page - 1) * 100;

        $query = $this->entityManager->createQueryBuilder()
            ->select('c')
            ->from(Card::class, 'c')
            ->setMaxResults(100)
            ->setFirstResult($offset)
            ->getQuery();

        $cards = $query->getResult();
        $cardCount = count($cards);

        $end = microtime(true);
        $this->logger->info('API response: Returned {count} cards in {time}s', [
            'count' => $cardCount,
            'time' => round($end - $start, 3),
            'page' => $page,
            'status' => 200
        ]);
        return $this->json($cards);
    }

    #[Route('/setcodes', name: 'List all setcodes', methods: ['GET'])]
    #[OA\Get(description: 'Return setcodes')]
    #[OA\Response(response: 200, description: 'List all setcodes')]
    public function setCodeAll(): Response
    {
        $start = microtime(true);
        $this->logger->info('API call: Getting all setCode', [
            'method' => 'GET',
            'route' => '/api/card/setcodes'
        ]);

        $setCodes = $this->entityManager->createQueryBuilder()
            ->select('DISTINCT c.setCode')
            ->from(Card::class, 'c')
            ->getQuery()
            ->getResult();

        $formattedSetCodes = array_map(function ($item) {
            return $item['setCode'];
        }, $setCodes);

        $end = microtime(true);
        $this->logger->info('API response: List all setCode in {time}s', [
            'time' => round($end - $start, 3),
            'count' => count($formattedSetCodes),
            'status' => 200
        ]);

        return $this->json($formattedSetCodes);
    }

    #[Route('/{uuid}', name: 'Show card', methods: ['GET'])]
    #[OA\Parameter(name: 'uuid', description: 'UUID of the card', in: 'path', required: true, schema: new OA\Schema(type: 'integer'))]
    #[OA\Get(description: 'Get a card by UUID')]
    #[OA\Response(response: 200, description: 'Show card')]
    #[OA\Response(response: 404, description: 'Card not found')]
    public function cardShow(string $uuid): Response
    {
        $start = microtime(true);
        $this->logger->info('API call: Getting card details', [
            'uuid' => $uuid,
            'method' => 'GET',
            'route' => '/api/card/{uuid}'
        ]);
        $card = $this->entityManager->getRepository(Card::class)->findOneBy(['uuid' => $uuid]);
        if (!$card) {
            $this->logger->error('API response: Card not found',[
                'uuid' => $uuid
            ]);
            return $this->json(['error' => 'Card not found'], 404);
        }
        $end = microtime(true);
        $this->logger->info('API response: Showing card {uuid} in {time}s',[
            'uuid' => $uuid,
            'time' => round($end - $start, 3),
            'status' => 200
        ]);
        return $this->json($card);
    }

    #[Route('/name/{name}', name: 'List cards by name', methods: ['GET'])]
    #[OA\Get(description: 'Return cards by name')]
    #[OA\Response(response: 200, description: 'List cards by name')]
    public function cardsByName(string $name): Response
    {
        $start = microtime(true);
        $this->logger->info('API call: Getting all cards by name {name}', [
            'name' => $name,
            'method' => 'GET',
            'route' => '/api/card/name/{name}'
        ]);
        $cards = $this->entityManager->createQueryBuilder()
            ->select('c')
            ->from(Card::class, 'c')
            ->where('c.name LIKE :name')
            ->setParameter('name', '%' . $name . '%')
            ->getQuery()
            ->getResult();
        $end = microtime(true);
        $this->logger->info('API response: List of cards by name {name} in {time}s', [
            'name' => $name,
            'time' => round($end - $start, 3),
            'status' => 200
        ]);
        return $this->json($cards);
    }
}
