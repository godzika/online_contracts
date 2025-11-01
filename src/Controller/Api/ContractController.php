<?php
namespace App\Controller\Api;

use App\Entity\Contract;
use App\Repository\ContractRepository;
use Doctrine\ORM\EntityManagerInterface;
use Random\RandomException;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Mailer\Exception\TransportExceptionInterface;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Serializer\Exception\ExceptionInterface;
use Symfony\Component\Serializer\SerializerInterface;
use Symfony\Component\Validator\Validator\ValidatorInterface;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Email;
use Twig\Environment as TwigEnvironment;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\ResponseHeaderBag;
use Symfony\Component\Filesystem\Filesystem;


#[Route('/api/contract')]
class ContractController extends AbstractController
{
    // GET /api/contract - ყველა კონტრაქტის სია
    #[Route('', name: 'api_contract_index', methods: ['GET'])]
    public function index(ContractRepository $contractRepository): JsonResponse
    {
        // ვაბრუნებთ მხოლოდ ამ ადმინის მიერ შექმნილ კონტრაქტებს
        $contracts = $contractRepository->findBy(['createdBy' => $this->getUser()]);

        // ვიყენებთ 'contract:read' ჯგუფს სერიალიზაციისთვის
        return $this->json($contracts, Response::HTTP_OK, [], ['groups' => 'contract:read']);
    }


    #[Route('', name: 'api_contract_new', methods: ['POST'])]
    public function new(
        Request $request,
        EntityManagerInterface $em,
        SerializerInterface $serializer,
        ValidatorInterface $validator,
        // === დაამატე ეს არგუმენტები ===
        MailerInterface $mailer,
        TwigEnvironment $twig,
        string $frontendSignUrl
        // ==============================
    ): JsonResponse {

        try {
            $contract = $serializer->deserialize($request->getContent(), Contract::class, 'json');
        } catch (\Exception $e) {
            return $this->json(['message' => 'Invalid JSON body'], Response::HTTP_BAD_REQUEST);
        }

        $errors = $validator->validate($contract);
        if (count($errors) > 0) {
            return $this->json($errors, Response::HTTP_BAD_REQUEST);
        }

        $contract->setCreatedBy($this->getUser());
        $contract->setStatus('pending');
        $contract->setUniqueToken(bin2hex(random_bytes(32)));
        $contract->setCreatedAt(new \DateTimeImmutable());

        $em->persist($contract);
        $em->flush();

        // === იმეილის გაგზავნის ლოგიკა ===
        try {
            // 1. შექმენი ხელმოწერის უნიკალური ბმული
            $signUrl = $frontendSignUrl . $contract->getUniqueToken();

            // 2. დაარენდერე იმეილის Twig შაბლონი
            $emailBody = $twig->render('emails/sign_invitation.html.twig', [
                'signee_name' => $contract->getSigneeName(),
                'contract_title' => $contract->getTitle(),
                'sign_url' => $signUrl,
            ]);

            // 3. შექმენი და გააგზავნე იმეილი
            $email = (new Email())
                ->from('no-reply@yourcompany.com') // შეცვალე შენი იმეილით
                ->to($contract->getSigneeEmail())
                ->subject('მოთხოვნა ხელმოწერაზე: ' . $contract->getTitle())
                ->html($emailBody);

            $mailer->send($email);

        } catch (\Exception $e) {
            // თუ იმეილი არ გაიგზავნა, დაალოგინე შეცდომა
            // შეგიძლია აქ $logger-ი გამოიყენო
            // ამ ეტაპზე, უბრალოდ ვაბრუნებთ შექმნილ კონტრაქტს,
            // მაგრამ რეალურ პროექტში ეს შეცდომა უნდა დამუშავდეს
        } catch (TransportExceptionInterface $e) {
        }
        // ===================================

        return $this->json($contract, Response::HTTP_CREATED, [], ['groups' => 'contract:read']);
    }

