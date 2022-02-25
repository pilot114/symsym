<?php

namespace App\Controller;

use App\Entity\Conference;
use App\Repository\CommentRepository;
use App\Repository\ConferenceRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Routing\RouterInterface;
use Symfony\Component\Serializer\Encoder\JsonEncoder;
use Symfony\Component\Serializer\Normalizer\AbstractNormalizer;
use Symfony\Component\Serializer\Normalizer\ObjectNormalizer;
use Symfony\Component\Serializer\Serializer;

class HomeController extends AbstractController
{
    #[Route('/', name: 'homepage')]
    public function index(ConferenceRepository $conferenceRepository, RouterInterface $router): Response
    {
        $paths = [];
        foreach ($router->getRouteCollection()->all() as $route) {
            $paths[] = sprintf('<a href="%s">%s</a>', $route->getPath(), $route->getPath());
        }

        return new Response(implode("<br>", $paths));
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

    #[Route('/api/v1/conference', name: 'conferences')]
    public function conference(ConferenceRepository $conferenceRepository): Response
    {
        $data = $conferenceRepository->findAll();
        return $this->successResponse($data);
    }

    #[Route('/api/v1/conference/{id}', name: 'conference')]
    public function showConference(Request $request, int $id, ConferenceRepository $conferenceRepository, CommentRepository $commentRepository): Response
    {
        // TODO: можно как-то сразу внедрять сущность по id из роута
        $conference = $conferenceRepository->find($id);

        $page = max(0, $request->query->getInt('page', 0));

        $paginator = $commentRepository->findByConference($conference, $page);
        $data = $paginator->getIterator()->getArrayCopy();

        return $this->successResponse($data, [
            'total' => count($paginator)
        ]);
    }

    protected function successResponse(array $data, array $meta = []): Response
    {
        $prepare = $this->getSerializer()->serialize($data, 'json');

        $payload = [
            'result' => json_decode($prepare),
            'errors' => []
        ];
        if ($meta) {
            $payload += $meta;
        }
        return $this->json($payload);
    }

    /**
     * Обработка для циклических ссылок и дат
     */
    protected function getSerializer()
    {
        $encoder = new JsonEncoder();
        $defaultContext = [
            AbstractNormalizer::CIRCULAR_REFERENCE_HANDLER => fn ($object) => null,
            AbstractNormalizer::CALLBACKS => [
                'createdAt' => function ($innerObject): string {
                    if ($innerObject instanceof \DateTimeInterface) {
                        return $innerObject->format(\DateTimeInterface::ISO8601);
                    }
                    return '';
                },
            ],
        ];
        $normalizer = new ObjectNormalizer(null, null, null, null, null, null, $defaultContext);
        return new Serializer([$normalizer], [$encoder]);
    }
}
