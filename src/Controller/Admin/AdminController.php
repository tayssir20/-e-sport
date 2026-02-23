<?php
namespace App\Controller\Admin;

use App\Entity\User;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Doctrine\ORM\EntityManagerInterface;

#[Route('/admin')]
class AdminController extends AbstractController
{
    #[Route('/', name: 'admin_dashboard')]
    public function index(EntityManagerInterface $em): Response
    {
        // Get all users from the database
        // $users = $em->getRepository(User::class)->findAll();

        return $this->render('admin/dashboard.html.twig', [
            // 'users' => $users
        ]);
    }
}




