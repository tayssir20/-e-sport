<?php

namespace App\Controller\Admin;

use App\Entity\Stream;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\File\File;
use App\Form\StreamType;

#[Route('/admin/stream')]
class StreamController extends AbstractController
{
    #[Route('/', name: 'admin_stream_index')]
    public function index(EntityManagerInterface $em): Response
    {
        // Récupérer le flux existant ou en créer un
        $stream = $em->getRepository(Stream::class)->findOneBy([]);
        if (!$stream) {
            $stream = new Stream();
            $stream->setUrl('http://192.168.126.144:8080/hls/match1.m3u8');
            $stream->setIsActive(true);
            $em->persist($stream);
            $em->flush();
        }
$videos = $em->getRepository(Stream::class)->findAll();
        return $this->render('admin/stream/index.html.twig', [
            'stream' => $stream,
            'streamUrl' => $stream->getUrl(),
            'videos' => $videos,
        ]);
    }

    #[Route('/start/{id}', name: 'admin_stream_start')]
    public function start(Stream $stream, EntityManagerInterface $em): Response
    {
          // Désactiver tous les streams
    $em->createQuery('UPDATE App\Entity\Stream s SET s.isActive = false')
       ->execute();

    // Activer celui sélectionné
    $stream->setIsActive(true);
    $em->flush();

    return $this->redirectToRoute('admin_stream_index');
    }
    #[Route('/upload', name: 'admin_stream_upload')]
public function upload(Request $request, EntityManagerInterface $em): Response
{
    $stream = new Stream(); // nouvelle vidéo

    $form = $this->createForm(StreamType::class, $stream);
    $form->handleRequest($request);

    if ($form->isSubmitted() && $form->isValid()) {
        $stream->setIsActive(false); // active la vidéo
        $em->persist($stream);
        $em->flush();

        return $this->redirectToRoute('admin_stream_index');
    }

    return $this->render('admin/stream/upload.html.twig', [
        'form' => $form->createView(),
    ]);
}

    
    
}