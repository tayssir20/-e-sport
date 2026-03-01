<?php
namespace App\Tests\Service;

use App\Entity\Blog;
use App\Entity\Rating;
use App\Entity\User;
use App\Service\BlogManager;
use PHPUnit\Framework\TestCase;

class BlogManagerTest extends TestCase
{
    public function testAddRating()
    {
        $blog = new Blog();
        $user = new User();

        // ⚡ On force l'ID pour les tests
        $ref = new \ReflectionProperty(User::class, 'id');
        $ref->setAccessible(true);
        $ref->setValue($user, 1);

        $rating = new Rating();
        $rating->setUser($user);
        $rating->setValue(5);

        $manager = new BlogManager();
        $this->assertTrue($manager->addRating($blog, $rating));
        $this->assertEquals(5.0, $blog->getAverageRating());
        $this->assertEquals(1, $blog->getRatingCount());
    }

    public function testAddDuplicateRatingThrowsException()
    {
        $this->expectException(\InvalidArgumentException::class);

        $blog = new Blog();
        $user = new User();

        // ⚡ On force l'ID pour les tests
        $ref = new \ReflectionProperty(User::class, 'id');
        $ref->setAccessible(true);
        $ref->setValue($user, 1);

        $rating1 = new Rating();
        $rating1->setUser($user);
        $rating1->setValue(4);

        $rating2 = new Rating();
        $rating2->setUser($user);
        $rating2->setValue(5);

        $manager = new BlogManager();
        $manager->addRating($blog, $rating1);
        $manager->addRating($blog, $rating2); // doit lancer l'exception
    }

    public function testCommentCountUpdate()
    {
        $blog = new Blog();
        $manager = new BlogManager();

        $this->assertEquals(0, $blog->getCommentCount());

        // Simuler l’ajout de commentaires
        $comment1 = new \App\Entity\Comment();
        $comment2 = new \App\Entity\Comment();

        $blog->getComments()->add($comment1);
        $blog->getComments()->add($comment2);

        $manager->updateCommentCount($blog);

        $this->assertEquals(2, $blog->getCommentCount());
    }
}