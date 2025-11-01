<?php

namespace App\Entity;

use App\Repository\SignatureRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: SignatureRepository::class)]
#[ORM\Table(name: '`signature`')] // 'signature' დაცული სიტყვაა ზოგ ბაზაში, ბრჭყალები უსაფრთხოა
class Signature
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    /**
     * OneToOne კავშირი Contract-თან.
     * ხელმოწერა ეკუთვნის ერთ კონტრაქტს.
     */
    #[ORM\OneToOne(inversedBy: 'signature', cascade: ['persist', 'remove'])]
    #[ORM\JoinColumn(nullable: false)]
    private ?Contract $contract = null;

    /**
     * აქ ვინახავთ ხელმოწერის Base64 სურათს,
     * რომელსაც Front-End-ი გვიგზავნის (data:image/png;base64,...).
     * 'text' ტიპი საკმარისად დიდია ამისთვის.
     */
    #[ORM\Column(type: Types::TEXT)]
    private ?string $signatureData = null;

    /**
     * ხელმომწერის IP მისამართი (აუდიტისთვის).
     */
    #[ORM\Column(length: 45, nullable: true)]
    private ?string $ipAddress = null;

    /**
     * ხელმოწერის ზუსტი დრო.
     */
    #[ORM\Column(type: Types::DATETIME_IMMUTABLE)]
    private ?\DateTimeImmutable $signedAt = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getContract(): ?Contract
    {
        return $this->contract;
    }

    public function setContract(Contract $contract): static
    {
        $this->contract = $contract;

        return $this;
    }

    public function getSignatureData(): ?string
    {
        return $this->signatureData;
    }

    public function setSignatureData(string $signatureData): static
    {
        $this->signatureData = $signatureData;

        return $this;
    }

    public function getIpAddress(): ?string
    {
        return $this->ipAddress;
    }

    public function setIpAddress(?string $ipAddress): static
    {
        $this->ipAddress = $ipAddress;

        return $this;
    }

    public function getSignedAt(): ?\DateTimeImmutable
    {
        return $this->signedAt;
    }

    public function setSignedAt(\DateTimeImmutable $signedAt): static
    {
        $this->signedAt = $signedAt;

        return $this;
    }
}
