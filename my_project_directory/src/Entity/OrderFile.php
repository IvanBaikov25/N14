<?php
namespace App\Entity;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
class OrderFile
{
    #[ORM\Id, ORM\GeneratedValue, ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(inversedBy: 'files'), ORM\JoinColumn(nullable: false)]
    private ?Order $order = null;

    #[ORM\Column(length: 255)]
    private string $originalName;

    #[ORM\Column(length: 255)]
    private string $storedPath;

    #[ORM\Column]
    private \DateTimeInterface $uploadedAt;

    public function __construct()
    {
        $this->uploadedAt = new \DateTime();
    }

    public function getId(): ?int
    {
        return $this->id;
    }
    public function getOrder(): ?Order
    {
        return $this->order;
    }
    public function setOrder(?Order $order): self
    {
        $this->order = $order;
        return $this;
    }
    public function getOriginalName(): string
    {
        return $this->originalName;
    }
    public function setOriginalName(string $originalName): self
    {
        $this->originalName = $originalName;
        return $this;
    }
    public function getStoredPath(): string
    {
        return $this->storedPath;
    }
    public function setStoredPath(string $storedPath): self
    {
        $this->storedPath = $storedPath;
        return $this;
    }
    public function getUploadedAt(): \DateTimeInterface
    {
        return $this->uploadedAt;
    }
}