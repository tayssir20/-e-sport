<?php

namespace App\Tests\Service;

use App\Entity\Stream;
use App\Service\StreamManager;
use PHPUnit\Framework\TestCase;

class StreamManagerTest extends TestCase
{
    public function testValidStream(): void
    {
        $stream = new Stream();
        $stream->setIsActive(false);
        $stream->setUrl('video.mp4');
        $stream->setCreatedAt(new \DateTimeImmutable());

        $manager = new StreamManager();

        $this->assertTrue($manager->validate($stream));
    }

    public function testActiveStreamWithoutVideo(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Un stream actif doit avoir une vidéo.');

        $stream = new Stream();
        $stream->setIsActive(true);
        $stream->setUrl(null);
        $stream->setCreatedAt(new \DateTimeImmutable());

        $manager = new StreamManager();
        $manager->validate($stream);
    }

    public function testStreamWithFutureDate(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('La date de création ne peut pas être dans le futur.');

        $stream = new Stream();
        $stream->setIsActive(false);
        $stream->setUrl('video.mp4');
        $stream->setCreatedAt(
            (new \DateTimeImmutable())->modify('+1 day')
        );

        $manager = new StreamManager();
        $manager->validate($stream);
    }
}