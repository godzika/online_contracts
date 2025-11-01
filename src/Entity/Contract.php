<?php

namespace App\Entity;

use App\Repository\ContractRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Annotation\Groups;
use Symfony\Component\Validator\Constraints as Assert;


#[ORM\Entity(repositoryClass: ContractRepository::class)]
class Contract
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    #[Groups(['contract:read'])]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    #[Groups(['contract:read', 'public:read'])]
    #[Assert\NotBlank]
    private ?string $title = null;

    #[ORM\Column(type: Types::TEXT)]
    #[Groups(['contract:read', 'public:read'])]
    #[Assert\NotBlank]
    private ?string $content = null;

    #[ORM\Column(length: 255)]
    #[Groups(['contract:read', 'public:read'])]
    #[Assert\NotBlank]
    private ?string $signeeName = null;

    #[ORM\Column(length: 255)]
    #[Groups(['contract:read'])]
    #[Assert\NotBlank]
    private ?string $signeeEmail = null;

    #[ORM\Column(length: 255)]
    #[Groups(['contract:read'])]
    private ?string $status = null;

    #[ORM\ManyToOne(inversedBy: 'contracts')]
    #[ORM\JoinColumn(nullable: false)]
    #[Groups(['contract:read'])]
    private ?User $createdBy = null;

    #[Groups(['contract:read'])]
    #[ORM\Column(length: 255, unique: true)]
    private ?string $uniqueToken = null;

    #[ORM\Column(length: 255, nullable: true)]
    #[Groups(['contract:read'])]
    private ?string $pdfPath = null;

    #[ORM\Column(type: Types::DATETIME_IMMUTABLE)]
    #[Groups(['contract:read'])]
    private ?\DateTimeImmutable $createdAt = null;

    // === 1. დამატებული ველი ===
    #[ORM\Column(type: Types::DATETIME_IMMUTABLE, nullable: true)]
    #[Groups(['contract:read'])]
    private ?\DateTimeImmutable $signedAt = null;

    // === 2. დამატებული კავშირი Signature Entity-სთან ===
    #[ORM\OneToOne(mappedBy: 'contract', cascade: ['persist', 'remove'])]
    private ?Signature $signature = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getTitle(): ?string
    {
        return $this->title;
    }

    public function setTitle(string $title): static
    {
        $this->title = $title;

        return $this;
    }

    public function getContent(): ?string
    {
        return $this->content;
    }

    public function setContent(string $content): static
    {
        $this->content = $content;

        return $this;
    }

    public function getSigneeName(): ?string
    {
        return $this->signeeName;
    }

    public function setSigneeName(string $signeeName): static
    {
        $this->signeeName = $signeeName;

        return $this;
    }

    public function getSigneeEmail(): ?string
    {
        return $this->signeeEmail;
    }

    public function setSigneeEmail(string $signeeEmail): static
    {
        $this->signeeEmail = $signeeEmail;

        return $this;
    }

    public function getStatus(): ?string
    {
        return $this->status;
    }

    public function setStatus(string $status): static
    {
        $this->status = $status;

        return $this;
    }

    public function getCreatedBy(): ?User
    {
        return $this->createdBy;
    }

    public function setCreatedBy(?User $createdBy): static
    {
        $this->createdBy = $createdBy;

        return $this;
    }

    public function getUniqueToken(): ?string
    {
        return $this->uniqueToken;
    }

    public function setUniqueToken(string $uniqueToken): static
    {
        $this->uniqueToken = $uniqueToken;

        return $this;
    }

    public function getPdfPath(): ?string
    {
        return $this->pdfPath;
    }

    public function setPdfPath(?string $pdfPath): static
    {
        $this->pdfPath = $pdfPath;

        return $this;
    }

    public function getCreatedAt(): ?\DateTimeImmutable
    {
        return $this->createdAt;
    }

    public function setCreatedAt(\DateTimeImmutable $createdAt): static
    {
        $this->createdAt = $createdAt;

        return $this;
    }

    // === 3. დამატებული მეთოდები ===

    public function getSignedAt(): ?\DateTimeImmutable
    {
        return $this->signedAt;
    }

    public function setSignedAt(?\DateTimeImmutable $signedAt): static
    {
        $this->signedAt = $signedAt;

        return $this;
    }

    public function getSignature(): ?Signature
    {
        return $this->signature;
    }

    public function setSignature(Signature $signature): static
    {
        // set the owning side of the relation if required
        if ($signature->getContract() !== $this) {
            $signature->setContract($this);
        }

        $this->signature = $signature;

        return $this;
    }
}
