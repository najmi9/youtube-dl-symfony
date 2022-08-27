<?php

declare(strict_types=1);

namespace App\Controller;

use App\Entity\Link;
use App\Events\VideoConvertingEvent;
use App\Mercure\SubscriptionTokenProvider;
use App\Repository\LinkRepository;
use App\Service\StreamService;
use DateTime;
use Exception;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Process\Process;
use Symfony\Component\Routing\Annotation\Route;

class AppController extends AbstractController
{
    private const MEDIA_DIR = 'medias';

    private StreamService $streamService;

    public function __construct(StreamService $streamService)
    {
        $this->streamService = $streamService;
    }

    /**
     * @Route("/", name="app_app", methods={"GET", "POST"})
     */
    public function index(SubscriptionTokenProvider $subscriptionTokenProvider): Response
    {
        $subscriptionId = md5(uniqid());

        return $this->render('app/index.html.twig', [
            'token' => $subscriptionTokenProvider->getJwt($subscriptionId),
            'subscriptionId' => $subscriptionId,
        ]);
    }

    /**
     * @Route("/download", name="app_download", methods={"POST"})
     */
    public function download(
        Request $request,
        EventDispatcherInterface $eventDispatcher,
        LinkRepository $linkRepository
    ): JsonResponse {
        $data = json_decode($request->getContent(), true);

        $url = $data['url'] ?? null;
        $downloadVideo = $data['isMp4'] ?? false;
        $topic = $data['topic'] ?? null;

        if (!$url || !$topic) {
            throw new Exception('Bad Request');
        }

        $timestamp = (new DateTime())->getTimestamp();

        $command = [
            'youtube-dl',
            '--no-mark-watched',
            '--newline',
            '-o' . self::MEDIA_DIR . '/' . $timestamp . '/%(id)s.%(ext)s',
        ];

        if (false === $downloadVideo) {
            $command[] = '-x';
            $command[] = '-f bestaudio';
        }

        $command[] = $url;

        $process = new Process($command);

        $process->setTimeout(3600);

        $process->run(function (string $type, string $buffer) use($eventDispatcher, $process, $topic) {
            $eventDispatcher->dispatch(new VideoConvertingEvent(
                [
                    'processId' => $process->getPid(),
                    'progressNumber' => $this->streamService->getProgressNumber($buffer),
                ],
                [
                    $topic,
                ]
            ));
        });

        $link = new Link();
        $link->setIpAddress($request->getClientIp())
            ->setIsMp4($downloadVideo)
            ->setUrl($url)
        ;

        $linkRepository->add($link, true);

        return $this->json([
            'url' => $this->generateUrl(
                'app_download_media',
                [
                    'timestamp' => $timestamp,
                ]
            ),
        ]);
    }

    /**
     * @Route("/download-media/{timestamp}", name="app_download_media", methods={"GET"})
     */
    public function downloadVideo(string $timestamp): Response
    {
        $files = array_diff(
            scandir(sprintf('%s/%s', self::MEDIA_DIR, $timestamp)),
            ['..', '.']
        );

        if (empty($files)) {
            $this->addFlash('danger', 'Unable to find downloaded file');

            return $this->redirectToRoute('app_download');
        }

        $fileName = end($files);

        return $this->streamService->binaryResponse(
            sprintf('%s/%s/%s', self::MEDIA_DIR, $timestamp, $fileName),
            $fileName
        );
    }

    /**
     * @Route("cancel-download/{id}", methods={"GET"}, name="cancel_download")
     */
    public function cancelDownload(string $id): Response
    {
        $process = new Process(['kill', '-9', $id]);

        $process->start();

        return $this->json([
            'message' => $process->getOutput()
        ]);
    }
}
