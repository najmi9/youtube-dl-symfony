<?php

declare(strict_types=1);

namespace App\Service;

use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\ResponseHeaderBag;
use Symfony\Component\Mime\FileinfoMimeTypeGuesser;

class StreamService
{
    public function getProgressNumber(string $stream): string
    {
        $array = explode('% of', $stream);
        $str = $array[0] ?? null;

        if (null === $str) {
            return '0%';
        }

        $str = trim($str);

        $str = str_replace('[download]', '', $str);
        $str = str_replace(' ', '', $str);

        $str = (float) $str;

        return $str . '%';
    }

    public function binaryResponse(string $content, string $filename): BinaryFileResponse
    {
        $response = new BinaryFileResponse($content);

        $mimeTypeGuesser = new FileinfoMimeTypeGuesser();

        if ($mimeTypeGuesser->isGuesserSupported()) {
            $response->headers->set('Content-Type', $mimeTypeGuesser->guessMimeType($content));
        }

        $response->setContentDisposition(ResponseHeaderBag::DISPOSITION_ATTACHMENT, $filename);

        $response->deleteFileAfterSend(true);

        return $response;
    }
}
