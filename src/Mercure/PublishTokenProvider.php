<?php

declare(strict_types=1);

namespace App\Mercure;

use DateTimeImmutable;
use Lcobucci\JWT\Configuration;
use Lcobucci\JWT\Signer\Hmac\Sha256;
use Lcobucci\JWT\Signer\Key\InMemory;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Symfony\Component\Mercure\Jwt\TokenProviderInterface;

final class PublishTokenProvider implements TokenProviderInterface
{
    private ParameterBagInterface $parameterBag;

    public function __construct(
        ParameterBagInterface $parameterBag
    ) {
        $this->parameterBag = $parameterBag;
    }

    public function getJwt(): string
    {
        $configuration = Configuration::forSymmetricSigner(
            new Sha256(),
            InMemory::plainText($this->parameterBag->get('publish_secret'))
        );

        return $configuration->builder()
            ->withClaim(
                'mercure',
                ['publish' => $this->getTargets()]
            )
            ->issuedAt(new DateTimeImmutable())
            ->expiresAt(new DateTimeImmutable('+ 3 days'))
            ->getToken($configuration->signer(), $configuration->signingKey())
            ->toString()
        ;
    }

    private function getTargets(): array
    {
        return [
            '/stream/downloading/{id}',
        ];
    }
}