    // GET /api/contract/{id} - ერთი კონტრაქტის ჩვენება
    #[Route('/{id}', name: 'api_contract_show', methods: ['GET'])]
    public function show(Contract $contract): JsonResponse
    {
        // უსაფრთხოება: ვამოწმებთ, რომ ადმინს მხოლოდ თავისი კონტრაქტის ნახვა შეუძლია
        if ($contract->getCreatedBy() !== $this->getUser()) {
            return $this->json(['message' => 'Not authorized'], Response::HTTP_FORBIDDEN);
        }

        return $this->json($contract, Response::HTTP_OK, [], ['groups' => 'contract:read']);
    }

    #[Route('/{id}/download', name: 'api_contract_download_pdf', methods: ['GET'])]
    public function downloadPdf(Contract $contract, string $pdfStorageDir): Response
    {
        // 1. უსაფრთხოების შემოწმება: მხოლოდ ამ კონტრაქტის ავტორს შეუძლია გადმოწერა
        if ($contract->getCreatedBy() !== $this->getUser()) {
            return $this->json(['message' => 'Not authorized'], Response::HTTP_FORBIDDEN);
        }

        // 2. ფაილის არსებობის შემოწმება
        $pdfPath = $contract->getPdfPath();
        if (!$pdfPath) {
            return $this->json(['message' => 'No PDF found for this contract'], Response::HTTP_NOT_FOUND);
        }

        // $pdfStorageDir ავტომატურად მოდის config/services.yaml-დან
        $filePath = $pdfStorageDir . '/' . $pdfPath;

        if (!file_exists($filePath)) {
            // თუ ფაილი სერვერზე რატომღაც წაიშალა
            return $this->json(['message' => 'File not found on server.'], Response::HTTP_NOT_FOUND);
        }

        // 3. ფაილის დაბრუნება (стриминг)
        // $this->file() არის AbstractController-ის დამხმარე მეთოდი
        // ResponseHeaderBag::DISPOSITION_INLINE - ეუბნება ბრაუზერს, რომ სცადოს ფაილის გახსნა (და არა პირდაპირ გადმოწერა)
        return $this->file($filePath, $contract->getTitle() . '.pdf', ResponseHeaderBag::DISPOSITION_INLINE);
    }

    /**
     * ახალი ენდფოინთი კონტრაქტის წასაშლელად
     */
    #[Route('/{id}', name: 'api_contract_delete', methods: ['DELETE'])]
    public function delete(
        Contract $contract,
        EntityManagerInterface $em,
        Filesystem $filesystem, // ვიყენებთ Symfony-ს Filesystem კომპონენტს
        string $pdfStorageDir   // ეს მოდის services.yaml-დან
    ): JsonResponse {

        // 1. უსაფრთხოება: მხოლოდ ავტორს შეუძლია წაშლა
        if ($contract->getCreatedBy() !== $this->getUser()) {
            return $this->json(['message' => 'Not authorized'], Response::HTTP_FORBIDDEN);
        }

        // 2. ვშლით ასოცირებულ PDF ფაილს (თუ არსებობს)
        $pdfPath = $contract->getPdfPath();
        if ($pdfPath) {
            $filePath = $pdfStorageDir . '/' . $pdfPath;
            if ($filesystem->exists($filePath)) {
                $filesystem->remove($filePath);
            }
        }

        // 3. ვშლით კონტრაქტს (და მასთან დაკავშირებულ Signature-ს) ბაზიდან
        // შენიშვნა: Entity-ებში cascade: ['remove'] უნდა გეწეროს,
        // რათა კონტრაქტის წაშლამ ავტომატურად წაშალოს Signature.
        // თუ არ გიწერია, ამ ეტაპზე მაინც იმუშავებს.
        $em->remove($contract);
        $em->flush();

        // 4. ვაბრუნებთ "წარმატებით წაიშალა" პასუხს
        return $this->json(null, Response::HTTP_NO_CONTENT);
    }

}
