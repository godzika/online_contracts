<?php

namespace App\Service;

use App\Entity\Contract;
use App\Entity\Signature;
use Doctrine\ORM\EntityManagerInterface;
use Knp\Snappy\Pdf as SnappyPdf; // ეს არის wkhtmltopdf-ის სერვისი
use Twig\Environment as TwigEnvironment;

class PdfGeneratorService
{
    public function __construct(
        private SnappyPdf $knpSnappy,
        private TwigEnvironment $twig,
        private EntityManagerInterface $entityManager,
        private string $pdfStorageDir // ეს ავტომატურად მოგვეწოდება services.yaml-დან
    ) {}

    public function generateAndSave(Contract $contract, Signature $signature): void
    {
        // 1. დაარენდერე HTML
        $html = $this->twig->render('pdf/signed_contract.html.twig', [
            'contract' => $contract,
            'signature' => $signature
        ]);

        // 2. დააგენერირე PDF კონტენტი
        $pdfContent = $this->knpSnappy->getOutputFromHtml($html);

        // 3. მოიფიქრე ფაილის სახელი
        $filename = $contract->getId() . '_' . $contract->getUniqueToken() . '.pdf';
        $filePath = $this->pdfStorageDir . '/' . $filename;

        // 4. შექმენი დირექტორია თუ არ არსებობს
        if (!file_exists($this->pdfStorageDir)) {
            mkdir($this->pdfStorageDir, 0775, true);
        }

        // 5. შეინახე ფაილი
        file_put_contents($filePath, $pdfContent);

        // 6. განაახლე კონტრაქტის Entity (მივაწეროთ PDF-ის მისამართი)
        $contract->setPdfPath($filename); // ვინახავთ მხოლოდ ფაილის სახელს
        $this->entityManager->persist($contract);
        // flush()-ს გამოვიძახებთ კონტროლერში
    }
}
