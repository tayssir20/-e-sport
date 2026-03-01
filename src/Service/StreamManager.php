<?php

namespace App\Service;

use App\Entity\Stream;

class StreamManager
{
    public function validate(Stream $stream): bool
    {
        // Règle 1 : un stream actif doit avoir une vidéo
        if ($stream->isActive() && empty($stream->getUrl())) {
            throw new \InvalidArgumentException('Un stream actif doit avoir une vidéo.');
        }

        // Règle 2 : la date ne peut pas être dans le futur
        if ($stream->getCreatedAt() > new \DateTimeImmutable()) {
            throw new \InvalidArgumentException('La date de création ne peut pas être dans le futur.');
        }

        return true;
    }
}