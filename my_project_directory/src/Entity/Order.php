<?php
namespace App\Entity;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity]
class Order
{
    #[ORM\Id, ORM\GeneratedValue, ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne, ORM\JoinColumn(nullable: false)]
    #[Assert\NotNull]
    private ?Client $client = null;

    #[ORM\ManyToMany(targetEntity: Dish::class)]
    #[Assert\Count(min: 1, minMessage: "Заказ должен содержать хотя бы одно блюдо")]
    private Collection $dishes;

    #[ORM\OneToMany(mappedBy: 'order', targetEntity: OrderFile::class, cascade: ['persist', 'remove'], orphanRemoval: true)]
    private Collection $files;

    #[ORM\Column]
    private \DateTimeInterface $createdAt;

    public function __construct()
    {
        $this->dishes = new ArrayCollection();
        $this->files = new ArrayCollection();
        $this->createdAt = new \DateTime();
    }

    public function getId(): ?int
    {
        return $this->id;
    }
    public function getClient(): ?Client
    {
        return $this->client;
    }
    public function setClient(?Client $client): self
    {
        $this->client = $client;
        return $this;
    }
    public function getDishes(): Collection
    {
        return $this->dishes;
    }
    public function addDish(Dish $dish): self
    {
        $this->dishes->add($dish);
        return $this;
    }
    public function removeDish(Dish $dish): self
    {
        $this->dishes->removeElement($dish);
        return $this;
    }
    public function getFiles(): Collection
    {
        return $this->files;
    }
    public function addFile(\App\Entity\OrderFile $file): self
    {
        $this->files->add($file);
        $file->setOrder($this);
        return $this;
    }
    public function removeFile(\App\Entity\OrderFile $file): self
    {
        $this->files->removeElement($file);
        return $this;
    }
    public function getCreatedAt(): \DateTimeInterface
    {
        return $this->createdAt;
    }
}