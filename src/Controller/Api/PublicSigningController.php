<?php

namespace App\Controller\Api;

use App\Entity\Contract;
use App\Entity\Signature;
use App\Repository\ContractRepository;
use App\Service\PdfGeneratorService; // <-- ჩვენი PDF სერვისი
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Routing\Annotation\Route;
use Psr\Log\LoggerInterface;

#[Route('/api/public')]
class PublicSigningController extends AbstractController
{
    /**
     * ენდფოინთი 1: კონტრაქტის მონაცემების მიღება (Front-End-ისთვის)
     * Front-End-ი ამას გამოიძახებს, რომ ხელმომწერს აჩვენოს კონტრაქტის ტექსტი
     */
    #[Route('/contract/{token}', name: 'api_public_contract_get', methods: ['GET'])]
    public function getContract(
        string $token,
        ContractRepository $contractRepo
    ): JsonResponse {
        $contract = $contractRepo->findOneBy(['uniqueToken' => $token]);

        // ვამოწმებთ, რომ კონტრაქტი არსებობს და სტატუსი არის 'pending' (ანუ ხელმოსაწერია)
        if (!$contract || $contract->getStatus() !== 'pending') {
            return $this->json(['message' => 'Contract not found or already signed'], Response::HTTP_NOT_FOUND);
        }

        // ვაბრუნებთ JSON-ს 'public:read' ჯგუფის მიხედვით
        // (ამ ჯგუფში გვაქვს მხოლოდ title, content, signeeName)
        return $this->json($contract, Response::HTTP_OK, [], ['groups' => 'public:read']);
    }

    /**
     * ენდფოინთი 2: ხელმოწერის მიღება და PDF-ის გენერაცია
     * Front-End-ი ამას გამოიძახებს, როცა მომხმარებელი "ხელმოწერას" დააჭერს
     */
    #[Route('/contract/{token}/sign', name: 'api_public_contract_sign', methods: ['POST'])]
    public function signContract(
        string $token,
        Request $request,
        ContractRepository $contractRepo,
        EntityManagerInterface $em,
        PdfGeneratorService $pdfGenerator,
        LoggerInterface $logger// <-- ვიყენებთ ჩვენს სერვისს
    ): JsonResponse {

        $contract = $contractRepo->findOneBy(['uniqueToken' => $token]);

        // დამატებითი შემოწმება
        if (!$contract || $contract->getStatus() !== 'pending') {
            return $this->json(['message' => 'Contract not found or already signed'], Response::HTTP_NOT_FOUND);
        }

        // 1. წავიკითხოთ JSON payload, რომელსაც Front-End-ი გვიგზავნის
        $data = json_decode($request->getContent(), true);
        $signatureData = $data['signatureData'] ?? null; // ველოდებით { "signatureData": "data:image/png;base64,..." }

        if (empty($signatureData)) {
            return $this->json(['message' => 'Signature data is missing'], Response::HTTP_BAD_REQUEST);
        }

        // 2. შევქმნათ Signature ობიექტი
        $signature = new Signature();
        $signature->setContract($contract);
        $signature->setSignatureData($signatureData); // ვინახავთ Base64 სურათს
        $signature->setIpAddress($request->getClientIp());
        $signature->setSignedAt(new \DateTimeImmutable());

        $em->persist($signature);

        // 3. განვაახლოთ Contract-ის სტატუსი
        $contract->setStatus('signed');
        $contract->setSignedAt(new \DateTimeImmutable());

        // 4. დავაგენერიროთ და შევინახოთ PDF (ეს სერვისი $contract-საც დაა-persist-ებს)
        try {
            $pdfGenerator->generateAndSave($contract, $signature);
        } catch (\Exception $e) {
            // === დაამატე ეს ხაზი, რომ ვნახოთ რეალური შეცდომა ===
            $logger->error('PDF Generation Failed: ' . $e->getMessage(), ['trace' => $e->getTraceAsString()]);
            // ===============================================

            // თუ PDF გენერაცია ჩავარდა
            return $this->json(['message' => 'Failed to generate PDF'], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
        // 5. შევინახოთ ყველაფერი ბაზაში (Signature და განახლებული Contract)
        $em->flush();

        return $this->json(['message' => 'Contract signed successfully'], Response::HTTP_OK);
    }
}
