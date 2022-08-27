<?php

declare(strict_types=1);

namespace App\Mercure;

use DateTimeImmutable;
use Lcobucci\JWT\Configuration;
use Lcobucci\JWT\Signer\Hmac\Sha256;
use Lcobucci\JWT\Signer\Key\InMemory;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;

final class SubscriptionTokenProvider
{
    private ParameterBagInterface $parameterBag;

    public function __construct(
        ParameterBagInterface $parameterBag
    ) {
        $this->parameterBag = $parameterBag;
    }

    public function getJwt(string $id): string
    {
        $configuration = Configuration::forSymmetricSigner(
            new Sha256(),
            InMemory::plainText($this->parameterBag->get('subscribe_secret'))
        );

        return $configuration->builder()
            ->issuedAt(new DateTimeImmutable())
            ->expiresAt(new DateTimeImmutable('+ 1 day'))
            ->withClaim(
                'mercure',
                ['subscribe' => $this->getTargets($id)]
            )
            ->getToken($configuration->signer(), $configuration->signingKey())
            ->toString()
        ;
    }

    private function getTargets(string $id): array
    {
        return [
            "/stream/downloading/{$id}",
        ];
    }
}
