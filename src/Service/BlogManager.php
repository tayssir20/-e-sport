<?php
namespace App\Service;

use App\Entity\Blog;
use App\Entity\Rating;

class BlogManager
{
    public function addRating(Blog $blog, Rating $rating): bool
    {
        if ($blog->hasUserRated($rating->getUser()->getId())) {
            throw new \InvalidArgumentException('Utilisateur a déjà noté ce blog.');
        }
        $blog->addRating($rating);
        return true;
    }

    public function updateCommentCount(Blog $blog): void
    {
        $blog->setCommentCount(count($blog->getComments()));
    }
}