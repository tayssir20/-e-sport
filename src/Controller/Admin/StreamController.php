<?php

namespace App\Controller\Admin;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route; // <-- attribut PHP 8+

class StreamController extends AbstractController
{
    #[Route('/admin/stream', name: 'admin_stream_index')]
    public function index(): Response
    {
        // URL de ton flux HLS OBS/Nginx
        $streamUrl = "http://192.168.126.144/hls/match1.m3u8";

        return $this->render('admin/stream/index.html.twig', [
            'streamUrl' => $streamUrl
        ]);
    }
}
