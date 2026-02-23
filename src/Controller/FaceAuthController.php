<?php

namespace App\Controller;

use App\Entity\User;
use App\Service\FaceRecognitionService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Authentication\AuthenticationUtils;

#[Route('/face')]
class FaceAuthController extends AbstractController
{
    private FaceRecognitionService $faceRecognitionService;
    private EntityManagerInterface $entityManager;

    public function __construct(
        FaceRecognitionService $faceRecognitionService,
        EntityManagerInterface $entityManager
    ) {
        $this->faceRecognitionService = $faceRecognitionService;
        $this->entityManager = $entityManager;
    }

    #[Route('/enroll', name: 'app_face_enroll')]
    public function enroll(): Response
    {
        $this->denyAccessUnlessGranted('IS_AUTHENTICATED_FULLY');
        
        /** @var User $user */
        $user = $this->getUser();
        
        return $this->render('face/enroll.html.twig', [
            'user' => $user,
            'isFaceEnabled' => $user->isFaceEnabled(),
        ]);
    }

    #[Route('/enroll/submit', name: 'app_face_enroll_submit', methods: ['POST'])]
    public function enrollSubmit(Request $request): JsonResponse
    {
        // Check if user is authenticated
        $user = $this->getUser();
        if (!$user instanceof User) {
            return new JsonResponse([
                'success' => false,
                'message' => 'Authentication required. Please log in again.'
            ], 401);
        }
        
        $faceEncoding = $request->request->get('face_encoding');
        
        if (!$faceEncoding) {
            return new JsonResponse([
                'success' => false,
                'message' => 'No face encoding provided.'
            ], 400);
        }

        $encoding = json_decode($faceEncoding, true);
        
        if (!$this->faceRecognitionService->isValidEncoding($encoding)) {
            return new JsonResponse([
                'success' => false,
                'message' => 'Invalid face encoding. Please capture your face clearly.'
            ], 400);
        }

        try {
            $this->faceRecognitionService->saveFaceEncoding($user, $encoding);
            
            return new JsonResponse([
                'success' => true,
                'message' => 'Face enrolled successfully!'
            ]);
        } catch (\Exception $e) {
            return new JsonResponse([
                'success' => false,
                'message' => 'Failed to save face encoding: ' . $e->getMessage()
            ], 500);
        }
    }

    #[Route('/disable', name: 'app_face_disable', methods: ['POST'])]
    public function disable(): JsonResponse
    {
        $this->denyAccessUnlessGranted('IS_AUTHENTICATED_FULLY');
        
        /** @var User $user */
        $user = $this->getUser();
        
        try {
            $this->faceRecognitionService->removeFaceEncoding($user);
            
            return new JsonResponse([
                'success' => true,
                'message' => 'Face recognition disabled.'
            ]);
        } catch (\Exception $e) {
            return new JsonResponse([
                'success' => false,
                'message' => 'Failed to disable face recognition.'
            ], 500);
        }
    }

    #[Route('/verify', name: 'app_face_verify')]
    public function verify(Request $request, AuthenticationUtils $authenticationUtils): Response
    {
        // Get error if any
        $error = $authenticationUtils->getLastAuthenticationError();
        
        // Get last username entered
        $lastUsername = $authenticationUtils->getLastUsername();

        // Check if there are users with face enabled
        $faceEnabledUsers = $this->entityManager->getRepository(User::class)->findBy([
            'isFaceEnabled' => true,
        ]);

        // Only show face login if there are users with face enabled
        if (empty($faceEnabledUsers)) {
            return $this->redirectToRoute('app_login');
        }

        return $this->render('face/verify.html.twig', [
            'last_username' => $lastUsername,
            'error' => $error,
        ]);
    }

    #[Route('/check-enabled', name: 'app_face_check_enabled', methods: ['GET'])]
    public function checkEnabled(): JsonResponse
    {
        $faceEnabledUsers = $this->entityManager->getRepository(User::class)->findBy([
            'isFaceEnabled' => true,
        ]);

        return new JsonResponse([
            'enabled' => count($faceEnabledUsers) > 0,
        ]);
    }
}
