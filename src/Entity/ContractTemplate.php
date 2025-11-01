<?php

namespace App\Entity;

use App\Repository\ContractTemplateRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Annotation\Groups; // <-- დამატებულია API-სთვის

#[ORM\Entity(repositoryClass: ContractTemplateRepository::class)]
class ContractTemplate
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    #[Groups(['template:read'])] // <-- დამატებულია
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    #[Groups(['template:read'])] // <-- დამატებულია
    private ?string $name = null; // <-- შესწორებულია (იყო $ნაname)

    #[ORM\Column(length: 255)]
    #[Groups(['template:read'])] // <-- დამატებულია
    private ?string $title = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    #[Groups(['template:read'])] // <-- დამატებულია
    private ?string $content = null;

    #[ORM\ManyToOne(inversedBy: 'contractTemplates')]
    #[ORM\JoinColumn(nullable: false)] // <-- დამატებულია (რეკომენდებულია)
    private ?User $createdBy = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getName(): ?string // <-- შესწორებულია
    {
        return $this->name; // <-- შესწორებულია
    }

    public function setName(string $name): static // <-- შესწორებულია
    {
        $this->name = $name; // <-- შესწორებულია

        return $this;
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

    public function setContent(?string $content): static
    {
        $this->content = $content;

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
}
